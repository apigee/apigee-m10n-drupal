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

use Drupal\Core\Url;
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\commerce_product\Entity\ProductType;

/**
 * Tests the add credit button on prepaid balance page.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class AddCreditPrepaidBalancePageTest extends AddCreditFunctionalTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->warmApigeexOrganizationCache();
    $this->stack->queueMockResponse(['post-apigeex-billing-type']);

    $this->developer = $this->signIn([
      'view own prepaid balance',
      'add credit to own developer prepaid balance',
    ], 'prepaid');

    // Enable add credit for the product type.
    $product_type = ProductType::load('default');
    $product_type->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit', 1);
    $product_type->save();

    // Create a product.
    $variation = $this->createCommerceProductVariation();
    $this->product = $this->createCommerceProduct($this->createCommerceStore(), $variation);

    $this->createCommercePaymentGateway();
    \Drupal::service('commerce_price.currency_importer')->import('AUD');

  }

  /**
   * Tests the add credit button per currency config.
   *
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::apigeeM10nPrepaidBalanceListAlter
   */
  public function testAddCreditButtonForCurrency() {
    $this->warmApigeexOrganizationCache();

    // Configure an add credit product for USD.
    // There should be an add credit button for usd but NOT for aud.
    $this->setAddCreditProductForCurrencyId($this->product, 'usd');
    $this->stack->queueMockResponse(['get-apigeex-billing-type']);

    $this->queueApigeexDeveloperResponse($this->developer);
    $this->stack->queueMockResponse([
      'get_apigeex_prepaid_balances',
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.xbilling', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->elementExists('css', '.add-credit--usd.dropbutton');
    $this->assertSession()->elementNotExists('css', '.add-credit--aud.dropbutton');

    // Verify link to add credit, it should contain the developer email.
    $url = $this->product->toUrl('canonical', [
      'query' => [
        AddCreditConfig::TARGET_FIELD_NAME => [
          'target_type' => 'developer',
          'target_id' => $this->developer->getEmail(),
        ],
      ],
    ]);
    $this->assertSession()->linkByHrefExists($url->toString());

    // Configure an add credit product for AUD.
    // There should be an add credit button for BOTH usd and aud.
    $this->setAddCreditProductForCurrencyId($this->product, 'aud');
    $this->queueApigeexDeveloperResponse($this->developer);
    $this->stack->queueMockResponse([
      'get_apigeex_prepaid_balances',
    ]);
    $this->drupalGet(Url::fromRoute('apigee_monetization.xbilling', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->elementExists('css', '.add-credit--usd.dropbutton');
    $this->assertSession()->elementExists('css', '.add-credit--aud.dropbutton');

    // Unpublish the add credit product.
    // There should NOT be any add credit button.
    $this->product->setUnpublished()->save();
    $this->queueApigeexDeveloperResponse($this->developer);
    $this->stack->queueMockResponse([
      'get_apigeex_prepaid_balances',
    ]);
    $this->drupalGet(Url::fromRoute('apigee_monetization.xbilling', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->elementNotExists('css', '.add-credit--usd.dropbutton');
    $this->assertSession()->elementNotExists('css', '.add-credit--aud.dropbutton');

    // Disable add credit for this product.
    // There should NOT be any add credit button.
    $this->product->setPublished();
    $this->product->set(AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME, FALSE);
    $this->product->save();
    $this->queueApigeexDeveloperResponse($this->developer);
    $this->stack->queueMockResponse([
      'get_apigeex_prepaid_balances',
    ]);
    $this->drupalGet(Url::fromRoute('apigee_monetization.xbilling', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->elementNotExists('css', '.add-credit--usd.dropbutton');
    $this->assertSession()->elementNotExists('css', '.add-credit--aud.dropbutton');
  }

}
