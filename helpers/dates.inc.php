<?php

class MigrationDateParser
{
    public $parseMethodsUsed = [];
    public $parsePatternsMatched = [];
    public $parseExamples = [];

    public function dateFormats()
    {
        $monthRe = "(January|Jan|February|Feb|March|Mar|April|Apr|May|June|Jun|July|Jul|August|Aug|September|Sep|Sept|October|Oct|November|Nov|December|Dec)";

        $dateFormats = [
          "MONTH D YEAR" => "month_day_year",
          "MONTH D,YEAR" => "month_day_year",
          "YEAR" => "year",
          "YEAR-YEAR" => "range",
          "YEAR to YEAR" => "range",
          "YEAR-DD" => "range_abbr",
          "prior to YEAR" => "single_end_year",
          "Prior to YEAR" => "single_end_year",
          "PRIOR TO YEAR" => "single_end_year",
          "TO YEAR" => "single_end_year",
          "Pre YEAR" => "single_end_year",
          "post YEAR" => "post_single",
          "YEAR ff" => "ff",
          "YEAR-" => "single_start_hyphen",
          "-YEAR" => "single_end_year",
          "up to YEAR" => "single_end_year",
          "D-D MONTH YEAR" => "day_day_month_year",
          "YEAR MONTH D-D" => "year_month_day_day",
          "YEAR MONTH D-MONTH D" => "year_monthname_day_monthname_day",
          "YEAR MONTH-YEAR MONTH D" => "year_monthname_year_monthname_day",
          "YEAR MONTH-MONTH" => "year_monthname_hyphen_monthname",
          "YEAR MONTH\/MONTH" => "year_monthname_hyphen_monthname",
          "YEAR,YEAR-YEAR" => "multi_single_and_range",
          "YEAR-YEAR & YEAR" => "multi_range_and_single",
          "YEAR-YEAR and YEAR" => "multi_range_and_single",
          "YEAR-YEAR,YEAR" => "multi_range_and_single",
          "YEAR,YEAR" => "multi_single_and_single",
          "YEAR YEAR" => "multi_single_and_single",
          "YEAR;YEAR" => "multi_single_and_single",
          "YEAR\/YEAR" => "multi_single_and_single",
          "YEAR and YEAR" => "multi_single_and_single",
          "YEAR-YEAR,YEAR-YEAR" => "multi_range_and_range",
          "YEAR-YEAR;YEAR-YEAR" => "multi_range_and_range",
          "YEAR-YEAR YEAR-YEAR" => "multi_range_and_range",
          "YEAR YEAR-YEAR" => "multi_single_and_range",
          "YEAR-YEAR YEAR" => "multi_range_and_single",
          "YEAR-YEAR and YEAR-YEAR" => "multi_range_and_range",
          "YEAR,YEAR,YEAR" => "multi_single_single_single",
          "YEAR,YEAR,YEAR-YEAR" => "multi_single_single_and_range",
          "YEAR,YEAR-YEAR,YEAR-YEAR" => "multi_single_range_and_range",
          "YEAR-YEAR,YEAR,YEAR" => "multi_range_single_and_single",
          "MONTH YEAR-MONTH YEAR" => "proper_range",
          "MONTH YEAR" => "proper_single",
          "YEAR MONTH" => "proper_single_reverse",
          "DD-DD-YEAR" => "backwards",
          "YEAR MONTH D" => "year_monthname_day",
          "YEAR MONTH,D" => "year_monthname_day",
          "YEAR MONTH Dth" => "year_monthname_day",
          "D MONTH YEAR" => "day_monthname_year",
          "YEAR-YEAR MONTH" => "year_year_monthname",
          "YEAR MONTH-YEAR" => "year_monthname_to_year",
          "YEAR-MONTH YEAR" => "year_monthname_year",
          "YEAR MONTH-YEAR MONTH" => "year_monthname_year_monthname",
          "YEAR MONTH-YEAR MONTH" => "year_monthname_year_monthname",
          "YEAR MONTH-MONTH YEAR" => "year_monthname_monthname_year",
          "YEAR MONTH D-YEAR MONTH D" => "year_monthname_day_to_year_monthname_day",
          "YEAR MONTH D-MONTH D YEAR" => "year_monthname_day_to_monthname_day_year",
          "YEAR,MONTH D-YEAR,MONTH D" => "year_comma_monthname_day_to_year_comma_monthname_day",
          "YEAR MONTH,D-YEAR MONTH,D" => "year_comma_monthname_day_to_year_comma_monthname_day",
          "YEAR MONTH D-YEAR MONTH" => "year_monthname_day_to_year_monthname",
          "YEAR MONTH D;MONTH D YEAR" => "year_monthname_day_month_day_monthname",
          "YEAR YEAR YEAR" => "multi_year_year_year",
          "YEAR,YEAR,YEAR,YEAR" => "multi_year_year_year_year",
          "YEAR,YEAR,YEAR,YEAR,YEAR" => "multi_year_year_year_year_year",
          "YEAR,YEAR,YEAR,YEAR,YEAR,YEAR" => "multi_year_year_year_year_year_year",
          "YEAR YEAR YEAR-YEAR YEAR YEAR-YEAR YEAR" => "multi_year_year_range_year_range_year",
          "YEAR,YEAR-YEAR,YEAR" => "multi_year_range_year",
        ];

        return $dateFormats;
    }

