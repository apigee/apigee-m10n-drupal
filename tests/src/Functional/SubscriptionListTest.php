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
  protected $developer;

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

    // If the user doesn't have the "view own subscription" permission, they should
    // get access denied.
    $this->developer = $this->createAccount([]);

    $this->drupalLogin($this->developer);

    // Set the default timezone for formatting the start  date.
    $subscription_default_display = \Drupal::config('core.entity_view_display.subscription.subscription.default')->get('content');
    $subscription_default_display['startDate']['settings']['timezone'] = 'America/Los_Angeles';
    \Drupal::configFactory()->getEditable('core.entity_view_display.subscription.subscription.default')->set('content', $subscription_default_display)->save();
  }

  /**
   * Tests permissions for `My Plans/Subscriptions` page.
   */
  public function testSubscriptionListAccessDenied() {
    $this->drupalGet(Url::fromRoute('entity.subscription.developer_collection', [
      'user' => $this->developer->id(),
    ]));

    $this->assertSession()->responseContains('Access denied');
  }

  /**
   * Tests for `My Plans/Subscriptions` page.
   *
   * @throws \Exception
   */
  public function testSubscriptionListView() {
    // Add the view own subscription permission to the current user.
    $user_roles = $this->developer->getRoles();
    $this->grantPermissions(Role::load(reset($user_roles)), ['view own subscription']);

    $package = $this->createPackage();
    $rate_plan = $this->createPackageRatePlan($package);
    $subscription = $this->createsubscription($this->developer, $rate_plan);

    $this->queueOrg();
    $this->stack
      ->queueMockResponse(['get_developer_subscriptions' => ['subscriptions' => [$subscription]]]);

    $this->drupalGet(Url::fromRoute('entity.subscription.developer_collection', [
      'user' => $this->developer->id(),
    ]));

    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my subscriptions table columns.
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.field-status', 'Future');
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.package-name', $rate_plan->getPackage()->getDisplayName());
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.products', $rate_plan->getPackage()->getApiProducts()[0]->getDisplayName());
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.rate-plan-name', $rate_plan->getDisplayName());
    $this->assertCssElementContains('.subscription-row:nth-child(1) td.subscription-start-date', $subscription->getStartDate()->setTimezone(new \DateTimeZone('America/Los_Angeles'))->format('m/d/Y'));
    static::assertSame($this->cssSelect('.subscription-row:nth-child(1) td.subscription-end-date')[0]->getText(), '');

  }

}
