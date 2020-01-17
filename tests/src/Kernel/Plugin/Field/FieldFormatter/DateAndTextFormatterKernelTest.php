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

namespace Drupal\Tests\apigee_m10n\Kernel\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Test the `apigee_date_and_text_formatter` field formatter.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class DateAndTextFormatterKernelTest extends MonetizationKernelTestBase {

  /**
   * The formatter manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The field's display settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Extra modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'field',
    'text',
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installEntitySchema('entity_test');

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = mb_strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'timestamp',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

    $this->display = $this->container->get('entity_display.repository')
      ->getViewDisplay($this->entityType, $this->bundle, 'default')
      ->setComponent($this->fieldName, [
        'type' => 'boolean',
        'settings' => [],
      ]);
    $this->display->save();

    $this->settings = [
      'date_format' => 'custom',
      'custom_date_format' => 'm/d/Y',
      'timezone' => '',
      'text' => 'Lorem ipsum dolor sit amet, @date consectetur adipiscing elit.',
    ];
  }

  /**
   * Test formatter display.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testView() {
    $value = time();

    // Grab the formatted date from the TimestampFormatter and add it to the
    // text.
    $expected_date = \Drupal::service('date.formatter')
      ->format($value, $this->settings['date_format'], $this->settings['custom_date_format']);
    $expected = new FormattableMarkup($this->settings['text'], [
      '@date' => $expected_date,
    ]);

    $component = $this->display->getComponent($this->fieldName);
    $component['type'] = 'apigee_date_and_text_formatter';
    $component['settings'] = $this->settings;
    $this->display->setComponent($this->fieldName, $component);

    $entity = EntityTest::create([]);
    $entity->{$this->fieldName}->value = $value;

    $content = $this->display->build($entity);
    $this->render($content);
    $this->assertRaw($expected);
  }

}
