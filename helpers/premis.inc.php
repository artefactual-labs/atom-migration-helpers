<?php

function getStatueCitationId()
{
    $sql = "SELECT id from term_i18n WHERE name = ?";

    $id = QubitPdo::fetchColumn($sql, ["Freedom of Information and Protection of Privacy Act"]);

    if ($id === false) {
        print "Run this after create_container_note_type_and_premis_statute.php\n";
        exit;
    }

    return $id;
}

function getActId($nameText='Disseminate')
{
  $sql = "SELECT id from term_i18n WHERE name=? AND culture='en'";

  $id = QubitPdo::fetchColumn($sql, [$nameText]);

  if ($id === false)
  {
    print "Act ". $nameText ." does not exist in AtoM database.\n";
    exit;
  }

  return $id;
}

function getDisplayActId()
{
    return getActId('Display');
}

function getDisseminateActId()
{
    return getActId('Disseminate');
}

function getUnderCopyrightId()
{
    $sql = "SELECT id from term_i18n WHERE name=? AND culture='en'";

    $id = QubitPdo::fetchColumn($sql, ["Under copyright"]);

    if ($id === false) {
        print "Mssing term\n";
        exit;
    }

    return $id;
}

function addOrGetRights($objectId, $basisId = null)
{
  // Return existing rights if already linked to object
  $criteria = new Criteria;
  $criteria->add(QubitRelation::SUBJECT_ID, $objectId);
  $criteria->add(QubitRelation::TYPE_ID, QubitTerm::RIGHT_ID);
  if (null !== $relation = QubitRelation::getOne($criteria))
  {
    $rightsId = $relation->objectId;
    $criteria = new Criteria;
    $criteria->add(QubitRights::ID, $rightsId);
    if (null !== $rights = QubitRights::getOne($criteria))
    {
      return $rights;
    }
  }

  if (empty($basisId))
  {
    $basisId = QubitTerm::RIGHT_BASIS_POLICY_ID;
  }

  $rights = new QubitRights;
  $rights->objectId = $objectId;
  $rights->basisId = $basisId;
  $rights->save();

  $rel = new QubitRelation;
  $rel->subjectId = $objectId;
  $rel->objectId = $rights->id;
  $rel->typeId = QubitTerm::RIGHT_ID;
  $rel->save();

  return $rights;
}

function addRightsNotes($rights, $rightsNote, $copyrightNote)
{
  $rights->rightsNote = $rightsNote;
  $rights->copyrightNote = $copyrightNote;
  $rights->save();
}

function addGrantedRight($rights, $actId, $restriction, $endDate, $note)
{
  $granted = new QubitGrantedRight;
  $granted->rightsId = $rights->id;
  $granted->actId = $actId;
  $granted->restriction = $restriction;
  $granted->endDate = $endDate;
  $granted->note = $note;
  $granted->save();

  $rights->grantedRights[] = $granted;
  $rights->save();
}