    public function setStartAndEndDate($self)
    {
        // Attempt to parse date data from string
        $dates = $this->parseDate($self->columnValue('eventDates'));

        if (is_array($dates)) {
            foreach ($dates as $range) {
                if (!empty($range[0])) {
                    $startDateText = $self->columnValue('eventStartDates');

                    if (!empty($startDateText)) {
                        $startDateText .= '|';
                    }

                    $startDateText .= $range[0];

                    $self->columnValue('eventStartDates', $startDateText);
                }

                if (!empty($range[1])) {
                    $endDateText = $self->columnValue('eventEndDates');

                    if (!empty($endDateText)) {
                        $endDateText .= '|';
                    }

                    $endDateText .= $range[1];

                    $self->columnValue('eventEndDates', $endDateText);
                }
            }
        }
    }

    public function dateIsSingle($dateData)
    {
        return count($dateData) == 2 && !is_array($dateData[0]) && !is_array($dateData[1]);
    }

    public function parseDate($datetext)
    {
        $replace = [
          "Dec." => "Dec",
          "Septemebr" => "September",
          "[" => "",
          "]" => "",
          "  " => " ",
          " - " => "-",
          "- " => "-",
          " -" => "-",
          "`" => "",
          ", " => ",",
          " ," => ",",
          "; " => ";",
          " ;" => ";",
          "/ " => "/",
          " /" => "/",
        ];

        foreach ($replace as $string => $replacement) {
            $datetext = str_replace($string, $replacement, $datetext);
        }

        list($match, $matches) = $this->detectDate(trim($datetext));

        $dates = null;

        if (!empty($match)) {
            $funcname = 'parse_'. $match;

            // Halt if no method exists
            if (!method_exists($this, $funcname)) {
                print 'No method '. $funcname ."\n";
                exit;
            }

            $dates = $this->$funcname($datetext, $matches);

            // Store parsing example
            if (empty($this->parseExamples[$funcname])) {
                $this->parseExamples[$funcname] = $dates;
            }

            // Note that parsing method has been used
            if (!in_array($funcname, $this->parseMethodsUsed)) {
                $this->parseMethodsUsed[] = $funcname;
            }

            if ($this->dateIsSingle($dates)) {
                $dates = [$dates];
            }

            foreach ($dates as $index => $range) {
                if (strlen($range[0]) == 4) {
                    $dates[$index][0] .= '-01-01';
                }

                if (strlen($range[1]) == 4) {
                    $dates[$index][1] .= '-12-31';
                }
            }
        }

        return $dates;
    }

