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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test the `apigee_organization` field widget.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class ApigeeOrganizationSelectWidgetKernelTest extends MonetizationKernelTestBase {

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
   * The formatter manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatter_manager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $field_manager;

  /**
   * Test product bundle.
   *
   * @var \Drupal\apigee_m10n\Entity\ProductBundleInterface
   */
  protected $product_bundle;

  /**
   * Test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installConfig(['filter', 'node', 'system']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['field']);

    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Test page']);
    $this->createNode(['title' => 'Page test']);

    $developer = $this->createAccount([
      'access content',
      'create page content',
    ]);
    $this->setCurrentUser($developer);
  }

  /**
   * Test widget display.
   */
  public function testView() {
    $field_name = 'field_test';
    $settings = [
      'size' => 30,
      'placeholder' => 'lorem ipsum',
    ];
    $this->createField('node', 'page', $field_name, $field_name);
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($field_name, [
        'type' => 'apigee_organization',
        'settings' => $settings,
      ])
      ->save();

    // Test the node add page for the field widget.
    $response = $this->container
      ->get('http_kernel')
      ->handle(Request::create('node/add/page', 'GET'));
    $this->setRawContent($response->getContent());

    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    $element = $this->cssSelect('[name="' . $field_name . '[0][value]"]');
    $this->assertNotEmpty($element);
    $element = $element[0];
    $attributes = (array) $element;
    $this->assertEquals($settings['size'], $attributes['@attributes']['size']);
    $this->assertEquals($settings['placeholder'], $attributes['@attributes']['placeholder']);
  }

  /**
   * Creates a field on the specified bundle.
   *
   * @param string $entity_type
   *   The type of entity the field will be attached to.
   * @param string $bundle
   *   The bundle name of the entity the field will be attached to.
   * @param string $field_name
   *   The name of the field.
   * @param string $field_label
   *   The label of the field.
   * @param int $cardinality
   *   The cardinality of the field.
   * @param array $settings
   *   The field settings.
   */
  protected function createField($entity_type, $bundle, $field_name, $field_label, $cardinality = 1, $settings = []) {
    // Look for or add the specified field to the requested entity bundle.
    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'type' => 'apigee_organization',
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
