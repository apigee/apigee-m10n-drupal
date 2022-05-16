<?php

/*
 * Copyright 2019 Google Inc.
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

namespace Drupal\Tests\apigee_m10n\Kernel\Plugin\Field\FieldWidget;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Base test class for field widget tests.
 */
abstract class BaseWidgetKernelTest extends MonetizationKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'system', 'field', 'text', 'filter', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installConfig(['filter', 'node', 'system']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['field']);

    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Test page', 'uid' => 0]);
    $this->createNode(['title' => 'Page test', 'uid' => 0]);

    $developer = $this->createAccount([
      'access content',
      'create page content',
    ]);
    $this->setCurrentUser($developer);
  }

  /**
   * Creates a field of the given type on the specified entity type/bundle.
   *
   * @param string $entity_type
   *   The type of entity the field will be attached to.
   * @param string $bundle
   *   The bundle name of the entity the field will be attached to.
   * @param string $field_name
   *   The name of the field.
   * @param string $field_type
   *   The field type.
   * @param string $field_label
   *   The label of the field.
   * @param int $cardinality
   *   The cardinality of the field.
   * @param array $settings
   *   The field settings.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createField($entity_type, $bundle, $field_name, $field_type, $field_label, $cardinality = 1, array $settings = []) {
    // Look for or add the specified field to the requested entity bundle.
    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'type' => $field_type,
        'entity_type' => $entity_type,
        'cardinality' => $cardinality,
        'settings' => $settings,
      ])->save();
    }
    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field_label,
        'settings' => $settings,
      ])->save();
    }
  }

}
