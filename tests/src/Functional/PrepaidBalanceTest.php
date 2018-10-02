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

    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
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

    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user_id' => 58
    ]));

    $this->assertSession()->statusCodeEquals(200);
  }

  public function testPrepaidBalancesView() {

    $this->stack->queueFromResponseFile(['post_developers']);

    // If the user has "view mint prepaid reports" permission, they should be able to see some prepaid balances.
    $this->account = $this->createAccount([
      'view mint prepaid reports'
    ]);

    $this->stack->queueFromResponseFile(['get_organization' => [
      'org_name' => 'tsnow-mint',
      'isMonetizationEnabled' => 'true'
    ]]);

    $this->drupalLogin($this->account);

    $this->stack->queueFromResponseFile(['get-prepaid-balances' => [
      "currentBalance" => 72.2000,
      "currentTotalBalance" => 120.0200,
      "currentUsage" => 47.8200,
      "previousBalance" => 0,
      "topups" => 30.0200,
      "usage" => 47.8200
    ]]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->account->id()
    ]));

    $this->assertSession()->responseNotContains('Access denied');

    $this->assertSession()->responseContains("<td>AUD</td>");
    $this->assertSession()->responseContains("<td>A$0.00</td>");
    $this->assertSession()->responseContains("<td>A$30.02</td>");
    $this->assertSession()->responseContains("<td>A$47.82</td>");
    $this->assertSession()->responseContains("<td>A$0.00</td>");

    $this->assertSession()->responseContains("<td>USD</td>");
    $this->assertSession()->responseContains("<td>$0.00</td>");
    $this->assertSession()->responseContains("<td>$30.02</td>");
    $this->assertSession()->responseContains("<td>$47.82</td>");
    $this->assertSession()->responseContains("<td>$0.00</td>");
  }
}
