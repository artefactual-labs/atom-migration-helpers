<?php

function getIoIds()
{
  $ids = [];

  $sql = "SELECT id FROM information_object";

  foreach (QubitPdo::fetchAll($sql, [], array('fetchMode' => PDO::FETCH_ASSOC)) as $result)
  {
    $ids[] = $result['id'];
  }

  return $ids;
}

function getIoChildren($parentId)
{
  $ids = [];

  $sql = "SELECT id FROM information_object WHERE parent_id=?";

  foreach (QubitPdo::fetchAll($sql, [$parentId], array('fetchMode' => PDO::FETCH_ASSOC)) as $result)
  {
    $ids[] = $result['id'];
  }

  return $ids;
}

function getParent($id)
{
  $sql = "SELECT parent_id FROM information_object WHERE id=?";

  return QubitPdo::fetchColumn($sql, [$id]);
}

function getAncestors($id)
{
  $ancestors = [];

  $currentId = $id;
  while ($parentId = getParent($currentId))
  {
    $ancestors[] = $parentId;
    $currentId = $parentId;
  }

  return $ancestors;
}

print "Checking sanity of information object hierarchy...\n";

$ids = getIoIds();
$ios = [];
$children = [];
$count = 0;

foreach ($ids as $id)
{
  $count++;

  if ($id != 1)
  {
    $ancestors = getAncestors($id);

    $topLevelId = array_pop($ancestors);

    print 'ID:'. $id ."/T: ". $topLevelId ."/NPA: ". count($ancestors) ." (". $count ."/". count($ids) .")\n";

    if ($topLevelId != 1)
    {
      print 'BAD TOP ID:'. $topLevelId ."\n";
      exit;
    }
  }
}

print "Done. Hierarchy is OK.\n";
