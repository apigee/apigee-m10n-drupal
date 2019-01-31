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

use Apigee\Edge\Api\Monetization\Entity\SupportedCurrency;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\Tests\apigee_m10n_add_credit\FunctionalJavascript\AddCreditFunctionalJavascriptTestBase;

/**
 * Tests custom amount for an apigee add credit product.
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
   *
   * @throws \Exception
   */
  public function setUp() {
    parent::setUp();

    $this->developer = $this->signInAsAdmin();

    $this->assertNoClientError();

    $this->createStore();
  }

  /**
   * Tests the price range field on a variation.
   */
  public function testPriceRangeField() {
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
      ->elementNotExists('css', '[name="variations[form][inline_entity_form][price][0][number]"]');
    $price_range_fields = ['minimum', 'maximum', 'default', 'currency_code'];
    foreach ($price_range_fields as $field_name) {
      $this->assertSession()
        ->elementExists('css', '[name="variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][' . $field_name . ']"]');
    }
  }

  /**
   * Tests validation for the price range field.
   *
   * @param float|null $minimum
   *   The minimum amount.
   * @param float|null $maximum
   *   The maximum amount.
   * @param float|null $default
   *   The default amount.
   * @param string|null $message
   *   The expected message.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   *
   * @dataProvider providerPriceRange
   */
  public function testPriceRangeFieldValidation(float $minimum = NULL, float $maximum = NULL, float $default = NULL, string $message = NULL) {
    $this->configurePriceRangeField();

    // Add a product.
    $this->drupalGet('product/add/default');
    if ($minimum) {
      $this->queueSupportedCurrencyResponse();
    }

    // Validate price range fields.
    $this->submitForm([
      'variations[form][inline_entity_form][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][minimum]' => $minimum,
      'variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][maximum]' => $maximum,
      'variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][default]' => $default,
    ], 'Create variation');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($message);
  }

  /**
   * Tests the unit price range field validation.
   *
   * @param float|null $minimum
   *   The minimum amount.
   * @param float|null $maximum
   *   The maximum amount.
   * @param float|null $default
   *   The default amount.
   * @param float|null $amount
   *   The amount for the unit price field.
   * @param string|null $message
   *   The expected message.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   *
   * @dataProvider providerUnitPrice
   */
  public function testUnitPriceValidation(float $minimum = NULL, float $maximum = NULL, float $default = NULL, float $amount = NULL, string $message = NULL) {
    $this->configurePriceRangeField();

    // Add a product.
    $this->drupalGet('product/add/default');
    $this->queueSupportedCurrencyResponse();

    $title = $this->randomString(16);
    $this->submitForm([
      'title[0][value]' => $title,
      'variations[form][inline_entity_form][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][minimum]' => $minimum,
      'variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][maximum]' => $maximum,
      'variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][default]' => $default,
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
   * Make sure that the price field does not break if price range is not used.
   *
   * @param mixed $value
   *   The value for the price field.
   * @param bool $valid
   *   Whether the value is valid or not.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   *
   * @dataProvider providerPriceField
   */
  public function testPriceFieldOnDefaultProduct($value, bool $valid) {
    $this->drupalGet('product/add/default');

    $this->submitForm([
      'edit-variations-form-inline-entity-form-price-0-number' => $value,
    ], 'Create variation');
    $this->assertSession()->assertWaitOnAjaxRequest();

    if (!$valid) {
      $this->assertSession()->pageTextContains('Price must be a number');
    }
    else {
      $this->assertSession()->pageTextNotContains('Price must be a number');
    }
  }

  /**
   * Tests adding a custom amount and checking out.
   */
  public function testCustomAmountPriceCheckout() {
    $this->configurePriceRangeField();

    // Add a product.
    $this->drupalGet('product/add/default');
    $this->queueSupportedCurrencyResponse();

    $title = $this->randomString(16);
    $this->submitForm([
      'title[0][value]' => $title,
      'variations[form][inline_entity_form][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][minimum]' => 20,
      'variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][maximum]' => 500,
      'variations[form][inline_entity_form][apigee_price_range][0][price_range][fields][default]' => 40,
    ], 'Save');

    $this->submitForm([
      'unit_price[0][amount][number]' => 50,
    ], 'Add to cart');

    $this->drupalGet('checkout/1');
    $this->assertCssElementContains('.field--name-total-price .order-total-line-value', '$50.00');
  }

  /**
   * Provides data to self::testPriceRangeFieldValidation().
   */
  public function providerPriceRange() {
    return [
      [
        5.00,
        NULL,
        NULL,
        'The minimum top up amount for USD is USD10.00.',
      ],
      [
        20.00,
        30.00,
        15.00,
        'The default value must be between the minimum and the maximum price.',
      ],
      [
        20.00,
        NULL,
        15.00,
        'This default value cannot be less than the minimum price.',
      ],
      [
        20.00,
        10.00,
        NULL,
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
        20.00,
        30.00,
        25.00,
        35.00,
        'The unit price value must be between USD20.00 and USD30.00.',
      ],
      [
        20.00,
        NULL,
        25.00,
        15.00,
        'This unit price cannot be less than USD20.00.',
      ],
      [
        20.00,
        NULL,
        NULL,
        15.00,
        'This unit price cannot be less than USD20.00.',
      ],
    ];
  }

  /**
   * Providers data for self::testPriceFieldOnDefaultProduct().
   */
  public function providerPriceField() {
    return [
      [
        'string',
        FALSE,
      ],
      [
        10.00,
        TRUE,
      ],
    ];
  }

  /**
   * Helper to configure price range field for default product type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function configurePriceRangeField() {
    // Enable add credit for the default product type.
    $product_type = ProductType::load('default');
    $product_type->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit', 1);
    $product_type->save();

    // Disable the price field and enable the price range field.
    $this->container->get('entity.manager')
      ->getStorage('entity_form_display')
      ->load('commerce_product_variation.default.default')
      ->removeComponent('price')
      ->setComponent('apigee_price_range', [
        'region' => 'content',
      ])
      ->save();

    // Enable the unit price field.
    $this->container->get('entity.manager')
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
