<?php

/*
 * Copyright 2021 Google Inc.
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

namespace Drupal\Tests\apigee_m10n_add_credit\FunctionalJavascript\ApigeeX;

use Apigee\Edge\Api\Monetization\Entity\SupportedCurrency;
use Drupal\commerce_number_pattern\Entity\NumberPattern;
use Drupal\commerce_product\Entity\ProductType;

/**
 * Tests custom amount for an apigee add credit product.
 *
 * TODO: These tests take a lof of time to run with the dataProviders. Figure out
 * a way to make this more efficient.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class AddCreditCustomAmountTest extends AddCreditFunctionalJavascriptTestBase {

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp(): void {
    parent::setUp();

    $this->warmApigeexOrganizationCache();
    $this->stack->queueMockResponse(['post-apigeex-billing-type']);
    $this->developer = $this->signInAsAdmin();

    $this->assertNoClientError();

    $this->createStore();
  }

  /**
   * Tests the price range field on a variation.
   */
  public function testPriceRangeField() {
    $this->setupApigeeAddCreditProduct();

    // Enable apigee add credit for default product.
    $this->drupalGet('admin/commerce/config/product-types/default/edit');
    $this->submitForm(['apigee_m10n_enable_add_credit' => 1], 'Save');

    // Disable the price field and enable the price range field.
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit/form-display');
    $page = $this->getSession()->getPage();
    $page->pressButton('Show row weights');
    $page->selectFieldOption('fields[price][region]', 'hidden');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->selectFieldOption('fields[apigee_price_range][region]', 'content');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // Check if price field is disabled and all price range fields are visible.
    $this->drupalGet('product/add/default');
    $this->assertSession()
      ->elementNotExists('css', '[name="variations[form][0][price][0][number]"]');
    $price_range_fields = ['minimum', 'maximum', 'default', 'currency_code'];
    foreach ($price_range_fields as $field_name) {
      $this->assertSession()
        ->elementExists('css', '[name="variations[form][0][apigee_price_range][0][price_range][fields][' . $field_name . ']"]');
    }
  }

  /**
   * Tests validation for the price range field.
   *
   * @param string|null $minimum
   *   The minimum amount.
   * @param string|null $maximum
   *   The maximum amount.
   * @param string|null $default
   *   The default amount.
   * @param string|null $message
   *   The expected message.
   *
   * @throws \Exception
   *
   * @dataProvider providerPriceRange
   */
  public function testPriceRangeFieldValidation(string $minimum = NULL, string $maximum = NULL, string $default = NULL, string $message = NULL) {
    $this->setupApigeeAddCreditProduct();

    // Add a product.
    $this->drupalGet('product/add/default');
    if ($minimum) {
      $this->queueSupportedCurrencyResponse();
    }

    // Validate price range fields.
    $this->submitForm([
      'variations[form][0][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][0][apigee_price_range][0][price_range][fields][minimum]' => $minimum,
      'variations[form][0][apigee_price_range][0][price_range][fields][maximum]' => $maximum,
      'variations[form][0][apigee_price_range][0][price_range][fields][default]' => $default,
    ], 'Create variation');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($message);
  }

  /**
   * Tests the unit price range field validation.
   *
   * @param string|null $minimum
   *   The minimum amount.
   * @param string|null $maximum
   *   The maximum amount.
   * @param string|null $default
   *   The default amount.
   * @param string|null $amount
   *   The amount for the unit price field.
   * @param string|null $message
   *   The expected message.
   *
   * @throws \Exception
   *
   * @dataProvider providerUnitPrice
   */
  public function testUnitPriceValidation(string $minimum = NULL, string $maximum = NULL, string $default = NULL, string $amount = NULL, string $message = NULL) {
    $this->setupApigeeAddCreditProduct();

    // Add a product.
    $this->drupalGet('product/add/default');
    $this->queueSupportedCurrencyResponse();

    $title = $this->randomString(16);
    $this->submitForm([
      'title[0][value]' => $title,
      'variations[form][0][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][0][apigee_price_range][0][price_range][fields][minimum]' => $minimum,
      'variations[form][0][apigee_price_range][0][price_range][fields][maximum]' => $maximum,
      'variations[form][0][apigee_price_range][0][price_range][fields][default]' => $default,
    ], 'Save');

    // Check if default value is set.
    $this->assertSession()
      ->elementAttributeContains('css', '[name="unit_price[0][amount][number]"]', 'value', $default);

    // Check if unit price is properly validated.
    $this->submitForm([
      'unit_price[0][amount][number]' => $amount,
    ], 'Add to cart');
    $this->assertSession()->pageTextContains($message);
  }

  /**
   * Tests adding a custom amount and checking out.
   */
  public function testCustomAmountPriceCheckout() {
    $this->setupApigeeAddCreditProduct();

    // Add a product.
    $this->drupalGet('product/add/default');
    $this->queueSupportedCurrencyResponse();

    $title = $this->randomString(16);
    $this->submitForm([
      'title[0][value]' => $title,
      'variations[form][0][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][0][apigee_price_range][0][price_range][fields][minimum]' => '20',
      'variations[form][0][apigee_price_range][0][price_range][fields][maximum]' => '500',
      'variations[form][0][apigee_price_range][0][price_range][fields][default]' => '40',
    ], 'Save');

    $this->submitForm([
      'unit_price[0][amount][number]' => '50',
    ], 'Add to cart');

    $this->drupalGet('checkout/1');
    $this->assertCssElementContains('.order-total-line .order-total-line-value', '$50.00');
  }

  /**
   * Tests the minimum validation amount on checkout.
   *
   * @param string $amount_1
   *   The amount for the first order item.
   * @param string $amount_2
   *   The amount for the second order item.
   * @param string $quantity_1
   *   The quantity for the first order item.
   * @param string $quantity_2
   *   The quantity for the second order item.
   * @param string $valid
   *   If the amount is valid.
   *
   * @throws \Exception
   *
   * @dataProvider providerMinimumAmountValidationOnCheckout
   */
  public function testMinimumAmountValidationOnCheckout(string $amount_1, string $amount_2, string $quantity_1, string $quantity_2, string $valid) {

    $this->createCommercePaymentGateway();
    $this->setupApigeeAddCreditProduct('default', FALSE);

    // Updated the number pattern as it is needed as transaction id
    // when updating balance.
    $number_pattern = NumberPattern::load('order_default');
    $number_pattern = $number_pattern->setPluginConfiguration(['pattern' => '[commerce_order:uuid]-[pattern:number]']
        );
    $number_pattern->save();

    // Add a product.
    $this->drupalGet('product/add/default');
    $title = $this->randomString(16);
    $this->submitForm([
      'title[0][value]' => $title,
      'variations[form][0][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][0][price][0][number]' => '1.00',
      'variations[form][0][apigee_price_range][0][price_range][fields][minimum]' => '10.00',
    ], 'Save');

    $this->drupalGet('product/1');
    $this->submitForm([
      'unit_price[0][amount][number]' => $amount_1,
    ], 'Add to cart');

    $text = !$valid ?? 'This amount cannot be less than USD10.00.';
    $this->assertSession()->pageTextContains($text);

    $this->drupalGet('product/1');
    $this->submitForm([
      'unit_price[0][amount][number]' => $amount_2,
    ], 'Add to cart');

    $text = !$valid ?? 'This amount cannot be less than USD10.00.';
    $this->assertSession()->pageTextContains($text);

    if ($valid) {
      // Go to the cart page.
      $this->drupalGet('cart');
      $this->submitForm([
        'edit_quantity[0]' => $quantity_1,
        'edit_quantity[1]' => $quantity_2,
      ], 'Checkout');
      $this->assertCssElementContains('h1.page-title', 'Order information');

      // Submit payment information.
      $this->assertSession()->waitForElementVisible('css', '.test-wait', 1000);
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
      $this->assertCssElementContains('.checkout-order-summary', $title);
      $total = ($amount_1 * $quantity_1) + ($amount_2 * $quantity_2);

      $this->assertCssElementContains('.checkout-order-summary', "Total $$total");

      // Finalize the payment.
      $this->queueSupportedCurrencyResponse();
      $this->assertSession()->waitForElementVisible('css', '.test-wait', 1000);
      $this->submitForm([], 'Pay and complete purchase');

      $text = 'Complete';
      $this->assertSession()->pageTextContains($text);

    }
  }

  /**
   * Provides data to self::testPriceRangeFieldValidation().
   */
  public function providerPriceRange() {
    return [
      [
        '20.00',
        '30.00',
        '15.00',
        'The default value must be between the minimum and the maximum price.',
      ],
      [
        '20.00',
        '',
        '15.00',
        'This default value cannot be less than the minimum price.',
      ],
      [
        '20.00',
        '10.00',
        '',
        'The minimum value is greater than the maximum value.',
      ],
    ];
  }

  /**
   * Provides data to self::testUnitPriceValidation().
   */
  public function providerUnitPrice() {
    return [
      [
        '20.00',
        '30.00',
        '25.00',
        '35.00',
        'The amount must be between USD20.00 and USD30.00.',
      ],
      [
        '20.00',
        '',
        '25.00',
        '15.00',
        'This amount cannot be less than USD20.00.',
      ],
      [
        '20.00',
        '',
        '',
        '15.00',
        'This amount cannot be less than USD20.00.',
      ],
    ];
  }

  /**
   * Provides data to self::testPriceFieldOnDefaultProduct().
   */
  public function providerPriceField() {
    return [
      // TODO: Commerce throws an error when a string is entered for price.
      [
        '10.00',
        'The product @title has been successfully saved.',
      ],
    ];
  }

  /**
   * Provides data to self::testMinimumAmountValidationOnCheckout().
   */
  public function providerMinimumAmountValidationOnCheckout() {
    return [
      [
        '5.00',
        '2.00',
        '1',
        '1',
        '0',
      ],
      [
        '10.00',
        '11.00',
        '1',
        '1',
        '1',
      ],
    ];
  }

  /**
   * Setup helper for tests.
   *
   * @param string $type
   *   The product type.
   * @param bool $with_range_field
   *   Set to TRUE to enable the price range field on the default variation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setupApigeeAddCreditProduct($type = 'default', $with_range_field = TRUE) {
    $this->configureApigeeAddCreditProduct($type);
    $this->enableProductVariationsField();
    $this->enableOrderItemUnitPriceField();

    if ($with_range_field) {
      $this->enableProductVariationPriceRangeField();
    }
  }

  /**
   * Enable add credit for the a product type.
   *
   * @param string $type
   *   The product type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function configureApigeeAddCreditProduct($type = 'default') {
    $product_type = ProductType::load($type);
    $product_type->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit', 1);
    $product_type->save();
  }

  /**
   * Helper to enable the variation field on the form display.
   */
  protected function enableProductVariationsField() {
    $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('commerce_product.default.default')
      ->setComponent('variations', [
        'type' => 'inline_entity_form_complex',
        'region' => 'content',
      ])
      ->save();
  }

  /**
   * Helper to configure the unit price field on the default order item.
   */
  protected function enableOrderItemUnitPriceField() {
    // Enable the unit price field.
    $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('commerce_order_item.default.add_to_cart')
      ->removeComponent('purchased_entity', [
        'type' => 'commerce_product_variation_title',
      ])
      ->setComponent('unit_price', [
        'region' => 'content',
        'type' => 'commerce_unit_price',
      ])
      ->save();
  }

  /**
   * Helper to configure price range field for default product type.
   */
  protected function enableProductVariationPriceRangeField() {
    // Disable the price field and enable the price range field.
    $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('commerce_product_variation.default.default')
      ->removeComponent('price')
      ->setComponent('apigee_price_range', [
        'region' => 'content',
      ])
      ->save();
  }

  /**
   * Helper to queue mock response for supported currency.
   *
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function queueSupportedCurrencyResponse(): void {
    $this->stack->queueMockResponse([
      'get-supported-currencies' => [
        'currencies' => [
          new SupportedCurrency([
            "description" => "United States Dollars",
            "displayName" => "United States Dollars",
            "id" => "usd",
            "minimumTopupAmount" => 11.0000,
            "name" => "USD",
            "status" => "ACTIVE",
            "virtualCurrency" => FALSE,
          ]),
        ],
      ],
    ]);
  }

}
