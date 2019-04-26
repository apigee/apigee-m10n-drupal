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

use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Url;

/**
 * Tests the add credit config form.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class AddCreditConfigFormTest extends AddCreditFunctionalTestBase {

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

    $this->signInAsAdmin();

    $this->assertNoClientError();

    $product_type = ProductType::load('default');
    $product_type->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit', 1);
    $product_type->save();

    // Create a product.
    $variation = $this->createCommerceProductVariation();
    $this->product = $this->createCommerceProduct($this->createCommerceStore(), $variation);
  }

  /**
   * Tests the Add Credit config form UI.
   *
   * @throws \Exception
   *
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::buildForm
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::getEditableConfigNames
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::getFormId
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::validateForm
   * @covers \Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm::submitForm
   */
  public function testConfigFormUi() {
    $this->queueOrg();
    $this->queueMockResponses(['get-supported-currencies']);
    $this->drupalGet(Url::fromRoute('apigee_m10n_add_credit.settings.add_credit')->toString());

    // Check the title.
    $this->assertCssElementContains('h1.page-title', 'Add credit');

    // Check the default value for modal.
    $this->assertSession()->checkboxChecked('Use modal');

    // Check UI form products.
    $this->assertSession()->elementExists('css', '#edit-products-container .button-action[href="' . Url::fromRoute('entity.commerce_product.add_page')->toString() . '"]');
    $this->assertCssElementContains('#edit-products', 'United States Dollars');
    $this->assertCssElementContains('#edit-products', 'Australia Dollars');
    $this->assertSession()->elementNotExists('css', '.edit--usd.dropbutton');
    $this->assertSession()->elementNotExists('css', '.edit--aud.dropbutton');

    // Check UI for notifications.
    $this->assertSession()->checkboxNotChecked('Always');
    $this->assertSession()->checkboxChecked('Only on error');
    $site_mail = $this->config('system.site')->get('mail');
    static::assertNotEmpty($site_mail);
    $this->assertSession()->fieldValueEquals('Email address', $site_mail);

    // Check the note about configuring commerce notifications.
    $this->assertCssElementContains('div.apigee-add-credit-notification-note', 'You can configure Drupal Commerce to send an email to the consumer to confirm completion of the order.');
    $this->assertCssElementContains('div.apigee-add-credit-notification-note', 'See Drupal commerce documentation.');

    // Test form config.
    $this->queueMockResponses(['get-supported-currencies']);
    $this->submitForm([
      'use_modal' => FALSE,
      'notify_on' => AddCreditConfig::NOTIFY_ALWAYS,
      'products[usd][product_id]' => "{$this->product->label()} ({$this->product->id()})",
    ], 'Save configuration');
    $this->assertCssElementContains('div.messages--status', ' The configuration options have been saved.');

    // Load the saved config and test the changes.
    $settings = $this->config(AddCreditConfig::CONFIG_NAME);
    static::assertSame(FALSE, $settings->get('use_modal'));
    static::assertSame($this->product->id(), $settings->get('products.usd.product_id'));
    static::assertSame(AddCreditConfig::NOTIFY_ALWAYS, $settings->get('notify_on'));
    static::assertSame($site_mail, $settings->get('notification_recipient'));
  }

}
