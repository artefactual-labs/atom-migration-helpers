<?php

function getContainerNoteId()
{
    $sql = "SELECT ti.id FROM term_i18n ti JOIN term t ON t.id = ti.id WHERE ti.name = ? AND t.taxonomy_id = ?";

    $id = QubitPdo::fetchColumn($sql, ["Container", 51]);

    if ($id === false) {
        print "Run this after create_container_note_type_and_premis_statute.php\n";
        exit;
    }

    return $id;
}

function getInformationObjectsWithContainerNotes($levelOfDescriptionIds, $containerNoteTypeId, $exclude = false)
{
    $ids = [];

    $sql = 'SELECT i.id FRON note n
    INNER JOIN information_object i ON n.object_id=i.id ';

    if ($exclude) {
        $sql .= 'WHERE i.level_of_description_id NOT IN ';
    } else {
        $sql .= 'WHERE i.level_of_description_id IN ';
    }

    $sql .= '('. implode(',', $levelOfDescriptionIds) . ') AND type_id=?';

    foreach (QubitPdo::fetchAll($sql, [$containerNoteTypeId], array('fetchMode' => PDO::FETCH_ASSOC)) as $result) {
        $noteIds[] = $result['id'];
    }

    return $ids;
}

function getContainerNotesForObject($id, $containerNoteTypeId)
{
    $noteIds = [];

    $sql = 'SELECT id FROM note WHERE object_id=? AND type_id=?';

    foreach (QubitPdo::fetchAll($sql, [$id, $containerNoteTypeId], array('fetchMode' => PDO::FETCH_ASSOC)) as $result) {
        $noteIds[] = $result['id'];
    }

    return $noteIds;
}

function addContainerNote($objectId, $content, $containerNoteId)
{
    $note = new QubitNote();
    $note->typeId = $containerNoteId;
    $note->objectId = $objectId;
    $note->content = $content;
    $note->save();
}

function deleteContainerNotesForObject($id, $containerNoteTypeId)
{
    foreach (getContainerNotesForObject($id, $containerNoteTypeId) as $id) {
        $note = QubitNote::getById($id);
        $note->delete();
    }
}
