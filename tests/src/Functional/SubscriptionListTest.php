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
 * Class SubscriptionListTest
 *
 * @group apigee_m10n
 */
class SubscriptionListTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var $account
   */
  protected $account;

  public function testSubscriptionListAccessDenied() {

    // If the user doesn't have the "access subscriptions" permission, they should get access denied.
    $this->account = $this->createAccount([]);

    $this->queueOrg();

    $this->drupalLogin($this->account);
    $this->drupalGet(Url::fromRoute('entity.subscription.collection_by_developer', [
      'user' => $this->account->id(),
    ]));

    $this->assertSession()->responseContains('Access denied');
  }

  public function testSubscriptionListView() {
    // If the user has "access subscriptions" permission, they should be able to see some prepaid balances.
    $this->account = $this->createAccount([
      'access subscriptions'
    ]);

    $this->queueOrg();

    $this->drupalLogin($this->account);

    $this->queueDeveloperResponse($this->account);

    $this->stack->queueFromResponseFile('get-subscriptions');

    $this->drupalGet(Url::fromRoute('entity.subscription.collection_by_developer', [
      'user' => $this->account->id(),
    ]));

//    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-aud > td:nth-child(1)', 'AUD');


  }
}
