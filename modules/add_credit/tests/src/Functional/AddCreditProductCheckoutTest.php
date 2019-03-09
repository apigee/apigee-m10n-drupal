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

/**
 * Tests the testing framework for testing offline.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class AddCreditProductCheckoutTest extends AddCreditFunctionalTestBase {

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
   *
   * @throws \Exception
   */
  public function setUp() {
    parent::setUp();

    // Create the developer account.
    $this->developer = $this->signIn(['add credit to own developer prepaid balance']);
    $this->assertNoClientError();

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
   * Tests the job will update the developer balance.
   *
   * @throws \Exception
   *
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::mail
   * @covers \Drupal\apigee_m10n_add_credit\EventSubscriber\CommerceOrderTransitionSubscriber::__construct
   * @covers \Drupal\apigee_m10n_add_credit\EventSubscriber\CommerceOrderTransitionSubscriber::getSubscribedEvents
   * @covers \Drupal\apigee_m10n_add_credit\EventSubscriber\CommerceOrderTransitionSubscriber::handleOrderStateChange
   * @covers \Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob::__construct
   * @covers \Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob::executeRequest
   * @covers \Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob::getPrepaidBalance
   * @covers \Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob::getLogger
   * @covers \Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob::getBalanceController
   * @covers \Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob::currencyFormatter
   * @covers \Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob::formatPrice
   * @covers \Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob::isDeveloperAdjustment
   * @covers \Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob::getMessage
   */
  public function testAddCreditToAccount() {
    // Go to the product page.
    $this->drupalGet('product/1');
    $this->assertCssElementContains('h1.page-title', $this->product->label());
    $this->assertCssElementContains('div.product--variation-field--variation_price__1', '$12.00');

    // Add the product to cart.
    $this->submitForm([], 'Add to cart', 'commerce-order-item-add-to-cart-form-commerce-product-1');
    $this->assertCssElementContains('h1.page-title', $this->product->label());
    $this->assertCssElementContains('div.messages--status', $this->product->label() . ' added to your cart');

    // Go to the cart.
    $this->clickLink('your cart');
    $this->assertCssElementContains('h1.page-title', 'Shopping cart');
    $this->assertCssElementContains('.view-commerce-cart-form td:nth-child(1)', $this->product->label());

    // Proceed to checkout.
    $this->checkout($this->developer, $this->product, '12.00');

  }

  /**
   * Test skip cart feature.
   *
   * @throws \Exception
   */
  public function testSkipCart() {
    // Enable skip cart for the default product type.
    $product_type = ProductType::load('default');
    $product_type->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_skip_cart', 1);
    $product_type->save();

    // Visit a default product.
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Checkout');

    // We should be on the checkout page.
    $this->assertCssElementContains('h1.page-title', 'Order information');
    $this->assertCssElementContains('.view-commerce-checkout-order-summary', $this->product->label());
    $this->assertCssElementContains('.view-commerce-checkout-order-summary', "Total $12.00");
  }

}
