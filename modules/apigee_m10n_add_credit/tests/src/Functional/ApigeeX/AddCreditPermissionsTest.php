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

use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;

/**
 * Tests permissions for add credit products.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class AddCreditPermissionsTest extends AddCreditFunctionalTestBase {

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

    // Enable add credit for the product type.
    $product_type = ProductType::load('default');
    $product_type->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit', 1);
    $product_type->save();

    // Create a product.
    $variation = $this->createCommerceProductVariation();
    $this->product = $this->createCommerceProduct($this->createCommerceStore(), $variation);

    $this->createCommercePaymentGateway();

    // Remove default module permission.
    user_role_revoke_permissions(RoleInterface::AUTHENTICATED_ID, ['add credit to own developer prepaid balance']);
  }

  /**
   * Tests permissions for add credit products.
   *
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::commerceProductAccess
   */
  public function testPermissionsForAddCreditProducts() {
    $path = $this->product->toUrl();

    // Create and sign in a user with no add credit permissions.
    $this->warmApigeexOrganizationCache();
    $this->stack->queueMockResponse(['post-apigeex-billing-type']);
    $this->developer = $this->signIn();

    // User should see access denied on an add credit product.
    $this->drupalGet($path);
    $this->assertSession()->responseContains('Access denied');

    $this->stack->queueMockResponse(['post-apigeex-billing-type']);
    $this->developer = $this->signIn(['add credit to own developer prepaid balance'], 'Prepaid');

    $this->drupalGet($path);
    $this->assertSession()->responseNotContains('Access denied');
  }

  /**
   * Tests permissions and add credit button on prepaid balance page.
   *
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::commerceProductAccess
   * @covers \Drupal\apigee_m10n_add_credit\AddCreditService::apigeeM10nPrepaidBalanceListAlter
   */
  public function testAddCreditButtonPermissionsOnPrepaidBalancePage() {
    // Configure an add credit product for USD.
    $this->setAddCreditProductForCurrencyId($this->product, 'usd');

    // Create and sign in a user with no add credit permissions.
    $this->warmApigeexOrganizationCache();
    $this->stack->queueMockResponse(['post-apigeex-billing-type']);
    $this->developer = $this->signIn(['view own prepaid balance'], 'prepaid');

    $this->queueApigeexDeveloperResponse($this->developer);
    $this->stack->queueMockResponse(['get-apigeex-billing-type']);

    $this->queueApigeexDeveloperResponse($this->developer);
    $this->stack->queueMockResponse([
      'get-apigeex-prepaid-balances' => [
        "currency_code" => 'AUD',
        "current_units_aud" => "20",
        "current_nano_aud" => "560000000",

        "currency_code" => 'USD',
        "current_units_usd" => "15",
        "current_nano_usd" => "340000000",
      ],
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.xbilling', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->elementNotExists('css', '.add-credit.dropbutton');

    // Create and sign in a user with add credit permissions.
    $this->stack->queueMockResponse(['post-apigeex-billing-type']);
    $this->developer = $this->signIn([
                          'view own prepaid balance',
                          'add credit to own developer prepaid balance'
                        ], 'Prepaid');
    $this->queueApigeexDeveloperResponse($this->developer);

    $this->stack->queueMockResponse(['get-apigeex-billing-type']);

    $this->queueApigeexDeveloperResponse($this->developer);
    $this->stack->queueMockResponse([
      'get-apigeex-prepaid-balances' => [
        "current_units_aud" => "20",
        "current_nano_aud" => "560000000",

        "current_units_usd" => "15",
        "current_nano_usd" => "340000000",
      ],
    ]);
    $this->drupalGet(Url::fromRoute('apigee_monetization.xbilling', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->elementExists('css', '.add-credit.dropbutton');
  }

}
