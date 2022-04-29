<?php

function countCsvDataRows($filePath)
{
  $rowCount = 0;

  $file = new SplFileObject($filePath, 'r');

  if ($file->fgetcsv())
  {
    while ($file->fgetcsv())
    {
      $rowCount++;
    }
  }

  return $rowCount;
}

function getFileHandleOrDie($filePath)
{
  $handle = fopen($filePath, "r");

  if (!$handle)
  {
    print "Could not open ". $filePath ."\n";
    exit(1);
  }

  return $handle;
}

function getCsvHeaderRowOrDie($filePath, $buffer = 100000)
{
  $handle = getFileHandleOrDie($filePath);

  return fgetcsv($handle, $buffer, ",");
}

function getCsvRowsOrDie($filePath, $buffer = 100000)
{
  $handle = getFileHandleOrDie($filePath);  

  $header = fgetcsv($handle, $buffer, ",");

  $rows = [];

  while ($row = fgetcsv($handle, $buffer, ","))
  {
    $rows[] = $row;
  }

  return $rows;
}

function getColumnValueIndexOrDie($row, $column)
{
  $index = array_search($column, $row);

  if (!is_numeric($index))
  {
    print "No '". $column ."' column found.\n";
    exit(1);
  }

  return $index;
}
