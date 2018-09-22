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
 * Class PrepaidBalanceTest
 *
 * @package Drupal\Tests\apigee_m10n\Functional
 * @group apigee_m10n
 */
class PrepaidBalanceTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var $account
   */
  protected $account;

  protected function setUp() {
    parent::setUp();
    $this->account = $this->createAccount([]);
    $this->drupalLogin($this->account);
  }

  public function testPrepaidBalances() {


//    $page = $this->drupalGet(Url::fromRoute('apigee_m10n.billing', [
//      'user_id' => 1
//    ]));

    $page = $this->drupalGet('users/me/monetization/billing');

    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('502 Bad Gateway');
    $this->assertSession()->responseContains('Page not found');
  }
}
