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

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Defines the 'add_credit_target_entity' entity field type.
 *
 * @FieldType(
 *   id = "add_credit_target_entity",
 *   label = @Translation("Apigee add credit target entity"),
 *   no_ui = TRUE,
 *   description = @Translation("Apigee add credit target entity"),
 *   default_formatter = "add_credit_target_entity",
 *   default_widget = "add_credit_target_entity",
 *   list_class = "\Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\AddCreditTargetEntityFieldItemList",
 * )
 */
class AddCreditTargetItem extends FieldItemBase implements OptionsProviderInterface {

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
    foreach (\Drupal::service('plugin.manager.apigee_add_credit_entity_type')->getEntities($account) as $entity_type => $entities) {
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
    return TRUE;
  }

}
