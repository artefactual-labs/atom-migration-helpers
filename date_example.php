<?php

require_once(realpath(dirname(__FILE__)) .'/helpers/dates.inc.php');

$parser = new MigrationDateParser();
$dates = $parser->parseDate('2000-2001');

print_r($dates);
