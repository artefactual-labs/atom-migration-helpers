<?php

function getParent($id)
{
    $sql = "SELECT parent_id FROM information_object WHERE id=?";

    return QubitPdo::fetchColumn($sql, [$id]);
}

function getAncestors($id, $maxDepth = false)
{
    $ancestors = [];

    $depth = 0;
    $currentId = $id;

    while (($parentId = getParent($currentId)) && (empty($maxDepth) || $depth < $maxDepth)) {
        $ancestors[] = $parentId;
        $currentId = $parentId;

        $depth++;
    }

    // Indicate that maximum depth was reached
    if (!empty($maxDepth) && $depth == $maxDepth) {
        return false;
    }

    return $ancestors;
}

function parentingIsValidCheck($id, $parentId)
{
    $parentAncestors = getAncestors($parentId);

    if (!in_array($id, $parentAncestors)) {
        return true;
    }

    return false;
}
