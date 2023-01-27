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

namespace Drupal\Tests\apigee_m10n\Functional;

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
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
      'view own prepaid balance',
      'view product_bundle',
      'view own purchased_plan',
    ]);

    $this->drupalLogin($this->developer);

    // Check the manage profile link.
    $this->clickLink('My account');

    // Check the Pricing & Plans link.
    $session = $this->assertSession();
    $session->linkExists('Pricing & plans');
    $session->linkExists('My account');
    $session->linkExists('Balance and plans');

    $this->assertCssElementContains('.block-menu.navigation.menu--main ', 'Pricing & plans');
    $this->assertCssElementContains('.block-menu.navigation.menu--account', 'My account');

    $product_bundle = $this->createProductBundle();
    $rate_plan = $this->createRatePlan($product_bundle);
    $purchased_plan = $this->createPurchasedPlan($this->developer, $rate_plan);

    $this->stack->queueMockResponse([
      'get_developer_purchased_plans' => [
        'purchased_plans' => [$purchased_plan],
      ],
    ]);

    // Check the manage Balance and plans link.
    $this->queueDeveloperResponse($this->developer);
    $this->clickLink('Balance and plans');
    $session->linkExists('Purchased plans');
    $session->linkExists('Prepaid balance');
    $session->linkExists('Billing Details');
    $this->assertCssElementContains('nav.tabs-wrapper li:nth-of-type(1) a', 'Purchased plans');
    $this->assertCssElementContains('nav.tabs-wrapper li:nth-of-type(2) a', 'Prepaid balance');
    $this->assertCssElementContains('nav.tabs-wrapper li:nth-of-type(3) a', 'Billing Details');
  }

}
