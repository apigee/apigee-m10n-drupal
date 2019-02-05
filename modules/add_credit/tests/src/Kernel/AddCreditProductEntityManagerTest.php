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

namespace Drupal\Tests\apigee_m10n_add_credit\Kernel;

use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\TopUpAmountItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the AddCreditProductEntityManager service.
 *
 * @package Drupal\Tests\apigee_m10n_add_credit\Kernel
 *
 * @coversDefaultClass \Drupal\apigee_m10n_add_credit\AddCreditProductEntityManager
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_kernel
 */
class AddCreditProductEntityManagerTest extends MonetizationAddCreditKernelTestBase {

  /**
   * The add credit product entity manager.
   *
   * @var \Drupal\apigee_m10n_add_credit\AddCreditProductEntityManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->manager = \Drupal::service('apigee_m10n_add_credit.product_entity_manager');
  }

  /**
   * Tests AddCreditProductEntityManagerInterface::getApigeeTopUpAmountFieldName.
   *
   * @covers ::getApigeeTopUpAmountFieldName
   */
  public function testGetApigeeTopUpAmountFieldName() {
    // Create a default product variation.
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'title' => $this->randomString(),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);

    $variation->save();

    $this->assertNull($this->manager->getApigeeTopUpAmountFieldName($variation));

    // Add an apigee_top_up_amount field to the default variation type.
    FieldStorageConfig::create([
      'field_name' => 'field_amount',
      'entity_type' => 'commerce_product_variation',
      'type' => 'apigee_top_up_amount',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'commerce_product_variation',
      'field_name' => 'field_amount',
      'bundle' => 'default',
      'field_type' => 'apigee_top_up_amount',
    ])->save();

    $this->assertEquals('field_amount', $this->manager->getApigeeTopUpAmountFieldName($variation));
  }

}
