# atom-migration-helpers

PHP functions for use in data migration scripts and migration-related utility
scripts.

See PROCESS.md for a guide to the data migration process.


## csv.inc.php

The `CsvReader` class provides an easy way to get data from CSV files for
ad-hoc migration scripts.

Example script reading CSV:

    <?php
    
    require_once(realpath(dirname(__FILE__)) .'/helpers/csv.inc.php');
    
    $csv = new CsvReader("accessions.csv");
    
    while($row = $csv->getRow()) {
        print $csv->getColumn("Title") ."\n";
    }
    
    print $csv->countRows() ."\n";


## dates.inc.php

The `MigrationDateParser` class provides a way to parse a string describing
dates into one or more date ranges.

Example script parsing dates:

    <?php
    
    require_once(realpath(dirname(__FILE__)) .'/helpers/dates.inc.php');
    
    $parser = new MigrationDateParser();
    $dates = $parser->parseDate("2001-2002 November");
    
    print_r($dates);

Example result:

    Array
    (
        [0] => Array
            (
                [0] => 2001-01-01
                [1] => 2002-11-30
            )
    
    )
