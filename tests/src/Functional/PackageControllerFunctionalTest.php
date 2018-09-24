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

/**
 * Functional tests for the package controller.
 *
 * @package Drupal\Tests\apigee_m10n\Functional
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Controller\PackagesController
 */
class PackageControllerFunctionalTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var $account
   */
  protected $account;

  /**
   * (@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->createAccount([
      'access monitization packages',
      'access purchased monitization packages',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Test the catalog page controller.
   * TODO: Add mock API responses and test package contents.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCatalogPage() {
    $this->drupalGet('user/'.$this->account->id().'/monetization/packages');

    $this->assertSession()->pageTextContains('Packages');
  }
}
