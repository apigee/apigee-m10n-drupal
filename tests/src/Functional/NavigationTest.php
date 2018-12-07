<?php

/**
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

use Drupal\Core\Url;

/**
 * Functional test for navigation.
 *
 * @package Drupal\Tests\apigee_m10n\Functional
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class NavigationTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

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
    $this->account = $this->createAccount([
      'view mint prepaid reports',
      'access monetization packages',
      'view subscription',
    ]);

    $this->queueOrg();
    $this->drupalLogin($this->account);

    // Check the manage profile link.
    $this->clickLink('Manage profile');

    // Check the Pricing & Plans link.
    $session = $this->assertSession();
    $session->linkExists('Pricing & Plans');
    $session->linkExists('Manage profile');
    $session->linkExists('Prepaid Balance');
    $session->linkExists('Purchased Plans');

    $this->assertCssElementContains('.block-menu.navigation.menu--main ', 'Pricing & Plans');
    $this->assertCssElementContains('.block-menu.navigation.menu--account', 'Manage profile');
    $this->assertCssElementContains('nav.tabs', 'Prepaid Balance');
    $this->assertCssElementContains('nav.tabs', 'Purchased Plans');
  }

}
