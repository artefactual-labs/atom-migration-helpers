<?php

function countTableRows($table)
{
    $sql = 'SELECT count(*) as count FROM '. $table;

    return QubitPdo::fetchColumn($sql);
}

function getIoCountsByLod()
{
  $lodCounts = [];

  $sql = 'SELECT count(*) AS count, i.level_of_description_id, ti.name FROM information_object i
            INNER JOIN term_i18n ti on (i.level_of_description_id=ti.id and (ti.culture="en"))
            WHERE i.id != ?
            GROUP BY i.level_of_description_id
            ORDER BY name';

  $results = QubitPdo::fetchAll($sql, [QubitInformationObject::ROOT_ID]);

  foreach ($results as $result) {
    $lodCounts[$result->level_of_description_id] = ['count' => $result->count, 'name' => $result->name];
  }

  return $lodCounts;
}

$tables = [
  'information_object' => 'Information objects (including root)',
  'accession' => 'Accessions',
  'donor' => 'Donors',
  'actor' => 'Actors',
  'physical_object' => 'Physical objects',
  'term' => 'Terns',
  'event' => 'Events',
  'relation' => 'Relations',
  'property' => 'Properties'
];

foreach ($tables as $table => $description)
{
  $count = countTableRows($table);

  print $description .": ". $count ."\n\n";
}

$lodCounts = getIoCountsByLod();

if (count($lodCounts))
{
  print "Information object counts by level of description:\n";

  foreach ($lodCounts as $lodId => $lodCount)
  {
    print '* '. $lodCount['name'] .' (L.O.D. ID '. $lodId .'): '. $lodCount['count'] ."\n";
  }
}
