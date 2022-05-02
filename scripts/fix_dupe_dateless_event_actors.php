<?php

// This script removes duplicates of dateless events (that should be inherited).
// It should be run using the tools:run Symfony task.

function displayTree(&$tree, $id, $depth)
{
    if (count($tree[$id]['children']) || !empty($tree[$id]['duplicate'])) {
        for ($x = 0; $x <= $depth; $x++) {
            print ' ';
        }
        print $id;

        print ' (';
        if (!empty($tree[$id]['duplicate'])) {
            print "D ". $tree[$id]['duplicate'] .", ";
        }
        print "C ". count($tree[$id]['children']) .")";
        print "\n";

        foreach ($tree[$id]['children'] as $childId) {
            displayTree($tree, $childId, $depth + 1);
        }
    }
}

// Fetch all root information objects
$sql = "SELECT id FROM information_object WHERE parent_id=?";

$statement = QubitFlatfileImport::sqlQuery($sql, [QubitInformationObject::ROOT_ID]);

$dupeEvents = [];
$types = [];

while ($result = $statement->fetch()) {
    $io = QubitInformationObject::getById($result['id']);

    // Get information object's tree and attempt to detect redundant creation relationships
    $sql = "SELECT id, parent_id FROM information_object WHERE lft >= ? AND rgt <= ? ORDER BY lft";

    $statement2 = QubitFlatfileImport::sqlQuery($sql, [$io->lft, $io->rgt]);

    $ioEvents = []; // Key should be type and value should be array of actors
  $tree = [$io->id => ['parentId' => 1, 'children' => []]]; // Create "tree" representation that can be displayed
  $dupeCount = 0;

    while ($result2 = $statement2->fetch()) {
        // Take note of parent and child IDs
        $parentId = $result2['parent_id'];
        $childId = $result2['id'];

        // Add current IO's ID as child of its parent, to tree
        $tree[$parentId]['children'][] = $childId;

        // Make sure current IO has its tree representation initialized
        if (empty($tree[$childId])) {
            $tree[$childId] = ['parentId' => $parentId, 'children' => []];
        }

        // Find events for current IO
        $sql = "SELECT id, type_id, actor_id, start_date, end_date FROM event WHERE object_id=?";

        $statement3 = QubitFlatfileImport::sqlQuery($sql, [$childId]);

        while ($result3 = $statement3->fetch()) {
            $typeId = $result3['type_id'];
            $actorId = $result3['actor_id'];
            $types[$typeId] = true;

            // Only check events with actors associated with them
            if (!empty($actorId)) {
                // If Initialize array used to keep track of actors associated with event type
                if (empty($ioEvents[$typeId])) {
                    $ioEvents[$typeId] = [];
                } elseif (!empty($ioEvents[$typeId][$actorId])) {
                    // Abort if date data is encountered
                    if (!empty($result3['start_date']) || !empty($result3['end_date'])) {
                        print "ERROR: This script is only designed to detect duplicates among simple events without dates.\n";
                        exit(1);
                    }

                    // Add this event to array listing duplicates event that should be deleted and bump duplicate count
                    print "Duplicate ". $typeId ." event with actor ". $actorId ." found for IO ". $childId ." (descendent of ". $io->id ."\n";
                    $dupeEvents[] = $result3['id'];
                    $dupeCount++;

                    // Note duplicate in tree representation
                    $tree[$childId]['duplicate'] = $actorId;
                }

                // Note that actor is assocated with event type
                $ioEvents[$typeId][$actorId] = true;
            }
        }
    }

    if (!empty($tree[$io->id]) && $dupeCount > 0) {
        print "\nTree for ". $io->id ." (". count($tree) ." nodes, ". $dupeCount ." dupes):\n";
        displayTree($tree, $io->id, 0);
    }
}

print "\nUnique types:\n";

foreach (array_keys($types) as $type) {
    print sprintf('* %s', $type) ."\n";
}

print "\nTotal duplicates: ". count($dupeEvents) ."\n";

print "Deleting dupes...\n";

foreach ($dupeEvents as $id) {
    $event = QubitEvent::getById($id);
    $event->delete();
}

print "Done.\n";
