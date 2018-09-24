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

  public function testPrepaidBalancesAccessDenied() {

    // If the user doesn't have the "view mint prepaid reports" permission, they should get access denied.
    $this->account = $this->createAccount([]);
    $this->drupalLogin($this->account);

    $this->drupalGet(Url::fromRoute('apigee_monitization.billing', [
      'user_id' => 58
    ]));

    $this->assertSession()->statusCodeEquals(403);
  }

  public function testPrepaidBalancesAccessGranted() {

    // If the user does have the "view mint prepaid reports" permission, they should get a 200 response.
    $this->account = $this->createAccount([
      'view mint prepaid reports'
    ]);
    $this->drupalLogin($this->account);

    $this->drupalGet(Url::fromRoute('apigee_monitization.billing', [
      'user_id' => 58
    ]));

    $this->assertSession()->statusCodeEquals(200);
  }

  public function testPrepaidBalancesView() {

    if (!$this->integration_enabled) {
      $this->stack->queueFromResponseFile(['get_prepaid_balances']);
      $this->stack->queueFromResponseFile(['get_prepaid_balances']);
      $this->stack->queueFromResponseFile(['get_prepaid_balances']);
      $this->stack->queueFromResponseFile(['get_prepaid_balances']);
    }

    // If the user has "view mint prepaid reports" permission, they should be able to see some prepaid balances.
    $this->account = $this->createAccount([
      'view mint prepaid reports'
    ]);
    $this->drupalLogin($this->account);

    $this->drupalGet(Url::fromRoute('apigee_monitization.billing', [
      // If real integration tests are enabled, use a user id that exists on the dev site,
      // otherwise use the generated account id.
      'user_id' => $this->integration_enabled ? 58 : $this->account->id()
    ]));

    $this->assertSession()->responseNotContains('Access denied');
  }
}
