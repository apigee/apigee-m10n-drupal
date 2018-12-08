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

use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;

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
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function setUp() {
    parent::setUp();

    // If the user doesn't have the "view subscription" permission, they should
    // get access denied.
    $this->account = $this->createAccount([]);

    $this->queueOrg();

    $this->drupalLogin($this->account);
  }

  /**
   * Tests permissions for `My Plans/Subscriptions` page.
   */
  public function testSubscriptionListAccessDenied() {
    $this->drupalGet(Url::fromRoute('entity.subscription.collection_by_developer', [
      'user' => $this->account->id(),
    ]));

    $this->assertSession()->responseContains('Access denied');
  }

  /**
   * Tests for `My Plans/Subscriptions` page.
   *
   * @throws \Exception
   */
  public function testSubscriptionListView() {
    // Add the view subscription permission to the current user.
    $user_roles = $this->account->getRoles();
    $this->grantPermissions(Role::load(reset($user_roles)), ['view subscription']);

    $package = $this->createPackage();
    $rate_plan = $this->createPackageRatePlan($package);
    $subscription = $this->createsubscription($this->account, $rate_plan);

    $this->stack
      ->queueMockResponse(['get_developer_subscriptions' => ['subscriptions' => [$subscription]]])
      ->queueMockResponse(['get_package_rate_plan' => ['plan' => $rate_plan]]);

    $this->drupalGet(Url::fromRoute('entity.subscription.collection_by_developer', [
      'user' => $this->account->id(),
    ]));

    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my subscriptions table columns.
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.field-status', 'Future');
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.package-name', $rate_plan->getPackage()->getDisplayName());
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.products', $rate_plan->getPackage()->getApiProducts()[0]->getDisplayName());
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.rate-plan-name', $rate_plan->getDisplayName());
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.subscription-start-date', $subscription->getStartDate()->format('m/d/Y'));
    static::assertSame($this->cssSelect('.subscription-row:nth-child(1) td.subscription-end-date')[0]->getText(), '');

  }

}
