<?php

function getTargetId($sourceName, $sourceId, $targetName = 'information_object')
{
    $sql = 'SELECT target_id FROM keymap WHERE source_name=? AND target_name=? AND source_id=?';

    return QubitPdo::fetchColumn($sql, [$sourceName, $targetName, $sourceId]);
}

function cacheTargetIds($targetName = 'information_object')
{
    $cache = [];

    // Get available sources for target type
    $sources = [];

    $sql = 'SELECT DISTINCT source_name FROM keymap WHERE target_name=?';

    $results = QubitPdo::fetchAll($sql, [$targetName]);

    foreach ($results as $result) {
        $sources[] = $result->source_name;
    }

    // Cache data for each source
    foreach ($sources as $source) {
        $cache[$source] = [];

        $sql = 'SELECT source_id, target_id FROM keymap WHERE source_name=? AND target_name=?';

        $results = QubitPdo::fetchAll($sql, [$source, $targetName]);

        foreach ($results as $result) {
            $cache[$source][$result->source_id] = $result->target_id;
        }
    }

    return $cache;
}

function getIoIdUsingIdentifier($identifier)
{
    $sql = 'SELECT id FROM information_object WHERE identifier=?';

    return QubitPdo::fetchColumn($sql, [$identifier]);
}

function getAccessionIdByIdentifier($identifier)
{
    $criteria = new Criteria();
    $criteria->add(QubitAccession::IDENTIFIER, $identifier);

    if (null !== $accession = QubitAccession::getOne($criteria)) {
        return $accession->id;
    }
}

function cacheIdentifierIds()
{
    $ids = [];

    $sql = 'SELECT id, identifier FROM information_object WHERE identifier IS NOT NULL AND identifier != ""';

    foreach (QubitPdo::fetchAll($sql, [], array('fetchMode' => PDO::FETCH_ASSOC)) as $result) {
        $identifier = $result['identifier'];
        $ids[$identifier] = $result['id'];
    }

    return $ids;
}

function cacheAccessionIdentifierIds()
{
    $ids = [];

    $sql = 'SELECT id, identifier FROM accession WHERE identifier IS NOT NULL AND identifier != ""';

    foreach (QubitPdo::fetchAll($sql, [], array('fetchMode' => PDO::FETCH_ASSOC)) as $result) {
        $identifier = $result['identifier'];
        $ids[$identifier] = $result['id'];
    }

    return $ids;
}

function getTermIdUsingName($name)
{
  $sql = 'SELECT id FROM term_i18n WHERE name=? AND culture=?';

  return QubitPdo::fetchColumn($sql, [$name, 'en']);
}

function getTermIdFromTaxonomyUsingName($name, $taxonomyId)
{
  $sql = 'SELECT ti.id FROM term_i18n ti JOIN term t ON t.id = ti.id WHERE ti.name=? AND ti.culture=? AND t.taxonomy_id=?';

  return QubitPdo::fetchColumn($sql, [$name, 'en', $taxonomyId]);
}

function getTermIdFromCachedTermsByName($name, $termCache)
{
  return array_search($name, $termCache);
}

function cacheTerms($taxonomyId)
{
  $terms = [];

  $sql = 'SELECT ti.id, ti.name FROM term_i18n ti JOIN term t ON t.id = ti.id WHERE ti.culture=? AND t.taxonomy_id=?';

  foreach (QubitPdo::fetchAll($sql, ['en', $taxonomyId], array('fetchMode' => PDO::FETCH_ASSOC)) as $result) {
        $name = $result['name'];
        $id = $result['id'];
        $terms[$id] = $name;
    }

    return $terms;
}

function cacheSubjectTerms()
{
    return cacheTerms(QubitTaxonomy::SUBJECT_ID);
}

function cachePlaceTerms()
{
    return cacheTerms(QubitTaxonomy::PLACE_ID);
}
