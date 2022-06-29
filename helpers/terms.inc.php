<?php

require_once(realpath(dirname(__FILE__)) .'/lookup.inc.php');

function addTerm($name, $taxonomyId, $parentId, $sourceNote='', $useForTerms='', $scopeNotes='')
{
  // If term with this name already exists, don't add a new one.
  $criteria = new Criteria;
  $criteria->addJoin(QubitTerm::ID, QubitTermI18n::ID, Criteria::INNER_JOIN);
  $criteria->add(QubitTerm::TAXONOMY_ID, $taxonomyId);
  $criteria->add(QubitTermI18n::NAME, $name);

  if (null === $term = QubitTerm::getOne($criteria))
  {
    // Create term
    $term = new QubitTerm;
    $term->taxonomyId = $taxonomyId;
    $term->parentId = $parentId;
    $term->name = $name;
    $term->save();
  } else {
     print "Term with name ". $name ." already exists with ID ". $term->getId() .". Adding notes and relationships to existing term.\n";
  }

  // Deal with source note
  if (!empty($sourceNote))
  {
    $note = new QubitNote();
    $note->objectId = $term->id;
    $note->typeId = QubitTerm::SOURCE_NOTE_ID;
    $note->content = $sourceNote;
    $note->culture = 'en';
    $note->save();
  }

  // Deal with use fors
  if (!empty($useForTerms))
  {
    $useForTermsArray = explode('|', $useForTerms);
    foreach($useForTermsArray as $useForTerm)
    {
      if (!empty($useForTerm))
      {
        addOtherFormOfNameToTerm($term, $useForTerm);
      }
    }
  }

  // Deal with scope notes
  if (!empty($scopeNotes))
  {
    $scopeNotesArray = explode('|', $scopeNotes);
    foreach($scopeNotesArray as $scopeNote)
    {
      if (!empty($scopeNote))
      {
        $note = new QubitNote();
        $note->objectId = $term->id;
        $note->typeId = QubitTerm::SCOPE_NOTE_ID;
        $note->content = $scopeNote;
        $note->culture = 'en';
        $note->save();
      }
    }
  }

  echo('.');
}

function addPlaceTerm($name, $parentId, $sourceNote='', $useForTerms='', $scopeNotes='')
{
  addTerm($name, QubitTaxonomy::PLACE_ID, $parentId, $sourceNote, $useForTerms, $scopeNotes);
}

function addSubjectTerm($name, $parentId, $sourceNote='', $useForTerms='', $scopeNotes='')
{
  addTerm($name, QubitTaxonomy::SUBJECT_ID, $parentId, $sourceNote, $useForTerms, $scopeNotes);
}

function addOtherFormOfNameToTerm($term, $otherFormOfName)
{
  $usedFor = new QubitOtherName();
  $usedFor->name = $otherFormOfName;
  $usedFor->objectId = $term->id;
  $usedFor->typeId = QubitTerm::ALTERNATIVE_LABEL_ID;
  $usedFor->sourceCulture = 'en';
  
  $term->otherNames[] = $usedFor;
}

function relateTerms($objectTerm, $cachedTerms, $relatedTerms)
{
  foreach($relatedTerms as $relatedTermName)
  {
    try {
      $termName = trim($relatedTermName);
      $relatedTermId = getTermIdFromCachedTermsByName($termName, $cachedTerms);
      if (!empty($relatedTermId))
      {
        $relation = new QubitRelation;
        $relation->objectId = $objectTerm->id;
        $relation->subjectId = $relatedTermId;
        $relation->typeId = QubitTerm::TERM_RELATION_ASSOCIATIVE_ID;
        $relation->sourceCulture = 'en';
        $relation->save();
      }  
    }
    catch (exception $e) {
       print "Error adding related term: ". $e->getMessage() ." (". $relatedTermName .")\n";
    }
  }
}
