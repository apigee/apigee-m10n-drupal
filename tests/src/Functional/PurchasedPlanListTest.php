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
 * Class PurchasedPlanListTest.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class PurchasedPlanListTest extends MonetizationFunctionalTestBase {

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

    // If the user doesn't have the "view own subscription" permission, they
    // should get access denied.
    $this->developer = $this->createAccount([]);

    $this->drupalLogin($this->developer);
  }

  /**
   * Tests for `My Plans` page.
   *
   * @throws \Exception
   */
  public function testPurchasedPlanListView() {
    $package = $this->createPackage();
    $rate_plan = $this->createPackageRatePlan($package);
    $purchased_plan = $this->createPurchasedPlan($this->developer, $rate_plan);

    $this->queueOrg();
    $this->stack
      ->queueMockResponse(['get_developer_purchased_plans' => ['purchased_plans' => [$purchased_plan]]]);

    $this->drupalGet(Url::fromRoute('entity.subscription.developer_collection', [
      'user' => $this->developer->id(),
    ]));

    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my subscriptions table columns.
    $this->assertCssElementText('.subscription-row:nth-child(1) td.subscription-status', 'Active');
    $this->assertCssElementText('.subscription-row:nth-child(1) td.subscription-rate-plan', $rate_plan->getDisplayName());
    $this->assertCssElementText('.subscription-row:nth-child(1) td.subscription-start-date', $purchased_plan->getStartDate()->format('m/d/Y'));
    static::assertSame($this->cssSelect('.subscription-row:nth-child(1) td.subscription-end-date')[0]->getText(), '');

  }

}
