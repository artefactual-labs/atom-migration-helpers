<?php

function cacheIoLodIds()
{
  $ios = [];

  $sql = 'SELECT id, level_of_description_id FROM information_object WHERE id != 1';

  foreach (QubitPdo::fetchAll($sql, [], array('fetchMode' => PDO::FETCH_ASSOC)) as $result)
  {
    $id = $result['id'];
    $lod = $result['level_of_description_id'];
    $ios[$id] = $lod;
  }

  return $ios;
}

function getLodIdByName($name)
{
  $criteria = new Criteria();
  $criteria->add(QubitTerm::TAXONOMY_ID, QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID);
  $criteria->addJoin(QubitTerm::ID, QubitTermI18n::ID);
  $criteria->add(QubitTermI18n::CULTURE, 'en');
  $criteria->add(QubitTermI18n::NAME, $name);

  if ($term = QubitTermI18n::getOne($criteria)) {
    return $term->id;
  }
}

function getLodRankById()
{
  $rankByName = [
    'Collection' => 1,
    'Fonds' => 1,
    'Sous-fonds' => 2,
    'Sous-sous-fonds' => 3,
    'Series' => 4,
    'Sub-series' => 5,
    'Subseries' => 5,
    'File' => 6,
    'Item' => 7
  ];

  $levelRank = [];

  foreach ($rankByName as $name => $rank)
  {
    $collectionId = getLodIdByName($name);
    $levelRank[$collectionId] = $rank;
  }

  return $levelRank;
}

function rankForLod($lodId, $levelRank)
{
  return $levelRank[$lodId];
}

function parentLodIsSane($id, $parentId, &$lods, $levelRank)
{
  $idLod = $lods[$id];
  $parentIdLod = $lods[$parentId];

  return $levelRank[$idLod] > $levelRank[$parentId];
}
