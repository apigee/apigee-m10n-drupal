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

use Drupal\apigee_m10n_add_credit\Form\ApigeeAddCreditConfigForm;
use Drupal\commerce_product\Entity\Product;
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
class AddCreditProductAdminTest extends MonetizationFunctionalTestBase {
  use StoreCreationTrait;

  /**
   * The admin user.
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
    // Create an admin account.
    $this->admin = $this->createAccount(array_keys(\Drupal::service('user.permissions')->getPermissions()));
    $this->queueOrg();
    $this->drupalLogin($this->admin);
    $this->assertNoClientError();

    $this->store = $this->createStore(NULL, $this->config('system.site')->get('mail'));
    $this->store->save();
  }

  /**
   * Tests the UI for setting up an Add credit product.
   *
   * @throws \Exception
   *
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::entityBaseFieldInfo
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::entityBundleFieldInfo
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::formCommerceProductTypeEditFormAlter
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::formCommerceProductTypeSubmit
   */
  public function testSetUpAddCreditProduct() {
    // Edit the product type.
    $this->drupalGet('admin/commerce/config/product-types/default/edit');
    $this->assertCssElementContains('h1.page-title', 'Edit Default');
    $this->assertSession()->checkboxNotChecked('apigee_m10n_enable_add_credit');
    // Enable add credit for this product type.
    $this->submitForm(['apigee_m10n_enable_add_credit' => 1], 'Save');
    // Make sure the product was updated successfully.
    $this->assertCssElementContains('h1.page-title', 'Product types');
    $this->assertCssElementContains('div.messages--status', 'The product type Default has been successfully saved.');

    // Go to the "manage form display" page for this product type.
    $this->drupalGet('admin/commerce/config/product-types/default/edit/form-display');
    // Get the field table text.
    $table_test = $this->getSession()->getPage()->find('css', 'table#field-display-overview')->getText();
    // Make sure the enable field comes before the "Disabled" row (region).
    static::assertLessThan(
      strpos($table_test, 'Disabled'),
      strpos($table_test, 'This is an Apigee add credit product'),
      'Failed asserting the "Add credit" toggle is not disabled.'
    );

    // Go to the "Add product" page.
    $this->drupalGet('product/add/default');
    $this->assertSession()->checkboxChecked('apigee_add_credit_enabled[value]');
    $this->assertCssElementContains('h1.page-title', 'Add product');
    // Generate a random title.
    $title = $this->randomString(16);
    $this->submitForm([
      'title[0][value]' => $title,
      'variations[form][inline_entity_form][sku][0][value]' => 'SKU-ADD-CREDIT-10',
      'variations[form][inline_entity_form][price][0][number]' => '10.00',
    ], 'Save');
    $this->assertCssElementContains('h1.page-title', $title);
    $this->assertCssElementContains('div.messages--status', "The product {$title} has been successfully saved.");

    $product = Product::load(1);
    static::assertSame($title, $product->getTitle());
    static::assertSame('1', $product->apigee_add_credit_enabled->value);
  }

  /**
   * Tests the UI for setting up an Add credit product.
   *
   * @throws \Exception
   *
   * @covers \Drupal\apigee_m10n_add_credit\Form\ApigeeAddCreditConfigForm::buildForm
   * @covers \Drupal\apigee_m10n_add_credit\Form\ApigeeAddCreditConfigForm::getEditableConfigNames
   * @covers \Drupal\apigee_m10n_add_credit\Form\ApigeeAddCreditConfigForm::getFormId
   * @covers \Drupal\apigee_m10n_add_credit\Form\ApigeeAddCreditConfigForm::validateForm
   * @covers \Drupal\apigee_m10n_add_credit\Form\ApigeeAddCreditConfigForm::submitForm
   */
  public function testNotificationAdminUi() {
    $this->drupalGet('admin/config/apigee-edge/monetization/add-credit-settings');
    // Check the title.
    $this->assertCssElementContains('h1.page-title', 'Apigee Add Credit Configuration');
    // Check the default values.
    $this->assertSession()->checkboxNotChecked('Always');
    $this->assertSession()->checkboxChecked('Only on error');
    $site_mail = $this->config('system.site')->get('mail');
    static::assertNotEmpty($site_mail);
    $this->assertSession()->fieldValueEquals('Email address', $site_mail);
    // Check the note about configuring commerce notifications.
    $this->assertCssElementContains('div.apigee-add-credit-notification-note', 'It is possible to configure drupal commerce to send an email to the purchaser upon order completion.');
    $this->assertCssElementContains('div.apigee-add-credit-notification-note', 'See Drupal commerce documentation for more information.');

    // Change to always notify.
    $this->submitForm(['notify_on' => ApigeeAddCreditConfigForm::NOTIFY_ALWAYS], 'Save configuration');
    $this->assertCssElementContains('h1.page-title', 'Apigee Add Credit Configuration');
    $this->assertCssElementContains('div.messages--status', ' The configuration options have been saved.');
    // Load the saved config and test the changes.
    $settings = $this->config(ApigeeAddCreditConfigForm::CONFIG_NAME);
    static::assertSame(ApigeeAddCreditConfigForm::NOTIFY_ALWAYS, $settings->get('notify_on'));
    static::assertSame($site_mail, $settings->get('notification_recipient'));
  }

}
