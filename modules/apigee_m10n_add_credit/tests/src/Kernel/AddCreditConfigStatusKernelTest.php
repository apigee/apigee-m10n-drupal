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

use Apigee\Edge\Api\Monetization\Entity\SupportedCurrency;
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Tests the status page for add credit.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_kernel
 */
class AddCreditConfigStatusKernelTest extends MonetizationKernelTestBase {

  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Base modules.
    'key',
    'file',
    'field',
    'options',
    'path',
    'path_alias',
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_api_client',
    'system',
    // Modules for this test.
    'apigee_m10n_add_credit',
    'address',
    'profile',
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'commerce',
    'user',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('apigee_edge', ['apigee_edge_job']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'apigee_m10n_add_credit',
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_product');
  }

  /**
   * Tests the status report.
   *
   * @throws \Exception
   */
  public function testStatusReport() {
    $this->warmOrganizationCache();

    // TODO: Figure out why this line is needed otherwise monetization fails.
    \Drupal::service('apigee_m10n.monetization')->isMonetizationEnabled();

    $requirements = $this->checkAddCreditRequirements();
    $this->assertEquals(REQUIREMENT_WARNING, $requirements['severity']);
    $this->assertEquals('The following supported currencies have not been configured for add credit: usd, aud.', strip_tags((string) $requirements['description']));

    // Configure product for usd.
    $this->configureProductForCurrencies(['usd']);
    $requirements = $this->checkAddCreditRequirements();
    $this->assertEquals('The following supported currencies have not been configured for add credit: aud.', strip_tags((string) $requirements['description']));

    // Configure product for all currencies.
    $this->configureProductForCurrencies(['usd', 'aud']);
    $this->assertNull($this->checkAddCreditRequirements());
  }

  /**
   * Helper to check requirements for apigee_m10n_add_credit.
   *
   * @return array|null
   *   An array of requirements warnings or null.
   */
  protected function checkAddCreditRequirements() {
    $this->stack->queueMockResponse([
      'get_supported_currencies' => [
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
          new SupportedCurrency([
            "description" => "Australia Dollars",
            "displayName" => "Australia Dollars",
            "id" => "aud",
            "minimumTopupAmount" => 10.0000,
            "name" => "AUD",
            "status" => "ACTIVE",
            "virtualCurrency" => FALSE,
          ]),
        ],
      ],
    ]);

    $module_handler = \Drupal::moduleHandler();
    $module_handler->loadInclude('apigee_m10n_add_credit', 'install');
    $requirements = apigee_m10n_add_credit_requirements('runtime');
    return $requirements['add_credit_products'] ?? NULL;
  }

  /**
   * Helper to configure product for currencies.
   *
   * @param array $currencies
   *   An array of supported currency ids.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function configureProductForCurrencies(array $currencies) {
    // Create an add credit product.
    $product = Product::create([
      'title' => $this->randomMachineName(),
      'type' => 'default',
      'stores' => [$this->createStore()],
      'variations' => [],
      AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME => 1,
    ]);
    $product->save();

    // Update config.
    $config = [];
    foreach ($currencies as $currency) {
      $config[$currency] = [
        'product_id' => $product->id(),
      ];
    }

    \Drupal::configFactory()->getEditable(AddCreditConfig::CONFIG_NAME)->set('products', $config)->save();
  }

}
