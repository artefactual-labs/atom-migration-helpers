<?php

class MigrationDateParser
{
  public $parseMethodsUsed = [];
  public $parsePatternsMatched = [];
  public $parseExamples = [];

  function dateFormats()
  {
    $monthRe = "(January|Jan|February|Feb|March|Mar|April|Apr|May|June|Jun|July|Jul|August|Aug|September|Sep|October|Oct|November|Nov|December|Dec)";

    $dateFormats = [
      "/^[0-9]{4}$/" => "single",
      "/^[0-9]{4}-[0-9]{4}$/" => "range",
      "/^[0-9]{4}–[0-9]{4}$/" => "range",
      "/^[0-9]{4} to [0-9]{4}$/" => "range",
      "/^[0-9]{4}-[0-9]{2}$/" => "range_abbr",
      "/^prior to [0-9]{4}$/" => "prior_single",
      "/^Prior to [0-9]{4}$/" => "prior_single",
      "/^PRIOR TO [0-9]{4}$/" => "prior_single",
      "/^post [0-9]{4}$/" => "post_single",
      "/^TO [0-9]{4}$/" => "prior_single",
      "/^Pre [0-9]{4}$/" => "prior_single",
      "/^[0-9]{4}\?$/" => "ff",
      "/^[0-9]{4} ff$/" => "ff",
      "/^[0-9]{4}-$/" => "single_start_hyphen",
      "/^-[0-9]{4}$/" => "single_end_hyphen",
      "/^[0-9]{4}, [0-9]{4}-[0-9]{4}$/" => "multi_single_and_range",
      "/^[0-9]{4}-[0-9]{4} & [0-9]{4}$/" => "multi_range_and_single",
      "/^[0-9]{4}-[0-9]{4} and [0-9]{4}$/" => "multi_range_and_single",
      "/^[0-9]{4}-[0-9]{4}, [0-9]{4}$/" => "multi_single_and_range_reverse",
      "/^[0-9]{4}, [0-9]{4}$/" => "multi_single_and_single",
      "/^[0-9]{4} and [0-9]{4}$/" => "multi_single_and_single",
      "/^[0-9]{4}-[0-9]{4}, [0-9]{4}-[0-9]{4}$/" => "multi_range_and_range",
      "/^[0-9]{4}-[0-9]{4} [0-9]{4}-[0-9]{4}$/" => "multi_range_and_range",
      "/^[0-9]{4}-[0-9]{4} and [0-9]{4}-[0-9]{4}$/" => "multi_range_and_range",
      "/^[0-9]{4}, [0-9]{4}, [0-9]{4}$/" => "multi_single_single_single",
      "/^[0-9]{4}, [0-9]{4}, [0-9]{4}-[0-9]{4}$/" => "multi_single_single_and_range",
      "/^[0-9]{4}, [0-9]{4}-[0-9]{4}, [0-9]{4}-[0-9]{4}$/" => "multi_single_range_and_range",
      "/^[0-9]{4}-[0-9]{4}, [0-9]{4}, [0-9]{4}$/" => "multi_range_single_and_single",
      "/^". $monthRe ." ([0-9]{4})-". $monthRe ." ([0-9]{4})$/" => "proper_range",
      "/^". $monthRe ." ([0-9]{1,2}), ([0-9]{4})-". $monthRe ." ([0-9]{1,2}), ([0-9]{4})$/" => "proper_range_full",
      "/^". $monthRe ." ([0-9]{4})$/" => "proper_single",
      "/^". $monthRe ." ([0-9]{1,2}), ([0-9]{4})$/" => "proper_single_full",
      "/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/" => "backwards",
      "/^". $monthRe ."-[0-9]{2}$/" => "month_and_part_year",
    ];

    return $dateFormats;
  }

  function setStartAndEndDate($self)
  {
    // Attempt to parse date data from strinng
    $dates = $this->parseDate($self->columnValue('eventDates'));

    if (is_array($dates))
    {
      foreach ($dates as $range)
      {
        if (!empty($range[0]))
        {
          $startDateText = $self->columnValue('eventStartDates');

          if (!empty($startDateText))
          {
            $startDateText .= '|';
          }

          $startDateText .= $range[0];

          $self->columnValue('eventStartDates', $startDateText);
        }

        if (!empty($range[1]))
        {
          $endDateText = $self->columnValue('eventEndDates');

          if (!empty($endDateText))
          {
            $endDateText .= '|';
          }

          $endDateText .= $range[1];

          $self->columnValue('eventEndDates', $endDateText);
        }
      }
    }
  }

  function dateIsSingle($dateData)
  {
    return count($dateData) == 2 && !is_array($dateData[0]) && !is_array($dateData[1]);
  }