    public function detectDate($date)
    {
        $remove = [
          "?",
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
          "n.d",
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

        foreach ($remove as $text) {
            $date = str_replace($text, "", $date);
        }

        $match = null;
        $matchesTemp = null;
        $matches = null;

        foreach ($this->dateFormats() as $dateFormat => $name) {
            $dateFormat = "/^". $dateFormat ."$/";

            // Do month substitute last as it contains "D" which would otherwise be
            // interpreted as being the "D" token
            $subs = [
              "YEAR" => "([0-9]{4})",
    "DD" => "([0-9]{2})",
    "D" => "([0-9]{1,2})",
              "MONTH" => "(January|Jan|February|Feb|March|Mar|April|Apr|May|June|Jun|July|Jul|August|Aug|September|Sep|Sept|October|Oct|November|Nov|December|Dec)",
            ];

            foreach ($subs as $string => $replacement) {
                $dateFormat = str_replace($string, $replacement, $dateFormat);
            }

            if (preg_match($dateFormat, $date, $matchesTemp)) {
                $match = $name;
                $matches = $matchesTemp;

                if (!in_array($dateFormat, $this->parsePatternsMatched)) {
                    $this->parsePatternsMatched[] = $dateFormat;
                }
            }
        }

        return [$match, $matches];
    }

    public function fullYearFromShort($shortYear)
    {
        $pivotYear = 50;

        if ($shortYear < $pivotYear) {
            $year = 2000 + intval($shortYear);
        } else {
            $year = 1900 + intval($shortYear);
        }

        return $year;
    }

    public function parse_month_day_year($date, $matches)
    {
        $dateParts = date_parse($matches[1]);
        $startDate = $matches[3] .'-'. $dateParts['month'] .'-'. $matches[2];

        return [
            [$startDate, $startDate]
        ];
    }

    public function parse_backwards($date)
    {
        $year = substr($date, 6, 4);
        $month = substr($date, 3, 2);
        $day = substr($date, 0, 2);

        $date = $year .'-'. $month .'-'. $day;

        return [$date, $date];
    }

    public function parse_day_day_month_year($date, $matches)
    {
        $dateParts = date_parse($matches[3]);
        $startDate = $matches[4] .'-'. $dateParts['month'] .'-'. $matches[1];
        $endDate = $matches[4] .'-'. $dateParts['month'] .'-'. $matches[2];

        return [
            [$startDate, $endDate]
        ];
    }

    public function parse_year_month_day_day($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-'. $matches[3];
        $endDate = $matches[1] .'-'. $dateParts['month'] .'-'. $matches[4];

        return [
            [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_day_monthname_day($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-'. $matches[3];

        $dateParts = date_parse($matches[4]);
        $endDate = $matches[1] .'-'. $dateParts['month'] .'-'. $matches[5];

        return [
            [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_hyphen_monthname($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-01';

        $dateParts = date_parse($matches[3]);
        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, $dateParts['month'], $matches[1]);
        $endDate = $matches[1] .'-'. $dateParts['month'] .'-'. $daysInEndMonth;

        return [
            [$startDate, $endDate]
        ];
    }

    public function parse_multi_single_and_range($date, $matches)
    {
        $startDate1 = $matches[1];
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2];
        $endDate2 = $matches[3];

        return [
            [$startDate1, $endDate1],
            [$startDate2, $endDate2]
        ];
    }

    public function parse_multi_range_and_single($date, $matches)
    {
        $startDate1 = $matches[1];
        $endDate1 = $matches[2];

        $startDate2 = $matches[3];
        $endDate2 = $matches[3] .'-12-31';

        return [
            [$startDate1,  $endDate1],
            [$startDate2, $endDate2]
        ];
    }

    public function parse_multi_range_single_and_single($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[2] .'-12-31';

        $startDate2 = $matches[3] .'-01-01';
        $endDate2 = $matches[3] .'-12-31';

        $startDate3 = $matches[4] .'-01-01';
        $endDate3 = $matches[4] .'-12-31';

        return [
            [$startDate1, $endDate1],
            [$startDate2, $endDate2],
            [$startDate3, $endDate3]
        ];
    }

    public function parse_multi_single_and_single($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2] .'-01-01';
        $endDate2 = $matches[2] .'-12-31';

        return [
                [$startDate1, $endDate1],
            [$startDate2, $endDate2]
        ];
    }

    public function parse_multi_range_and_range($date, $matches)
    {
        $startDate1 = $matches[1];
        $endDate1 = $matches[2];

        $startDate2 = $matches[3];
        $endDate2 = $matches[4];

        return [
            [$startDate1, $endDate1],
            [$startDate2, $endDate2]
        ];
    }

    public function parse_multi_single_single_single($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2] .'-01-01';
        $endDate2 = $matches[2] .'-12-31';

        $startDate3 = $matches[3] .'-01-01';
        $endDate3 = $matches[3] .'-12-31';

        return [
            [$startDate1, $endDate1],
            [$startDate2, $endDate2],
            [$startDate3, $endDate3],
        ];
    }

    public function parse_multi_single_single_and_range($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2] .'-01-01';
        $endDate2 = $matches[2] .'-12-31';

        $startDate3 = $matches[3] .'-01-01';
        $endDate3 = $matches[4] .'-12-31';

        return [
            [$startDate1, $endDate1],
            [$startDate2, $endDate2],
            [$startDate3, $endDate3]
        ];
    }

    public function parse_multi_single_range_and_range($date, $matches)
    {
        $startDate1 = $matches[1];

        $startDate2 = $matches[2];
        $endDate2 = $matches[3];

        $startDate3 = $matches[4];
        $endDate3 = $matches[5];

        return [
            [$startDate1, ""],
        [$startDate2, $endDate2],
            [$startDate3, $endDate3]
        ];
    }

    public function parse_single_start_hyphen($date, $matches)
    {
        $startDate = $matches[1] .'-01-01';

        return [$startDate, ''];
    }

    public function parse_single_end_year($date, $matches)
    {
        $endDate = $matches[1] .'-12-31';

        return ['', $endDate];
    }

    public function parse_ff($date, $matches)
    {
        $startDate = $matches[1] .'-01-01';

        return [$startDate, ''];
    }

    public function parse_post_single($date, $matches)
    {
        $startDate = $matches[1] .'-01-01';

        return [$startDate, ''];
    }

    public function parse_range($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[2] .'-12-31';

        return [
          [$startDate1, $endDate1],
        ];
    }

    public function parse_year($date)
    {
        $startDate1 = $date .'-01-01';
        $endDate1 = $date .'-12-31';

        return [
          [$startDate1, $endDate1]
        ];
    }

    public function parse_range_abbr($date)
    {
        $chunks = explode('-', $date);
        $prefix = substr($chunks[0], 0, 2);

        $startDate1 = $chunks[0] .'-01-01';
        $endDate1 = $prefix . $chunks[1] .'-12-31';

        return [
          [$startDate1, $endDate1]
        ];
    }

    public function parse_proper_range($date, $matches)
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

    public function parse_proper_single($date, $matches)
    {
        $dateParts = date_parse($matches[1]);
        $startMonth = $dateParts['month'];
        $startYear = $matches[2];

        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, $startMonth, $matches[2]);

        $startDate1 = $startYear .'-'. $startMonth .'-01';
        $endDate1 = $startYear .'-'. $startMonth .'-'. $daysInEndMonth;

        return [
          [$startDate1, $endDate1],
        ];
    }

    public function parse_proper_single_reverse($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startMonth = $dateParts['month'];
        $startYear = $matches[1];

        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, $startMonth, $matches[1]);

        $startDate1 = $startYear .'-'. $startMonth .'-01';
        $endDate1 = $startYear .'-'. $startMonth .'-'. $daysInEndMonth;

        return [
          [$startDate1, $endDate1]
        ];
    }

