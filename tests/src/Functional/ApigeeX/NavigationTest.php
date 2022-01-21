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

namespace Drupal\Tests\apigee_m10n\Functional\ApigeeX;

/**
 * Functional test for navigation.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class NavigationTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->placeBlock('system_menu_block:main', ['region' => 'primary_menu']);
    $this->placeBlock('system_menu_block:account', ['region' => 'secondary_menu']);
    $this->placeBlock('local_tasks_block', ['region' => 'content']);
  }

  /**
   * Test the site navigation.
   *
   * @throws \Exception
   */
  public function testNavigation() {
    $this->developer = $this->createAccount([
      'view xproduct',
      'view own purchased_plan'
    ]);
    // Warm the ApigeeX organization.
    $this->warmApigeexOrganizationCache();
    $this->drupalLogin($this->developer);

    // Check the manage profile link.
    $this->clickLink('My account');

    // Check the Pricing & Plans link.
    $session = $this->assertSession();
    $session->linkExists('Buy API');
    $session->linkExists('My account');
    $session->linkExists('Manage Subscriptions');

    $this->assertCssElementContains('.block-menu.navigation.menu--main ', 'Buy API');
    $this->assertCssElementContains('.block-menu.navigation.menu--account', 'My account');

    // Warm the ApigeeX organization.
    $this->warmApigeexOrganizationCache();
    $xproduct = $this->createApigeexProduct();
    $xrate_plan = $this->createRatePlan($xproduct);
    $purchased_product = $this->createPurchasedProduct($this->developer, $xrate_plan);

    $this->stack->queueMockResponse([
      'get_developer_purchased_products' => [
        'purchased_products' => [$purchased_product],
      ],
    ])->queueMockResponse([
      'get_monetization_apigeex_plans' => [
        'plans' => [$xrate_plan]
      ]
    ]);

    // Check the manage Manage Subscriptions link.
    $this->clickLink('Manage Subscriptions');
  }

}