  function parseDate($datetext)
  {
    $datetext = str_replace("Septemebr", "September", $datetext);
    $datetext = str_replace("[", "", $datetext);
    $datetext = str_replace("]", "", $datetext);
    $datetext = str_replace("  ", " ", $datetext);
    $datetext = str_replace(" - ", "-", $datetext);
    $datetext = str_replace("- ", "-", $datetext);
    $datetext = str_replace(" -", "-", $datetext);
    $datetext = str_replace("`", "", $datetext);

    list($match, $matches) = $this->detectDate(trim($datetext));

    $dates = null;

    if (!empty($match))
    {
      $funcname = 'parse_'. $match;

      // Halt if no method exists
      if (!method_exists($this, $funcname))
      {
        print 'No method '. $funcname ."\n";
        exit;
      }

      $dates = $this->$funcname($datetext, $matches);

      // Store parsing example
      if (empty($this->parseExamples[$funcname]))
      {
        $this->parseExamples[$funcname] = $dates;
      }

      // Note that parsing method has been used
      if (!in_array($funcname, $this->parseMethodsUsed))
      {
        $this->parseMethodsUsed[] = $funcname;
      }

      if ($this->dateIsSingle($dates))
      {
        $dates = [$dates];
      }

      foreach ($dates as $index => $range)
      {
        if (strlen($range[0]) == 4)
        {
          $dates[$index][0] .= '-01-01';
        }

        if (strlen($range[1]) == 4)
        {
          $dates[$index][1] .= '-12-31';
        }
      }
    }

    return $dates;
  }

  function detectDate($date)
  {
    $remove = [
      "CIRCA ",
      " (narrated in 1963)",
      "Ca. ",
      "c. ",
      "CIRA ",
      "N.D., ",
      ", N.D.",
      "N.D. ",
      "N.D.",
      " (with gaps)",
      ", with gaps.",
      ", with gaps",
      " with gaps",
      " (WITH GAPS)",
      "c.a ",
      "c.",
      "ca. ",
      "ca.",
      "ca ",
      ", n.d.",
      ", n.d",
      ", nd",
      "nd, ",
      "n.d., ",
      " and nd",
      " WITH GAPS",
      " (originals)",
      "-present",
      "."
    ];

    foreach ($remove as $text)
    {
      $date = str_replace($text, "", $date);
    }

    $match = null;
    $matchesTemp = null;
    $matches = null;

    foreach ($this->dateFormats() as $dateFormat => $name)
    {
      if (preg_match($dateFormat, $date, $matchesTemp))
      {
        $match = $name;
        $matches = $matchesTemp;

        if (!in_array($dateFormat, $this->parsePatternsMatched))
        {
          $this->parsePatternsMatched[] = $dateFormat;
        }
      }
    }

    return [$match, $matches];
  }

  function fullYearFromShort($shortYear)
  {
    $pivotYear = 50;

    if ($shortYear < $pivotYear)
    {
      $year = 2000 + intval($shortYear);
    }
    else
    {
      $year = 1900 + intval($shortYear);
    }

    return $year;
  }

  function parse_backwards($date)
  {
    $year = substr($date, 6, 4);
    $month = substr($date, 3, 2);
    $day = substr($date, 0, 2);

    $date = $year .'-'. $month .'-'. $day;

    return [$date];
  }

  function parse_multi_single_and_range($date)
  {
    $startDate1 = substr($date, 0, 4);

    $startDate2 = substr($date, -9, 4);
    $endDate2 = substr($date, -4, 4);

    return [$startDate1, $endDate2];

    /*
    return [
      [$startDate1, ''],
      [$startDate2, $endDate2]
    ];
    */
  }

  function parse_multi_range_and_single($date)
  {
    $startDate1 = substr($date, 0, 4);
    $endDate1 = substr($date, 5, 4);

    $startDate2 = substr($date, -4, 4);

    return [$startDate1,  $startDate2];

    /*
    return [
      [$startDate1, $endDate1],
      [$startDate2, '']
    ];
    */
  }

  function parse_multi_single_and_range_reverse($date)
  {
    $startDate1 = substr($date, 0, 4);
    $endDate1 = substr($date, 5, 4);

    $startDate2 = substr($date, -4, 4);

    return [$startDate1, $startDate2];

    /*
    return [
      [$startDate1, $endDate1],
      [$startDate2, '']
    ];
    */
  }

  function parse_multi_range_single_and_single($date)
  {
    $startDate1 = substr($date, 0, 4);
    $endDate1 = substr($date, 5, 4);

    $startDate2 = substr($date, 11, 4);

    $startDate3 = substr($date, 17, 4);

    return [$startDate1, $startDate3];

    /*
    return [
      [$startDate1, $endDate1],
      [$startDate2, ''],
      [$startDate3, '']
    ];
    */
  }

  function parse_multi_single_and_single($date)
  {
    $startDate1 = substr($date, 0, 4);

    $startDate2 = substr($date, -4, 4);

    return [$startDate1, $startDate2];
 
    /*
    return [
      [$startDate1, ''],
      [$startDate2, '']
    ];
    */
  }

