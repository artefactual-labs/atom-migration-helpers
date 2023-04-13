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
