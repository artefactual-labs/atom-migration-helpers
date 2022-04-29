<?php

function getStatueCitationId()
{
  $sql = "SELECT id from term_i18n WHERE name = ?";

  $id = QubitPdo::fetchColumn($sql, ["Freedom of Information and Protection of Privacy Act"]);

  if ($id === false)
  {
    print "Run this after create_container_note_type_and_premis_statute.php\n";
    exit;
  }

  return $id;
}

function getDisseminateActId()
{
  $sql = "SELECT id from term_i18n WHERE name=? AND culture='en'";

  $id = QubitPdo::fetchColumn($sql, ["Disseminate"]);

  if ($id === false)
  {
    print "Run this after create_container_note_type_and_premis_statute.php\n";
    exit;
  }

  return $id;
}

function getUnderCopyrightId()
{
  $sql = "SELECT id from term_i18n WHERE name=? AND culture='en'";

  $id = QubitPdo::fetchColumn($sql, ["Under copyright"]);

  if ($id === false)
  {
    print "Mssing term\n";
    exit;
  }

  return $id;
}

function addRights($objectId, $copyrightStatusId, $citationId, $actId, $restriction, $basisId = null)
{
  if (empty($basisId))
  {
    $basisId = QubitTerm::RIGHT_BASIS_POLICY_ID;
  }

  $rights = new QubitRights;
  $rights->objectId = $objectId;
  $rights->basisId = $basisId;
  $rights->copyrightStatusId = $copyrightStatusId;

  if ($basisId == QubitTerm::RIGHT_BASIS_STATUTE_ID)
  {
    $rights->statuteCitationId = $citationId;
  }

  $rights->save();

  $granted = new QubitGrantedRight;
  $granted->rightsId = $rights->id;
  $granted->actId = $actId;
  $granted->restriction = $restriction;
  $granted->save();

  $rel = new QubitRelation;
  $rel->subjectId = $objectId;
  $rel->objectId = $rights->id;
  $rel->typeId = QubitTerm::RIGHT_ID;
  $rel->save();
}
