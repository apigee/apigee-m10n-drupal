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

namespace Drupal\Tests\apigee_m10n_add_credit\Functional;

use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Url;

/**
 * Tests permissions for add credit products.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class AddCreditPermissionsTest extends AddCreditFunctionalTestBase {

  /**
   * A developer user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * A test product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable add credit for the product type.
    $product_type = ProductType::load('default');
    $product_type->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit', 1);
    $product_type->save();

    // Create a product.
    $variation = $this->createCommerceProductVariation();
    $this->product = $this->createCommerceProduct($this->createCommerceStore(), $variation);

    $this->createCommercePaymentGateway();
  }

  /**
   * Tests permissions for add credit products.
   *
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::commerceProductAccess
   */
  public function testPermissionsForAddCreditProducts() {
    $path = $this->product->toUrl()->toString();

    // Create and sign in a user with no add credit permissions.
    $this->developer = $this->signIn();

    // User should see access denied on an add credit product.
    $this->drupalGet($path);
    $this->assertSession()->responseContains('Access denied');

    $this->developer = $this->signIn(['add credit to own developer prepaid balance']);
    $this->drupalGet($path);
    $this->assertSession()->responseNotContains('Access denied');
  }

  /**
   * Tests permissions and add credit button on prepaid balance page.
   *
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::commerceProductAccess
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::apigeeM10nPrepaidBalanceListAlter
   */
  public function testAddCreditButtonPermissionsOnPrepaidBalancePage() {
    // Configure an add credit product for USD.
    $this->setAddCreditProductForCurrencyId($this->product, 'usd');

    // Create and sign in a user with no add credit permissions.
    $this->developer = $this->signIn(['view mint prepaid reports']);
    $this->queueOrg();
    $this->queueMockResponses(['get-prepaid-balances']);
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->elementNotExists('css', '.add-credit.dropbutton');

    // Create and sign in a user with add credit permissions.
    $this->developer = $this->signIn(['view mint prepaid reports', 'add credit to own developer prepaid balance']);
    $this->queueMockResponses(['get-prepaid-balances']);
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->elementExists('css', '.add-credit.dropbutton');
  }

}
