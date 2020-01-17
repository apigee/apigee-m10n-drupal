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

use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n\Plugin\Field\FieldFormatter\DatestampFormatter;
use Drupal\Core\Field\FieldItemList;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Test the `apigee_datestamp` field formatter.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class DatestampFormatterKernelTest extends MonetizationKernelTestBase {

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
   * Test purchased rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\PurchasedPlanInterface
   */
  protected $purchasedPlan;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
    ]);

    // Do not use user 1.
    $this->createAccount();
    $developer = $this->createAccount(MonetizationInterface::DEFAULT_AUTHENTICATED_PERMISSIONS);

    $this->productBundle = $this->createProductBundle();
    $this->ratePlan = $this->createRatePlan($this->productBundle);
    $this->purchasedPlan = $this->createPurchasedPlan($developer, $this->ratePlan);

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
    $date_format = 'custom';
    $custom_date_format = 'm/d/Y';
    $item_list = $this->purchasedPlan->get('startDate');
    static::assertInstanceOf(FieldItemList::class, $item_list);
    /** @var \Drupal\apigee_m10n\Plugin\Field\FieldFormatter\DatestampFormatter $instance */
    $instance = $this->formatterManager->createInstance('apigee_datestamp', [
      'field_definition' => $this->fieldManager->getBaseFieldDefinitions('rate_plan')['startDate'],
      'settings' => [
        'date_format' => $date_format,
        'custom_date_format' => $custom_date_format,
        'timezone' => '',
      ],
      'label' => TRUE,
      'view_mode' => 'default',
      'third_party_settings' => [],
    ]);
    static::assertInstanceOf(DatestampFormatter::class, $instance);

    /* @var \DateTimeImmutable $value */
    $value = $this->purchasedPlan->getStartDate();
    $expected = \Drupal::service('date.formatter')
      ->format($value->getTimestamp(), $date_format, $custom_date_format);

    // Render the field item.
    $build = $instance->view($item_list);

    static::assertSame('Start Date', (string) $build['#title']);
    static::assertTrue($build['#label_display']);
    static::assertSame($expected, (string) $build[0]['#markup']);
  }

}
