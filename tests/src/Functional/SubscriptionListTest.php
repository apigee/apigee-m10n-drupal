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
 * Class SubscriptionListTest.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class SubscriptionListTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Tests permissions for `My Plans/Subscriptions` page.
   */
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

  /**
   * Tests for `My Plans/Subscriptions` page.
   */
  public function testSubscriptionListView() {
    // If the user has "access subscriptions" permission, they should be able to see some prepaid balances.
    $this->account = $this->createAccount([
      'access subscriptions'
    ]);

    $this->queueOrg();

    $this->drupalLogin($this->account);

    $this->stack->queueMockResponse('get_subscriptions');

    $this->drupalGet(Url::fromRoute('entity.subscription.collection_by_developer', [
      'user' => $this->account->id(),
    ]));

    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');

    // Checking my subscriptions table columns.
    $this->assertSession()->elementTextContains('css', 'tr.foo-displayname > td:nth-child(1)', 'Future');
    $this->assertSession()->elementTextContains('css', 'tr.foo-displayname > td:nth-child(2)', 'The Foo');
    $this->assertSession()->elementTextContains('css', 'tr.foo-displayname > td:nth-child(3)', '');
    $this->assertSession()->elementContains('css', 'tr.foo-displayname > td:nth-child(4)', '<a href="/user/' . $this->account->id() . '/monetization/packages/');
    $this->assertSession()->elementTextContains('css', 'tr.foo-displayname > td:nth-child(5)', '12/04/2018');
    $this->assertSession()->elementTextContains('css', 'tr.foo-displayname > td:nth-child(5)', '12/04/2018');

  }

}
