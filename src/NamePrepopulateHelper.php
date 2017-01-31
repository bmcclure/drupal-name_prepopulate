<?php

namespace Drupal\name_prepopulate;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\name\NameFormatParser;
use Drupal\user\Entity\User;

class NamePrepopulateHelper {
  /** @var FieldableEntityInterface */
  var $entity;

  /** @var string */
  var $fieldName;

  public function __construct(FieldableEntityInterface $entity, $fieldName = 'field_name') {
    $this->entity = $entity;
    $this->fieldName = $fieldName;
  }

  public function fillTextField(FieldableEntityInterface $entity, $fieldName = 'name') {
    if (!$entity->hasField($fieldName)) {
      return;
    }

    $nameString = $this->getNameString();

    if (is_null($nameString)) {
      return;
    }

    $entity->set($fieldName, [0 => ['value' => $nameString]]);
  }

  public function prepopulateFromAccount() {
    $field = $this->getField();

    if (is_null($field || !$field->isEmpty())) {
      return;
    }

    $accountField = $this->getAccountField();

    if (is_null($accountField) || $accountField->isEmpty()) {
      return;
    }

    $field->setValue($accountField->getValue());
  }

  protected function getNameString() {
    /** @var \Drupal\Core\Field\FieldConfigInterface $fieldConfig; */
    $fieldConfig = FieldConfig::loadByName($this->entity->getEntityTypeId(), $this->entity->bundle(), $this->fieldName);

    /** @var \Drupal\name\NameFormatInterface $format */
    $format = \Drupal::entityTypeManager()->getStorage('name_format')->load($fieldConfig->getSetting('override_format'));

    $field = $this->getField();

    if ($field->isEmpty()) {
      return '';
    }

    return NameFormatParser::parse($field->get(0)->getValue(), $format->get('pattern'), ['object' => $this->entity, 'type' => $this->entity->getEntityTypeId()]);
  }

  protected function getField() {
    if (!$this->entity->hasField($this->fieldName)) {
      return NULL;
    }

    return $this->entity->get($this->fieldName);
  }

  protected function getAccountField() {
    $account = \Drupal::currentUser();

    if (!$account->isAuthenticated()) {
      return NULL;
    }

    $field_name = \Drupal::config('name.settings')->get('user_preferred');

    /**
     * @var \Drupal\Core\Field\FieldConfigInterface $field;
     */
    $field = FieldConfig::loadByName('user', 'user', $field_name);

    if (!$field) {
      return NULL;
    }

    $user = User::load($account->id());

    if (!$user->hasField($field->getName())) {
      return NULL;
    }

    return $user->get($field->getName());
  }
}
