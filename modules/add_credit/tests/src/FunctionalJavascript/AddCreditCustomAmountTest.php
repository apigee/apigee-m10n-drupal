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
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
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
   * The name of the top up amount field used for tests.
   */
  const TOP_UP_AMOUNT_FIELD_NAME = 'field_amount';

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
   * Tests the apigee_top_up_amount_price field widget.
   */
  public function testTopUpAmountFieldPriceWidget() {
    $this->configureVariationsField();

    // Configure an apigee_top_up_amount field with the default price widget.
    // Check if price field is disabled and all top_up_amount fields are visible.
    $this->configureTopUpAmountField();
    $this->drupalGet('product/add/default');
    $this->assertSession()
      ->elementNotExists('css', '[name="variations[form][inline_entity_form][price][0][number]"]');
    $top_up_fields = ['number', 'currency_code'];
    foreach ($top_up_fields as $field_name) {
      $this->assertSession()
        ->elementExists('css', '[name="variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][' . $field_name . ']"]');
    }
  }

  /**
   * Tests the apigee_top_up_amount_range field widget.
   */
  public function testTopUpAmountFieldRangeWidget() {
    $this->configureVariationsField();

    // Configure an apigee_top_up_amount field with the price range widget.
    // Check if price field is disabled and all top_up_amount fields are visible.
    $this->configureTopUpAmountField('apigee_top_up_amount_range');
    $this->drupalGet('product/add/default');
    $top_up_fields = ['minimum', 'maximum', 'number', 'currency_code'];
    foreach ($top_up_fields as $field_name) {
      $this->assertSession()
        ->elementExists('css', '[name="variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][' . $field_name . ']"]');
    }
  }

  /**
   * Tests validation for the top up amount field with price widget.
   *
   * @param float|null $number
   *   The amount.
   * @param string|null $message
   *   The expected message.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   *
   * @dataProvider providerTopUpAmountPrice
   */
  public function testTopUpAmountPriceFieldValidation(float $number = NULL, string $message = NULL) {
    $this->configureVariationsField();
    $this->configureTopUpAmountField();

    // Add a product.
    $this->drupalGet('product/add/default');
    $this->queueSupportedCurrencyResponse();

    // Validate price range fields.
    $this->submitForm([
      'variations[form][inline_entity_form][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][number]' => $number,
    ], 'Create variation');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($message);
  }

  /**
   * Tests validation for the top up amount field with range widget.
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
   * @dataProvider providerTopUpAmountRange
   */
  public function testTopUpAmountRangeFieldValidation(float $minimum = NULL, float $maximum = NULL, float $default = NULL, string $message = NULL) {
    $this->configureVariationsField();
    $this->configureTopUpAmountField('apigee_top_up_amount_range');

    // Add a product.
    $this->drupalGet('product/add/default');
    if ($minimum) {
      $this->queueSupportedCurrencyResponse();
    }

    // Validate price range fields.
    $this->submitForm([
      'variations[form][inline_entity_form][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][minimum]' => $minimum,
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][maximum]' => $maximum,
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][number]' => $default,
    ], 'Create variation');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($message);
  }

  /**
   * Tests the unit field validation and top up amount field with price widget.
   *
   * @param float|null $number
   *   The amount.
   * @param float|null $amount
   *   The amount for the unit price field.
   * @param string|null $message
   *   The expected message.
   *
   * @throws \Exception
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   *
   * @dataProvider providerUnitPricePrice
   */
  public function testUnitPricePriceValidation(float $number = NULL, float $amount = NULL, string $message = NULL) {
    $this->configureVariationsField();
    $this->configureTopUpAmountField();

    // Add a product.
    $this->drupalGet('product/add/default');
    $this->queueSupportedCurrencyResponse();

    $title = $this->randomString(16);
    $this->submitForm([
      'title[0][value]' => $title,
      'variations[form][inline_entity_form][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][number]' => $number,
    ], 'Save');

    // Check if default value is set.
    $this->assertSession()
      ->elementAttributeContains('css', '[name="unit_price[0][amount][number]"]', 'value', $number);

    // Check if unit price is properly validated.
    $this->submitForm([
      'unit_price[0][amount][number]' => $amount,
    ], 'Add to cart');
    $this->assertSession()->pageTextContains($message);
  }

  /**
   * Tests the unit field validation and top up amount field with range widget.
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
   * @throws \Exception
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   *
   * @dataProvider providerUnitPriceRange
   */
  public function testUnitPriceRangeValidation(float $minimum = NULL, float $maximum = NULL, float $default = NULL, float $amount = NULL, string $message = NULL) {
    $this->configureVariationsField();
    $this->configureTopUpAmountField('apigee_top_up_amount_range');

    // Add a product.
    $this->drupalGet('product/add/default');
    $this->queueSupportedCurrencyResponse();

    $title = $this->randomString(16);
    $this->submitForm([
      'title[0][value]' => $title,
      'variations[form][inline_entity_form][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][minimum]' => $minimum,
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][maximum]' => $maximum,
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][number]' => $default,
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
    $this->configureVariationsField();
    $this->configureTopUpAmountField('apigee_top_up_amount_range');

    // Add a product.
    $this->drupalGet('product/add/default');
    $this->queueSupportedCurrencyResponse();

    $title = $this->randomString(16);
    $this->submitForm([
      'title[0][value]' => $title,
      'variations[form][inline_entity_form][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][minimum]' => 20,
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][maximum]' => 500,
      'variations[form][inline_entity_form][' . static::TOP_UP_AMOUNT_FIELD_NAME . '][0][top_up_amount][fields][number]' => 40,
    ], 'Save');

    $this->submitForm([
      'unit_price[0][amount][number]' => 50,
    ], 'Add to cart');

    $this->drupalGet('checkout/1');
    $this->assertCssElementContains('.field--name-total-price .order-total-line-value', '$50.00');
  }

  /**
   * Provides data to self::testTopUpAmountPriceFieldValidation().
   */
  public function providerTopUpAmountPrice() {
    return [
      [
        5.00,
        'The minimum top up amount for USD is USD10.00.',
      ],
    ];
  }

  /**
   * Provides data to self::testTopUpAmountRangeFieldValidation().
   */
  public function providerTopUpAmountRange() {
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
        'The default value cannot be less than the minimum price.',
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
   * Provides data to self::testUnitPriceRangeValidation().
   */
  public function providerUnitPriceRange() {
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
        'The unit price cannot be less than USD20.00.',
      ],
      [
        20.00,
        NULL,
        NULL,
        15.00,
        'The unit price cannot be less than USD20.00.',
      ],
    ];
  }

  /**
   * Provides data to self::testUnitPricePriceValidation().
   */
  public function providerUnitPricePrice() {
    return [
      [
        30.00,
        25.00,
        'The unit price cannot be less than USD30.00.',
      ],
    ];
  }

  /**
   * Helper to enable the variation field on the form display.
   */
  protected function configureVariationsField() {
    $this->container->get('entity.manager')
      ->getStorage('entity_form_display')
      ->load('commerce_product.default.default')
      ->setComponent('variations', [
        'type' => 'inline_entity_form_complex',
        'region' => 'content',
      ])
      ->save();
  }

  /**
   * Helper to configure an apigee_top_up_amount field for default product type.
   *
   * @param string $widget
   *   The widget type for the field.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function configureTopUpAmountField($widget = 'apigee_top_up_amount_price') {
    // Enable add credit for the default product type.
    $product_type = ProductType::load('default');
    $product_type->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit', 1);
    $product_type->save();

    // Add an apigee_top_up_amount_field.
    FieldStorageConfig::create([
      'field_name' => static::TOP_UP_AMOUNT_FIELD_NAME,
      'entity_type' => 'commerce_product_variation',
      'type' => 'apigee_top_up_amount',
    ])->save();

    $currencies = \Drupal::entityTypeManager()
      ->getStorage('commerce_currency')
      ->loadMultiple();
    $currency_codes = array_keys($currencies);

    FieldConfig::create([
      'entity_type' => 'commerce_product_variation',
      'field_name' => static::TOP_UP_AMOUNT_FIELD_NAME,
      'bundle' => 'default',
      'field_type' => 'apigee_top_up_amount',
      'settings' => [
        'available_currencies' => array_combine($currency_codes, $currency_codes),
      ],
    ])->save();

    // Disable the price field and enable the apigee_top_up_amount field.
    $this->container->get('entity.manager')
      ->getStorage('entity_form_display')
      ->load('commerce_product_variation.default.default')
      ->removeComponent('price')
      ->setComponent(static::TOP_UP_AMOUNT_FIELD_NAME, [
        'region' => 'content',
        'type' => $widget,
      ])
      ->save();

    // Enable the unit price field on the default order item.
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