    public function parse_year_monthname_day($date, $matches)
    {
        $dateParts = date_parse($date);

        $startDate = $dateParts["year"] .'-'. $dateParts["month"] . '-'. $matches[3];
        $endDate = $startDate;

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_day_monthname_year($date, $matches)
    {
        $dateParts = date_parse($date);

        $startDate = $dateParts["year"] .'-'. $dateParts["month"] . '-'. $matches[1];
        $endDate = $startDate;

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_year_monthname($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-01';

        $dateParts = date_parse($matches[4]);
        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, $dateParts['month'], $matches[3]);
        $endDate = $matches[3] .'-'. $dateParts['month'] .'-'. $daysInEndMonth;

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_monthname_year($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-01';

        $dateParts = date_parse($matches[3]);
        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, $dateParts['month'], $matches[4]);
        $endDate = $matches[4] .'-'. $dateParts['month'] .'-'. $daysInEndMonth;

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_day_to_year_monthname_day($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-'. $matches[3];

        $dateParts = date_parse($matches[5]);
        $endDate = $matches[4] .'-'. $dateParts['month'] .'-'. $matches[6];

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_day_to_year_monthname($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-'. $matches[3];

        $dateParts = date_parse($matches[5]);

        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, $dateParts['month'], $matches[3]);

        $endDate = $matches[4] .'-'. $dateParts['month'] .'-'. $daysInEndMonth;

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_day_to_monthname_day_year($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-'. $matches[3];

        $dateParts = date_parse($matches[4]);
        $endDate = $matches[6] .'-'. $dateParts['month'] .'-'. $matches[5];

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_comma_monthname_day_to_year_comma_monthname_day($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-'. $matches[3];

        $dateParts = date_parse($matches[5]);
        $endDate = $matches[4] .'-'. $dateParts['month'] .'-'. $matches[6];

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_year_monthname($date, $matches)
    {
        $startDate = $matches[1] .'-01-01';

        $dateParts = date_parse($matches[3]);
        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, $dateParts['month'], $matches[2]);

        $endDate = $matches[2] .'-'. $dateParts['month'] .'-'. $daysInEndMonth;

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_year($date, $matches)
    {
        $startDate = $matches[1] .'-01-01';

        $dateParts = date_parse($matches[2]);
        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, $dateParts['month'], $matches[3]);

        $endDate = $matches[3] .'-'. $dateParts['month'] .'-'. $daysInEndMonth;

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_to_year($date, $matches)
    {
        $dateParts = date_parse($matches[2]);

        $startDate = $matches[1] .'-'. $dateParts['month'] .'-01';

        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, 12, $matches[3]);

        $endDate = $matches[3] .'-12-'. $daysInEndMonth;

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_year_monthname_day($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $startDate = $matches[1] .'-'. $dateParts['month'] .'-01';

        $dateParts = date_parse($matches[4]);
        $daysInEndMonth = cal_days_in_month(CAL_JULIAN, $dateParts['month'], $matches[3]);
        $endDate = $matches[3] .'-'. $dateParts['month'] .'-'. $matches[5];

        return [
          [$startDate, $endDate]
        ];
    }

    public function parse_year_monthname_day_month_day_monthname($date, $matches)
    {
        $dateParts = date_parse($matches[2]);
        $date1 = $matches[1] .'-'. $dateParts['month'] .'-'. $matches[3];

        $dateParts = date_parse($matches[4]);
        $date2 = $matches[6] .'-'. $dateParts['month'] .'-'. $matches[5];

        return [
          [$date1, $date1],
          [$date2, $date2]
        ];
    }

    public function parse_multi_year_year_year($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2] .'-01-01';
        $endDate2 = $matches[2] .'-12-31';

        $startDate3 = $matches[3] .'-01-01';
        $endDate3 = $matches[3] .'-12-31';

        return [
          [$startDate1, $endDate1],
          [$startDate2, $endDate2],
          [$startDate3, $endDate3],
        ];
    }

    public function parse_multi_year_year_year_year($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2] .'-01-01';
        $endDate2 = $matches[2] .'-12-31';

        $startDate3 = $matches[3] .'-01-01';
        $endDate3 = $matches[3] .'-12-31';

        $startDate4 = $matches[4] .'-01-01';
        $endDate4 = $matches[4] .'-12-31';

        return [
          [$startDate1, $endDate1],
          [$startDate2, $endDate2],
          [$startDate3, $endDate3],
          [$startDate4, $endDate4],
        ];
    }

    public function parse_multi_year_year_year_year_year($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2] .'-01-01';
        $endDate2 = $matches[2] .'-12-31';

        $startDate3 = $matches[3] .'-01-01';
        $endDate3 = $matches[3] .'-12-31';

        $startDate4 = $matches[4] .'-01-01';
        $endDate4 = $matches[4] .'-12-31';

        $startDate5 = $matches[5] .'-01-01';
        $endDate5 = $matches[5] .'-12-31';

        return [
          [$startDate1, $endDate1],
          [$startDate2, $endDate2],
          [$startDate3, $endDate3],
          [$startDate4, $endDate4],
          [$startDate5, $endDate5],
        ];
    }

    public function parse_multi_year_year_year_year_year_year($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2] .'-01-01';
        $endDate2 = $matches[2] .'-12-31';

        $startDate3 = $matches[3] .'-01-01';
        $endDate3 = $matches[3] .'-12-31';

        $startDate4 = $matches[4] .'-01-01';
        $endDate4 = $matches[4] .'-12-31';

        $startDate5 = $matches[5] .'-01-01';
        $endDate5 = $matches[5] .'-12-31';

        $startDate6 = $matches[6] .'-01-01';
        $endDate6 = $matches[6] .'-12-31';

        return [
          [$startDate1, $endDate1],
          [$startDate2, $endDate2],
          [$startDate3, $endDate3],
          [$startDate4, $endDate4],
          [$startDate5, $endDate5],
          [$startDate6, $endDate6],
        ];
    }

    public function parse_multi_year_year_range_year_range_year($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2] .'-01-01';
        $endDate2 = $matches[2] .'-12-31';

        $startDate3 = $matches[3] .'-01-01';
        $endDate3 = $matches[4] .'-12-31';

        $startDate4 = $matches[5] .'-01-01';
        $endDate4 = $matches[5] .'-12-31';

        $startDate5 = $matches[6] .'-01-01';
        $endDate5 = $matches[7] .'-12-31';

        $startDate6 = $matches[8] .'-01-01';
        $endDate6 = $matches[8] .'-12-31';

        return [
          [$startDate1, $endDate1],
          [$startDate2, $endDate2],
          [$startDate3, $endDate3],
          [$startDate4, $endDate4],
          [$startDate5, $endDate5],
          [$startDate6, $endDate6],
        ];
    }

    public function parse_multi_year_range_year($date, $matches)
    {
        $startDate1 = $matches[1] .'-01-01';
        $endDate1 = $matches[1] .'-12-31';

        $startDate2 = $matches[2] .'-01-01';
        $endDate2 = $matches[3] .'-12-31';

        $startDate3 = $matches[4] .'-01-01';
        $endDate3 = $matches[4] .'-12-31';

        return [
          [$startDate1, $endDate1],
          [$startDate2, $endDate2],
          [$startDate3, $endDate3],
        ];
    }

    public function getParseMethodsUsed()
    {
        return $this->parseMethodsUsed;
    }

    public function unusedParseMethods()
    {
        $unused = [];

        foreach (get_class_methods($this) as $method) {
            if (strpos($method, "parse_") === 0) {
                if (!in_array($method, $this->parseMethodsUsed)) {
                    $unused[] = $method;
                }
            }
        }

        return $unused;
    }

    public function unusedParsePatterns()
    {
        $unused = [];

        foreach (array_keys($this->dateFormats()) as $pattern) {
            if (!in_array($pattern, $this->parsePatternsMatched)) {
                $unused[] = $pattern;
            }
        }

        return $unused;
    }
}