  function parse_multi_range_and_range($date)
  {
    $startDate1 = substr($date, 0, 4);
    $endDate1 = substr($date, 5, 4);

    $startDate2 = substr($date, -9, 4);
    $endDate2 = substr($date, -4, 4);

    return [$startDate1, $endDate2];

    /*
    return [
      [$startDate1, $endDate1],
      [$startDate2, $endDate2]
    ];
    */
  }

  function parse_multi_single_single_single($date)
  {
    $startDate1 = substr($date, 0, 4);

    $startDate2 = substr($date, 6, 4);

    $startDate3 = substr($date, -4, 4);

    return [$startDate1, $startDate3];

    /*
    return [
      [$startDate1, ''],
      [$startDate2, ''],
      [$startDate3, '']
    ];
    */
  }

  function parse_multi_single_single_and_range($date)
  {
    $startDate1 = substr($date, 0, 4);

    $startDate2 = substr($date, 6, 4);

    $startDate3 = substr($date, -9, 4);
    $endDate3 = substr($date, -4, 4);

    return [$startDate1, $endDate3];

    /*
    return [
      [$startDate1, ''],
      [$startDate2, ''],
      [$startDate3, $endDate3]
    ];
    */
  }

  function parse_multi_single_range_and_range($date)
  {
    $startDate1 = substr($date, 0, 4);

    $startDate2 = substr($date, 6, 4);
    $endDate2 = substr($date, 11, 4);

    $startDate3 = substr($date, -9, 4);
    $endDate3 = substr($date, -4, 4);

    return [$startDate1, $endDate3];

    /*
    return [
      [$startDate1, ''],
      [$startDate2, $endDate2],
      [$startDate3, $endDate3]
    ];
    */
  }

  function parse_single_start_hyphen($date)
  {
    $startDate = substr($date, 0, 4) .'-01-01';

    return [$startDate, ''];
  }

  function parse_single_end_hyphen($date)
  {
    $endDate = substr($date, 1, 4) .'-12-31';

    return ['', $endDate];
  }

  function parse_ff($date)
  {
    $startDate = substr($date, 0, 4) .'-01-01';

    return [$startDate, ''];
  }

  function parse_prior_single($date)
  {
    $endDate = substr($date, -4, 4) .'-12-31';

    return ['', $endDate];
  }

  function parse_post_single($date)
  {
    $startDate = substr($date, -4, 4) .'-01-01';

    return [$startDate, ''];
  }

  function parse_range($date)
  {
    return explode('-', $date);
  }

  function parse_single($date)
  {
    return [$date, ''];
  }

  function parse_range_abbr($date)
  {
    $chunks = explode('-', $date);
    $prefix = substr($chunks[0], 0, 2);

    return [$chunks[0], $prefix . $chunks[1]];
  }

  function parse_month_and_part_year($date, $matches)
  {
    $monthParsed = date_parse($matches[1]);
    $month = sprintf('%02d', $monthParsed['month']);

    $shortYear = substr($date, 4, 2);

    $parseDate = $this->fullYearFromShort($shortYear) .'-'. $month . '-01';

    return [$parseDate];
  }

  function parse_proper_range($date, $matches)
  {
    $start = date_parse($matches[1]);
    $startMonth = $start['month'];
    $startYear = $matches[2];

    $end = date_parse($matches[3]);
    $endMonth = $end['month'];
    $endYear = $matches[4];

    return [
      [$startYear .'-'. $startMonth .'-01', $endYear .'-'. $endMonth .'-31'],
    ];
  }

  function parse_proper_range_full($date, $matches)
  {
    $start = date_parse($matches[1]);
    $startMonth = $start['month'];
    $startYear = $matches[3];

    $end = date_parse($matches[4]);
    $endMonth = $end['month'];
    $endYear = $matches[6];

    return [
      [$startYear .'-'. $startMonth .'-01', $endYear .'-'. $endMonth .'-31'],
    ];
  }

  function parse_proper_single($date, $matches)
  {
    $start = date_parse($matches[1]);
    $startMonth = $start['month'];
    $startYear = $matches[2];

    return [
      [$startYear .'-'. $startMonth .'-01', ''],
    ];
  }

  function parse_proper_single_full($date, $matches)
  {
    $start = date_parse($matches[1]);
    $startMonth = $start['month'];
    $startYear = $matches[3];

    return [
      [$startYear .'-'. $startMonth .'-'. $matches[2], ''],
    ];
  }

  function unusedParseMethods()
  {
    $unused = [];

    foreach (get_class_methods($this) as $method)
    {
      if (strpos($method, "parse_") === 0)
      {
        if (!in_array($method, $this->parseMethodsUsed))
        {
          $unused[] = $method;
        }
      }
    }

    return $unused;
  }

  function unusedParsePatterns()
  {
    $unused = [];

    foreach (array_keys($this->dateFormats()) as $pattern)
    {
      if (!in_array($pattern, $this->parsePatternsMatched))
      {
        $unused[] = $pattern;
      }
    }

    return $unused;
  }
}
