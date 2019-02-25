<?php

/*
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by the
 * Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public
 * License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

namespace Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType;

use Apigee\Edge\Api\Monetization\Entity\LegalEntity;
use Apigee\Edge\Api\Monetization\Entity\LegalEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Defines the 'apigee_legal_entity' entity field type.
 *
 * @FieldType(
 *   id = "apigee_legal_entity",
 *   label = @Translation("Apigee legal entity"),
 *   description = @Translation("Apigee legal entity"),
 *   default_formatter = "apigee_legal_entity",
 *   default_widget = "apigee_legal_entity",
 *   list_class = "\Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\LegalEntityFieldItemList",
 * )
 */
class LegalEntityItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['target_id'] = DataReferenceTargetDefinition::create('string')
      ->setLabel(t('Entity ID'))
      ->setSetting('unsigned', TRUE)
      ->setRequired(TRUE);

    $properties['target_type'] = DataReferenceTargetDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Target Entity Type'))
      ->setRequired(TRUE);

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel(t('Entity'))
      ->setDescription(new TranslatableMarkup('The referenced entity'))
      ->setComputed(TRUE)
      ->setReadOnly(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [
      'target_id' => [
        'description' => 'The ID of the target entity.',
        'type' => 'varchar_ascii',
        'length' => 255,
      ],
      'target_type' => [
        'description' => 'The Entity Type ID of the target entity.',
        'type' => 'varchar_ascii',
        'length' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      ],
    ];

    $schema = [
      'columns' => $columns,
      'indexes' => [
        'target_id' => ['target_id', 'target_type'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'target_id';
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return $this->getSettableValues($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    $flatten_options = OptGroup::flattenOptions($this->getSettableOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $entity_bundle_info = \Drupal::service('entity_type.bundle.info');
    $options = [];
    foreach ($this->getPossibleTargets($account) as $entity_type => $entities) {
      $bundles = $entity_bundle_info->getBundleInfo($entity_type);
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      foreach ($entities as $entity) {
        $options[(string) $bundles[$entity_type]['label']]["$entity_type:{$entity->id()}"] = $entity->label();
      }
    }

    return count($options) == 1 ? reset($options) : $options;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Avoid loading the entity by first checking the 'target_id'.
    if ($this->target_id !== NULL && $this->target_type !== NULL) {
      return FALSE;
    }
    if ($this->entity && $this->entity instanceof EntityInterface) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if (!$this->isEmpty() && $this->target_id === NULL) {
      $this->target_id = $this->entity->id();
      $this->target_type = $this->entity->getEntityTypeId();
    }
  }

  /**
   * Helper to get the target from the field.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The target entity.
   *
   * @throws \Exception
   */
  public function getTarget() {
    if ($this->isEmpty()) {
      return NULL;
    }

    if ($targets = \Drupal::entityTypeManager()->getStorage($this->target_type)->load($this->target_id)) {
      return reset($targets);
    }

    return NULL;
  }

  /**
   * Returns an array of possible targets.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return array
   *   An array of developer and/or teams entities keyed by entity type.
   */
  protected function getPossibleTargets(AccountInterface $account) {
    return \Drupal::service('apigee_m10n_add_credit.monetization')->getLegalEntities($account);
  }

}
