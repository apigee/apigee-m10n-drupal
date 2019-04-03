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
    $this->assertSession()->checkboxChecked(AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME . '[value]');
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
    static::assertSame('1', $product->get(AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME)->value);
  }

}
