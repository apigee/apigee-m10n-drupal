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

namespace Drupal\Tests\apigee_m10n_add_credit\Functional\ApigeeX;

use Drupal\apigee_m10n_add_credit\Form\GeneralSettingsConfigForm;
use Drupal\Core\Url;

/**
 * Tests the general settings config form.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class GeneralSettingsConfigFormTest extends AddCreditFunctionalTestBase {

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
  protected function setUp(): void {
    parent::setUp();

    $this->warmApigeexOrganizationCache();
    $this->stack->queueMockResponse(['post-apigeex-billing-type']);
    $this->developer = $this->signInAsAdmin();

    $this->assertNoClientError();

  }

  /**
   * Tests the general settings config form UI.
   *
   * @throws \Exception
   *
   * @covers \Drupal\apigee_m10n_add_credit\Form\GeneralSettingsConfigForm::buildForm
   * @covers \Drupal\apigee_m10n_add_credit\Form\GeneralSettingsConfigForm::getEditableConfigNames
   * @covers \Drupal\apigee_m10n_add_credit\Form\GeneralSettingsConfigForm::getFormId
   * @covers \Drupal\apigee_m10n_add_credit\Form\GeneralSettingsConfigForm::validateForm
   * @covers \Drupal\apigee_m10n_add_credit\Form\GeneralSettingsConfigForm::submitForm
   */
  public function testConfigFormUi() {
    $this->drupalGet(Url::fromRoute('apigee_m10n_add_credit.settings.generalsettings'));

    // Check the title.
    $this->assertCssElementContains('h1.page-title', 'General Settings');

    // Check the default value for Billingtype.
    $this->assertSession()->fieldValueEquals('billingtype', 'Postpaid');

    // Check the default value for Wait time.
    $this->assertSession()->fieldValueEquals('wait_time', '300');

    // Check the note about Maximum wait Settings notifications.
    $this->assertCssElementContains('div.apigee-add-credit-notification-note', "Apigee backend API for credit balance doesn't allow frequent top-ups, and hence in line with the same, the portal also disables the add credit option for a fixed period. If you want to change the above value, please make sure the changes fits well with the backend API guarantees.");

    // Test form config.
    $this->submitForm([
      'billingtype' => 'prepaid',
      'wait_time' => '250',
    ], 'Save configuration');
    $this->assertCssElementContains('div.messages--status', ' The configuration options have been saved.');

    // Load the saved config and test the changes.
    $settings = $this->config(GeneralSettingsConfigForm::CONFIG_NAME);
    static::assertSame('prepaid', $settings->get('billing.billingtype'));
    static::assertSame(250, $settings->get('wait_time'));
  }

}
