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

use Apigee\Edge\Api\Monetization\Entity\Developer;
use Drupal\apigee_edge\Job\Job;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\apigee_m10n\Functional\MonetizationFunctionalTestBase;

/**
 * Tests the testing framework for testing offline.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class AddCreditProductCheckoutTest extends MonetizationFunctionalTestBase {
  use StoreCreationTrait;

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
   * The commerce store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * The SDK balance controller.
   *
   * @var \Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceController
   */
  protected $balance_controller;

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
    'commerce_cart',
    'commerce_checkout',
    'commerce_product',
    'commerce_payment_test',
    'commerce_store',
    'commerce',
    'user',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function setUp() {
    parent::setUp();
    // Create the developer account.
    // @todo: Restrict this to a what a developers permissions would be.
    $this->developer = $this->createAccount(array_keys(\Drupal::service('user.permissions')->getPermissions()));
    $this->queueOrg();
    $this->drupalLogin($this->developer);

    $this->assertNoClientError();

    $this->store = $this->createStore(NULL, $this->config('system.site')->get('mail'));
    $this->store->save();

    // Enable add credit for the product type.
    $product_type = ProductType::load('default');
    $product_type->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit', 1);
    $product_type->save();

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation->save();

    $this->product = Product::create([
      'title' => $this->randomMachineName(),
      'type' => 'default',
      'stores' => [$this->store->id()],
      'variations' => [$variation],
      'apigee_add_credit_enabled' => 1,
    ]);
    $this->product->save();

    $gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $gateway->save();

    $this->balance_controller = \Drupal::service('apigee_m10n.sdk_controller_factory')
      ->developerBalanceController($this->developer);
  }

  /**
   * Tests the job will update the developer balance.
   *
   * @throws \Exception
   *
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::mail
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::commerceOrderItemCreate
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
    $this->submitForm([], 'Checkout');
    $this->assertCssElementContains('h1.page-title', 'Order information');
    // Submit payment information.
    $this->submitForm([
      'payment_information[add_payment_method][payment_details][security_code]' => '123',
      'payment_information[add_payment_method][billing_information][address][0][address][given_name]' => $this->developer->first_name->value,
      'payment_information[add_payment_method][billing_information][address][0][address][family_name]' => $this->developer->last_name->value,
      'payment_information[add_payment_method][billing_information][address][0][address][address_line1]' => '300 Beale Street',
      'payment_information[add_payment_method][billing_information][address][0][address][locality]' => 'San Francisco',
      'payment_information[add_payment_method][billing_information][address][0][address][administrative_area]' => 'CA',
      'payment_information[add_payment_method][billing_information][address][0][address][postal_code]' => '94105',
    ], 'Continue to review');
    $this->assertCssElementContains('h1.page-title', 'Review');
    $this->assertCssElementContains('.view-commerce-checkout-order-summary', "1 x");
    $this->assertCssElementContains('.view-commerce-checkout-order-summary', $this->product->label());

    // Before finalizing the payment, we have to add a couple of responses to
    // the queue.
    $this->stack
      // We should now have no existing balance .
      ->queueMockResponse(['get_prepaid_balances_empty'])
      // Queue a developer balance response for the top up (POST).
      ->queueMockResponse(['post_developer_balances' => ['amount' => '12.00']])
      // Queue an updated balance response.
      ->queueMockResponse([
        'get_prepaid_balances' => [
          'amount_usd' => '12.00',
          'topups_usd' => '12.00',
          'current_usage_usd' => '0',
        ],
      ]);

    // Finalize the payment.
    $this->submitForm([], 'Pay and complete purchase');

    $this->assertCssElementContains('h1.page-title', 'Complete');
    $this->assertCssElementContains('div.checkout-complete', 'Your order number is 1.');
    $this->assertCssElementContains('div.checkout-complete', 'You can view your order on your account page when logged in.');

    // Load all jobs.
    $query = \Drupal::database()->select('apigee_edge_job', 'j')->fields('j');
    $jobs = $query->execute()->fetchAllAssoc('id');
    static::assertCount(1, $jobs);

    /** @var \Drupal\apigee_edge\Job $job */
    $job = unserialize(reset($jobs)->job);
    static::assertSame(Job::FINISHED, $job->getStatus());

    // The new balance will be re-read so queue the response.
    $this->stack->queueMockResponse([
      'get_developer_balances' => [
        'amount_usd' => '12.00',
        'developer' => new Developer([
          'email' => $this->developer->getEmail(),
          'uuid' => \Drupal::service('uuid')->generate(),
        ]),
      ],
    ]);
    $new_balance = $this->balance_controller->getByCurrency('USD');
    // The new balance should be 12.00.
    static::assertSame(12.00, $new_balance->getAmount());
  }

}
