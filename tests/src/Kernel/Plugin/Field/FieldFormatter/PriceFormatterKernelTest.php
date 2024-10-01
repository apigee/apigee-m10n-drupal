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

use Drupal\Core\Field\FieldItemList;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Drupal\apigee_m10n\Plugin\Field\FieldFormatter\PriceFormatter;

/**
 * Test the `apigee_price` field formatter.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class PriceFormatterKernelTest extends MonetizationKernelTestBase {

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
   * Test product bundle.
   *
   * @var \Drupal\apigee_m10n\Entity\ProductBundleInterface
   */
  protected $productBundle;

  /**
   * Test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $ratePlan;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->productBundle = $this->createProductBundle();
    $this->ratePlan = $this->createRatePlan($this->productBundle);

    $this->formatterManager = $this->container->get('plugin.manager.field.formatter');
    $this->fieldManager = $this->container->get('entity_field.manager');
  }

  /**
   * Test formatter display.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testView() {
    $value = '2.0000';
    $this->ratePlan->setEarlyTerminationFee($value);
    $settings = [
      'strip_trailing_zeroes' => FALSE,
      'currency_display' => 'symbol',
    ];

    $item_list = $this->ratePlan->get('earlyTerminationFee');
    static::assertInstanceOf(FieldItemList::class, $item_list);
    /** @var \Drupal\Core\Field\BaseFieldDefinition $field_definition */
    $field_definition = $this->fieldManager->getBaseFieldDefinitions('rate_plan')['earlyTerminationFee'];

    /** @var \Drupal\apigee_m10n\Plugin\Field\FieldFormatter\PriceFormatter $instance */
    $instance = $this->formatterManager->createInstance('apigee_price', [
      'field_definition' => $field_definition,
      'settings' => $settings,
      'label' => TRUE,
      'view_mode' => 'default',
      'third_party_settings' => [],
    ]);
    static::assertInstanceOf(PriceFormatter::class, $instance);

    // Render the field item.
    $build = $instance->view($item_list);

    static::assertSame((string) $field_definition->getLabel(), (string) $build['#title']);
    static::assertTrue($build['#label_display']);
    static::assertSame('$2.00', (string) $build[0]['#markup']);

    $instance->setSetting('strip_trailing_zeroes', TRUE);
    $build = $instance->view($item_list);
    static::assertSame('$2', (string) $build[0]['#markup']);

    $instance->setSetting('currency_display', 'code');
    $build = $instance->view($item_list);
    static::assertSame('USD2', (string) $build[0]['#markup']);

    $instance->setSetting('currency_display', 'none');
    $build = $instance->view($item_list);
    static::assertSame('2', (string) $build[0]['#markup']);
  }

}
