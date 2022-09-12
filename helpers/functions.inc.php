<?php

function getFunctionType($name = 'Function')
{
  $criteria = new Criteria;
  $criteria->addJoin(QubitTerm::ID, QubitTermI18n::ID, Criteria::INNER_JOIN);
  $criteria->add(QubitTerm::TAXONOMY_ID, QubitTaxonomy::FUNCTION_ID);
  $criteria->add(QubitTermI18n::NAME, $name);

  if (null !== $term = QubitTerm::getOne($criteria))
  {
    return $term;
  }

}

function addOrGetFunctionObject($name, $functionType, $parent = null, $identifier = null)
{ 
  // Get function type id
  if (null === $functionTypeTerm = getFunctionType($functionType))
  {
    print "Error: Unable to find function type ". $functionType. ". Skipping function ". $name. ".\n";
    return;
  }

  // Skip if function already exists
  $criteria = new Criteria;
  $criteria->addJoin(QubitFunctionObject::ID, QubitFunctionObjectI18n::ID, Criteria::INNER_JOIN);
  $criteria->add(QubitFunctionObject::TYPE_ID, $functionTypeTerm->id);
  $criteria->add(QubitFunctionObjectI18n::AUTHORIZED_FORM_OF_NAME, $name);

  if (null !== $functionObject = QubitFunctionObject::getOne($criteria))
  {
    return $functionObject;
  }

  // Create and return QubitFunctionObject if it didn't already exist.
  $item = new QubitFunctionObject;
  $item->typeId = $functionTypeTerm->id;
  $item->authorizedFormOfName = $name;
  if (null !== $identifier)
  {
    $item->descriptionIdentifier = $identifier;
  }
  $item->save();

  if (null !== $parent)
  {
    // Add hierarchical relationship to parent (QubitFunctionObject)
    addHierarchicalFunctionRelationship($item, $parent);
  }
  print('.');
  return $item;
}

function addHierarchicalFunctionRelationship($functionObject, $parent)
{
  try {
    $relation = new QubitRelation;
    $relation->objectId = $parent->id;
    $relation->subjectId = $functionObject->id;
    $relation->typeId = QubitTerm::ISDF_HIERARCHICAL_RELATION_ID;
    $relation->sourceCulture = 'en';
    $relation->save();
  }
  catch (exception $e) {
     print "Error: Unable to add hierarchical function relationship: ". $e->getMessage() ."\n";
  }
}
