<?php

final class ManiphestEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'maniphest.task';

  public function getEngineName() {
    return pht('Maniphest Tasks');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorManiphestApplication';
  }

  protected function newEditableObject() {
    return ManiphestTask::initializeNewTask($this->getViewer());
  }

  protected function newObjectQuery() {
    return id(new ManiphestTaskQuery());
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New Task');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit %s %s', $object->getMonogram(), $object->getTitle());
  }

  protected function getObjectEditShortText($object) {
    return $object->getMonogram();
  }

  protected function getObjectCreateShortText() {
    return pht('Create Task');
  }

  protected function getCommentViewHeaderText($object) {
    $is_serious = PhabricatorEnv::getEnvConfig('phabricator.serious-business');
    if (!$is_serious) {
      return pht('Weigh In');
    }

    return parent::getCommentViewHeaderText($object);
  }

  protected function getObjectViewURI($object) {
    return '/'.$object->getMonogram();
  }

  protected function buildCustomEditFields($object) {
    // See T4819.
    $status_map = ManiphestTaskStatus::getTaskStatusMap();
    $dup_status = ManiphestTaskStatus::getDuplicateStatus();
    if ($object->getStatus() != $dup_status) {
      unset($status_map[$dup_status]);
    }

    $owner_phid = $object->getOwnerPHID();
    if ($owner_phid) {
      $owner_value = array($owner_phid);
    } else {
      $owner_value = array();
    }

    $priority_map = ManiphestTaskPriority::getTaskPriorityMap();

    // TODO: Restore these or toss them:
    // - Require a single owner.
    // - Default owner to viewer.
    // - Don't show "change status" for closed tasks.
    // - Don't show "change owner" for closed tasks.
    // - Don't let users change a task status to "Duplicate".
    // - When closing an unassigned task, assign the closing user.
    // - Make sure implicit CCs on actions are working reasonably.

    return array(
      id(new PhabricatorTextEditField())
        ->setKey('title')
        ->setLabel(pht('Title'))
        ->setDescription(pht('Name of the task.'))
        ->setTransactionType(ManiphestTransaction::TYPE_TITLE)
        ->setIsRequired(true)
        ->setValue($object->getTitle()),
      id(new PhabricatorSelectEditField())
        ->setKey('status')
        ->setLabel(pht('Status'))
        ->setDescription(pht('Status of the task.'))
        ->setTransactionType(ManiphestTransaction::TYPE_STATUS)
        ->setValue($object->getStatus())
        ->setOptions($status_map),
      id(new PhabricatorUsersEditField())
        ->setKey('assigned')
        ->setAliases(array('assign', 'assignee'))
        ->setLabel(pht('Assigned To'))
        ->setDescription(pht('User who is responsible for the task.'))
        ->setTransactionType(ManiphestTransaction::TYPE_OWNER)
        ->setValue($owner_value),
      id(new PhabricatorSelectEditField())
        ->setKey('priority')
        ->setLabel(pht('Priority'))
        ->setDescription(pht('Priority of the task.'))
        ->setTransactionType(ManiphestTransaction::TYPE_PRIORITY)
        ->setValue($object->getPriority())
        ->setOptions($priority_map),
      id(new PhabricatorRemarkupEditField())
        ->setKey('description')
        ->setLabel(pht('Description'))
        ->setDescription(pht('Task description.'))
        ->setTransactionType(ManiphestTransaction::TYPE_DESCRIPTION)
        ->setValue($object->getDescription()),
    );
  }

  protected function getEditorURI() {
    // TODO: Remove when cutting over.
    return $this->getApplication()->getApplicationURI('editpro/');
  }

}