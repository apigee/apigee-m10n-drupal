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

namespace Drupal\Tests\apigee_m10n_add_credit\Kernel\Plugin\EntityReferenceSelection;

use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\apigee_m10n_add_credit\Plugin\EntityReferenceSelection\AddCreditProductsSelection;
use Drupal\commerce_product\Entity\Product;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Tests the AddCreditProductsSelection plugin.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_kernel
 *
 * @covers \Drupal\apigee_m10n_add_credit\Plugin\EntityReferenceSelection\AddCreditProductsSelection
 */
class AddCreditProductsSelectionTest extends MonetizationKernelTestBase {

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * A test product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * The test selection handler.
   *
   * @var \Drupal\apigee_m10n_add_credit\Plugin\EntityReferenceSelection\AddCreditProductsSelection
   */
  protected $selectionHandler;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Base modules.
    'key',
    'file',
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_client',
    'system',

    // Modules for this test.
    'apigee_m10n_add_credit',
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequence']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('user');
    $this->installConfig([
      'apigee_m10n_add_credit',
      'user',
      'system',
    ]);

    // Create an admin user.
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($this->admin);

    $this->product = Product::create([
      'title' => $this->randomMachineName(),
      'type' => 'default',
      'variations' => [],
    ]);
    $this->product->save();

    $configuration = [
      'target_type' => 'commerce_product',
      'target_bundles' => NULL,
    ];

    $this->selectionHandler = new AddCreditProductsSelection($configuration, NULL, NULL, \Drupal::entityManager(), \Drupal::moduleHandler(), \Drupal::currentUser());
  }

  /**
   * Test referenceable entities for the EntityReferenceSelection plugin.
   */
  public function testReferenceableEntities() {
    // Zero since no add credit enabled products.
    $this->assertCount(0, $this->selectionHandler->getReferenceableEntities());

    // Enable add credit for default product.
    $this->product->set(AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME, 1)->save();
    $this->assertCount(1, $this->selectionHandler->getReferenceableEntities());
  }

}
