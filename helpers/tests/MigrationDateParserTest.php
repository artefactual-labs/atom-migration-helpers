<?php

use PHPUnit\Framework\TestCase;

include(dirname(dirname(dirname(__FILE__))) ."/helpers/dates.inc.php");

final class MigrationDateParserTest extends TestCase
{
    public function testNonDate(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("this is not a date");

        $this->assertSame($dates, null);
    }

    /**
     * @dataProvider provideMonthDateYearData
     */
    public function testParseMonthDayYear($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate($dateText);

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_month_day_year"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1987-10-6");
        $this->assertSame($dates[0][1], "1987-10-6");
    }

    public function provideMonthDateYearData()
    {
        return [
            ["Oct 6 1987"],
            ["Oct 6, 1987"],
        ];
    }

    public function testParseSimple(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2000");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2000-01-01");
        $this->assertSame($dates[0][1], "2000-12-31");
    }

    /**
     * @dataProvider provideRangeData
     */
    public function testParseRange($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2000-2001");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_range"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2000-01-01");
        $this->assertSame($dates[0][1], "2001-12-31");
    }

    public function provideRangeData()
    {
        return [
            ["2000-2001"],
            ["2000 to 2001"],
        ];
    }

    public function testParseRangeAbbr(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1981-82");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_range_abbr"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1981-01-01");
        $this->assertSame($dates[0][1], "1982-12-31");
    }

    /**
     * @dataProvider providePriorSingleData
     */
    public function testParsePriorSingle($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate($dateText);

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_single_end_year"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "");
        $this->assertSame($dates[0][1], "1982-12-31");
    }

    public function providePriorSingleData()
    {
        return [
            ["prior to 1982"],
            ["Prior to 1982"],
            ["PRIOR TO 1982"],
            ["TO 1982"],
            ["Pre 1982"]
        ];
    }

    public function testParsePostSingle(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("post 1982");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_post_single"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "");
    }

    /**
     * @dataProvider provideFf
     */
    public function testParseFf($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate($dateText);

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_ff"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "");
    }

    public function provideFf()
    {
        return [
            ["1982 ff"],
        ];
    }

    public function testParseSingleStartHyphen(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1982-");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_single_start_hyphen"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "");
    }

    /**
     * @dataProvider provideSingleEndYear
     */
    public function testParseSingleEndYear($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate($dateText);

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_single_end_year"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "");
        $this->assertSame($dates[0][1], "1982-12-31");
    }
    public function provideSingleEndYear()
    {
        return [
            ["-1982"],
            ["up to 1982"],
        ];
    }

    public function testParseMultiSingleAndRange(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1982, 1984-1985");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_single_and_range"]);

        $this->assertSame(count($dates), 2);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "1982-12-31");
        $this->assertSame($dates[1][0], "1984-01-01");
        $this->assertSame($dates[1][1], "1985-12-31");
    }

    /**
     * @dataProvider provideMultiRangeAndSingle
     */
    public function testParseMultiRangeAndSingle($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate($dateText);

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_range_and_single"]);

        $this->assertSame(count($dates), 2);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "1983-12-31");
        $this->assertSame($dates[1][0], "1985-01-01");
        $this->assertSame($dates[1][1], "1985-12-31");
    }

    public function provideMultiRangeAndSingle()
    {
        return [
            ["1982-1983 & 1985"],
        ["1982-1983 and 1985"],
            ["1982-1983, 1985"]
        ];
    }

    /**
     * @dataProvider provideMultiSingleAndSingle
     */
    public function testParseMultiSingleAndSingle($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate($dateText);

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_single_and_single"]);

        $this->assertSame(count($dates), 2);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "1982-12-31");
        $this->assertSame($dates[1][0], "1985-01-01");
        $this->assertSame($dates[1][1], "1985-12-31");
    }

    public function provideMultiSingleAndSingle()
    {
        return [
            ["1982/1985"],
            ["1982 1985"],
            ["1982, 1985"],
        ["1982 and 1985"],
        ["1982; 1985"],
        ];
    }

    /**
     * @dataProvider provideMultiRangeAndRange
     */
    public function testParseMultiRangeAndRange($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate($dateText);

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_range_and_range"]);

        $this->assertSame(count($dates), 2);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "1983-12-31");
        $this->assertSame($dates[1][0], "1985-01-01");
        $this->assertSame($dates[1][1], "1986-12-31");
    }

    public function provideMultiRangeAndRange()
    {
        return [
            ["1982-1983, 1985-1986"],
            ["1982-1983 1985-1986"],
            ["1982-1983 and 1985-1986"],
            ["1982-1983; 1985-1986"],
        ];
    }

    public function testParseMultiSingleSingleSingle(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1982, 1984, 1986");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_single_single_single"]);

        $this->assertSame(count($dates), 3);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "1982-12-31");
        $this->assertSame($dates[1][0], "1984-01-01");
        $this->assertSame($dates[1][1], "1984-12-31");
        $this->assertSame($dates[2][0], "1986-01-01");
        $this->assertSame($dates[2][1], "1986-12-31");
    }

    public function testParseMultiSingleSingleAndRange(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1982, 1984, 1986-1987");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_single_single_and_range"]);

        $this->assertSame(count($dates), 3);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "1982-12-31");
        $this->assertSame($dates[1][0], "1984-01-01");
        $this->assertSame($dates[1][1], "1984-12-31");
        $this->assertSame($dates[2][0], "1986-01-01");
        $this->assertSame($dates[2][1], "1987-12-31");
    }

    public function testParseMultiSingleRangeAndRange(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1982, 1984-1985, 1987-1988");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_single_range_and_range"]);

        $this->assertSame(count($dates), 3);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "");
        $this->assertSame($dates[1][0], "1984-01-01");
        $this->assertSame($dates[1][1], "1985-12-31");
        $this->assertSame($dates[2][0], "1987-01-01");
        $this->assertSame($dates[2][1], "1988-12-31");
    }

    public function testParseMultiRangeSingleAndSingle(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1982-1983, 1985, 1987");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_range_single_and_single"]);

        $this->assertSame(count($dates), 3);
        $this->assertSame($dates[0][0], "1982-01-01");
        $this->assertSame($dates[0][1], "1983-12-31");
        $this->assertSame($dates[1][0], "1985-01-01");
        $this->assertSame($dates[1][1], "1985-12-31");
        $this->assertSame($dates[2][0], "1987-01-01");
        $this->assertSame($dates[2][1], "1987-12-31");
    }

    public function testParseProperRange(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("January 1982-March 1983");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_proper_range"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1982-1-01");
        $this->assertSame($dates[0][1], "1983-3-31");
    }

    public function testParseProperSingle(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("January 1982");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_proper_single"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1982-1-01");
        $this->assertSame($dates[0][1], "1982-1-31");
    }

    public function testParseProperSingleReverse(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1982 January");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_proper_single_reverse"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1982-1-01");
        $this->assertSame($dates[0][1], "1982-1-31");
    }

    public function testParseBackwards(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("29-03-1982");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_backwards"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1982-03-29");
        $this->assertSame($dates[0][1], "1982-03-29");
    }

    public function testParseDayDayMonthnameYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("5-7 July 1981");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_day_day_month_year"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1981-7-5");
        $this->assertSame($dates[0][1], "1981-7-7");
    }

    public function testParseYearMonthnameDayDay(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1981 July 5-7");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_month_day_day"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1981-7-5");
        $this->assertSame($dates[0][1], "1981-7-7");
    }

    public function testParseYearMonthnameDayMonthnameDay(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1990 Nov 28-Dec 19");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_day_monthname_day"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1990-11-28");
        $this->assertSame($dates[0][1], "1990-12-19");
    }

    public function testParseYearMonthnameHyphenMonthname(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2003 May-Aug");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_hyphen_monthname"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2003-5-01");
        $this->assertSame($dates[0][1], "2003-8-31");
    }

    public function testParseYearMonthnameSlashMonthname(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2003 May/Aug");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_hyphen_monthname"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2003-5-01");
        $this->assertSame($dates[0][1], "2003-8-31");
    }

    /**
     * @dataProvider provideYearMonthnameDay
     */
    public function testParseYearMonthnameDay($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate($dateText);

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_day"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2013-7-13");
        $this->assertSame($dates[0][1], "2013-7-13");
    }
    public function provideYearMonthnameDay()
    {
        return [
            ["2013 July 13"],
            ["2013 July 13th"],
            ["2013 July, 13"],
        ];
    }

    public function testParseYearMonthnameMonthnameYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2013 July-December 2014");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_monthname_year"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2013-7-01");
        $this->assertSame($dates[0][1], "2014-12-31");
    }

    /**
     * @dataProvider provideYearMonthnameYearMonthname
     */
    public function testParseYearMonthnameYearMonthname(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2013 July-2014 December");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_year_monthname"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2013-7-01");
        $this->assertSame($dates[0][1], "2014-12-31");
    }

    public function provideYearMonthnameYearMonthname()
    {
        return [
            ["2013 July-2014 December"],
        ["2013 July - 2014 December"],
        ];
    }

    public function testParseDayMonthnameYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("21 July 2013");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_day_monthname_year"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2013-7-21");
        $this->assertSame($dates[0][1], "2013-7-21");
    }

    public function testParseMonthnameDayToYearMonthnameDay(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1951 Mar 6-1975 Apr 24");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_day_to_year_monthname_day"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1951-3-6");
        $this->assertSame($dates[0][1], "1975-4-24");
    }

    /**
     * @dataProvider provideYearCommaMonthnameDayToYearCommaMonthnameDay
     */
    public function testParseYearCommaMonthnameDayToYearCommaMonthnameDay(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2001, Jan 3-2002, Feb 25");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_comma_monthname_day_to_year_comma_monthname_day"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2001-1-3");
        $this->assertSame($dates[0][1], "2002-2-25");
    }

    public function provideYearCommaMonthnameDayToYearCommaMonthnameDay()
    {
        return [
            ["2001, Jan 3-2002, Feb 25"],
        ["2001 Jan, 3-2002 Feb, 25"],
        ];
    }

    public function testParseYearYearMonthname(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1969-1971 June");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_year_monthname"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1969-01-01");
        $this->assertSame($dates[0][1], "1971-6-30");
    }

    public function testParseYearToYearMonthname(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1969-1971 June");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_year_monthname"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1969-01-01");
        $this->assertSame($dates[0][1], "1971-6-30");
    }

    public function testParseYearMonthnameToYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1969 June-1971");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_to_year"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1969-6-01");
        $this->assertSame($dates[0][1], "1971-12-31");
    }

    public function testParseYearMonthnameYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1969-June 1971");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_year"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "1969-01-01");
        $this->assertSame($dates[0][1], "1971-6-30");
    }

    public function testParseYearMonthnameYearMonthnameDay(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2003 July-2004 August 23");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_year_monthname_day"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2003-7-01");
        $this->assertSame($dates[0][1], "2004-8-23");
    }

    public function testParseYearMonthnameDayToYearMonthname(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2003 July 3-2004 August");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_day_to_year_monthname"]);

        $this->assertSame(count($dates), 1);
        $this->assertSame($dates[0][0], "2003-7-3");
        $this->assertSame($dates[0][1], "2004-8-31");
    }

    /**
     * @dataProvider provideMultiYearYearToYear
     */
    public function testParseMultiYearYearToYear($dateText): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate($dateText);

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_single_and_range"]);

        $this->assertSame(count($dates), 2);
        $this->assertSame($dates[0][0], "2003-01-01");
        $this->assertSame($dates[0][1], "2003-12-31");
        $this->assertSame($dates[1][0], "2007-01-01");
        $this->assertSame($dates[1][1], "2008-12-31");
    }

    public function provideMultiYearYearToYear()
    {
        return [
            ["2003 2007-2008"],
            ["2003,2007-2008"],
        ];
    }

    public function testParseMultiYearToYearYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("2007-2008 2010");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_range_and_single"]);

        $this->assertSame(count($dates), 2);
        $this->assertSame($dates[0][0], "2007-01-01");
        $this->assertSame($dates[0][1], "2008-12-31");
        $this->assertSame($dates[1][0], "2010-01-01");
        $this->assertSame($dates[1][1], "2010-12-31");
    }

    public function testParseYearMonthnameDayMonthDayMonthname(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1989 Oct 25; Nov 22 1989");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_year_monthname_day_month_day_monthname"]);

        $this->assertSame(count($dates), 2);
        $this->assertSame($dates[0][0], "1989-10-25");
        $this->assertSame($dates[0][1], "1989-10-25");
        $this->assertSame($dates[1][0], "1989-11-22");
        $this->assertSame($dates[1][1], "1989-11-22");
    }

    public function testParseMultiYearYearYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1923 1948 1949");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_year_year_year"]);

        $this->assertSame(count($dates), 3);
        $this->assertSame($dates[0][0], "1923-01-01");
        $this->assertSame($dates[0][1], "1923-12-31");
        $this->assertSame($dates[1][0], "1948-01-01");
        $this->assertSame($dates[1][1], "1948-12-31");
        $this->assertSame($dates[2][0], "1949-01-01");
        $this->assertSame($dates[2][1], "1949-12-31");
    }

    public function testParseMultiYearYearYearYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1923, 1948, 1949, 1952");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_year_year_year_year"]);

        $this->assertSame(count($dates), 4);
        $this->assertSame($dates[0][0], "1923-01-01");
        $this->assertSame($dates[0][1], "1923-12-31");
        $this->assertSame($dates[1][0], "1948-01-01");
        $this->assertSame($dates[1][1], "1948-12-31");
        $this->assertSame($dates[2][0], "1949-01-01");
        $this->assertSame($dates[2][1], "1949-12-31");
        $this->assertSame($dates[3][0], "1952-01-01");
        $this->assertSame($dates[3][1], "1952-12-31");
    }

    public function testParseMultiYearYearYearYearYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1923, 1948, 1949, 1952, 1953");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_year_year_year_year_year"]);

        $this->assertSame(count($dates), 5);
        $this->assertSame($dates[0][0], "1923-01-01");
        $this->assertSame($dates[0][1], "1923-12-31");
        $this->assertSame($dates[1][0], "1948-01-01");
        $this->assertSame($dates[1][1], "1948-12-31");
        $this->assertSame($dates[2][0], "1949-01-01");
        $this->assertSame($dates[2][1], "1949-12-31");
        $this->assertSame($dates[3][0], "1952-01-01");
        $this->assertSame($dates[3][1], "1952-12-31");
        $this->assertSame($dates[4][0], "1953-01-01");
        $this->assertSame($dates[4][1], "1953-12-31");
    }

    public function testParseMultiYearYearYearYearYearYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1923, 1948, 1949, 1952, 1953, 1963");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_year_year_year_year_year_year"]);

        $this->assertSame(count($dates), 6);
        $this->assertSame($dates[0][0], "1923-01-01");
        $this->assertSame($dates[0][1], "1923-12-31");
        $this->assertSame($dates[1][0], "1948-01-01");
        $this->assertSame($dates[1][1], "1948-12-31");
        $this->assertSame($dates[2][0], "1949-01-01");
        $this->assertSame($dates[2][1], "1949-12-31");
        $this->assertSame($dates[3][0], "1952-01-01");
        $this->assertSame($dates[3][1], "1952-12-31");
        $this->assertSame($dates[4][0], "1953-01-01");
        $this->assertSame($dates[4][1], "1953-12-31");
        $this->assertSame($dates[5][0], "1963-01-01");
        $this->assertSame($dates[5][1], "1963-12-31");
    }

    public function testParseMultiYearYearRangeYearRangeYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1945 1951 1953-1958 1960 1963-1964 1966");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_year_year_range_year_range_year"]);

        $this->assertSame(count($dates), 6);
        $this->assertSame($dates[0][0], "1945-01-01");
        $this->assertSame($dates[0][1], "1945-12-31");
        $this->assertSame($dates[1][0], "1951-01-01");
        $this->assertSame($dates[1][1], "1951-12-31");
        $this->assertSame($dates[2][0], "1953-01-01");
        $this->assertSame($dates[2][1], "1958-12-31");
        $this->assertSame($dates[3][0], "1960-01-01");
        $this->assertSame($dates[3][1], "1960-12-31");
        $this->assertSame($dates[4][0], "1963-01-01");
        $this->assertSame($dates[4][1], "1964-12-31");
        $this->assertSame($dates[5][0], "1966-01-01");
        $this->assertSame($dates[5][1], "1966-12-31");
    }

    public function testParseMultiYearRangeYear(): void
    {
        $parser = new MigrationDateParser();
        $dates = $parser->parseDate("1951,1953-1958,1960");

        $this->assertSame($parser->getParseMethodsUsed(), ["parse_multi_year_range_year"]);

        $this->assertSame(count($dates), 3);
        $this->assertSame($dates[0][0], "1951-01-01");
        $this->assertSame($dates[0][1], "1951-12-31");
        $this->assertSame($dates[1][0], "1953-01-01");
        $this->assertSame($dates[1][1], "1958-12-31");
        $this->assertSame($dates[2][0], "1960-01-01");
        $this->assertSame($dates[2][1], "1960-12-31");
    }
}
