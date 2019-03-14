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
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm;
use Drupal\commerce_product\Entity\Product;

/**
 * Tests the testing framework for testing offline.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class AddCreditProductAdminTest extends AddCreditFunctionalTestBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function setUp() {
    parent::setUp();

    $this->signInAsAdmin();

    $this->assertNoClientError();

    $this->createCommerceStore();
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
    // Get the field table html. `GoutteDriver` treats option values as text.
    $table_html = $this->getSession()->getPage()->find('css', 'table#field-display-overview')->getHtml();
    // Make sure the enable field comes before the "Disabled" row (region).
    static::assertLessThan(
      strpos($table_html, '<td colspan="7">Disabled</td>'),
      strpos($table_html, 'This is an Apigee add credit product'),
      'Failed asserting the "Add credit" toggle is not disabled.'
    );

    // Enable the variations field.
    $this->container->get('entity.manager')
      ->getStorage('entity_form_display')
      ->load('commerce_product.default.default')
      ->setComponent('variations', [
        'type' => 'inline_entity_form_complex',
        'region' => 'content',
      ])
      ->save();

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
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::buildForm
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::getEditableConfigNames
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::getFormId
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::validateForm
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::submitForm
   */
  public function testNotificationAdminUi() {
    $this->queueOrg();
    $this->queueSupportedCurrencyResponse();
    $this->drupalGet('admin/config/apigee-edge/monetization/add-credit');
    // Check the title.
    $this->assertCssElementContains('h1.page-title', 'Add credit');
    // Check the default values.
    $this->assertSession()->checkboxNotChecked('Always');
    $this->assertSession()->checkboxChecked('Only on error');
    $site_mail = $this->config('system.site')->get('mail');
    static::assertNotEmpty($site_mail);
    $this->assertSession()->fieldValueEquals('Email address', $site_mail);
    // Check the note about configuring commerce notifications.
    $this->assertCssElementContains('div.apigee-add-credit-notification-note', 'You can configure Drupal Commerce to send an email to the consumer to confirm completion of the order.');
    $this->assertCssElementContains('div.apigee-add-credit-notification-note', 'See Drupal commerce documentation.');

    // Change to always notify.
    $this->queueSupportedCurrencyResponse();
    $this->submitForm(['notify_on' => AddCreditConfig::NOTIFY_ALWAYS], 'Save configuration');
    $this->assertCssElementContains('div.messages--status', ' The configuration options have been saved.');
    // Load the saved config and test the changes.
    $settings = $this->config(AddCreditConfig::CONFIG_NAME);
    static::assertSame(AddCreditConfig::NOTIFY_ALWAYS, $settings->get('notify_on'));
    static::assertSame($site_mail, $settings->get('notification_recipient'));
  }

  /**
   * Helper to queue mock response for supported currency.
   *
   * TODO: Move this to a trait.
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
