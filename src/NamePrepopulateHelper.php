<?php

namespace Drupal\name_prepopulate;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
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
    $field = $this->getField();

    if ($field->isEmpty()) {
      return '';
    }

    $value = ($field->get(0)->getValue())['value'];
    if ($value instanceof \Drupal\Component\Render\HtmlEscapedText) {
        return $value->__toString();
    } else {
        return $value;
    }
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
