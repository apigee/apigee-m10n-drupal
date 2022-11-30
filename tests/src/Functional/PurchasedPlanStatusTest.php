<?php

/**
 * Copyright 2021 Google Inc.
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
 * Class PurchasedPlanStatusTest.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class PurchasedPlanStatusTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * A product bundle.
   *
   * @var \Drupal\apigee_m10n\Entity\ProductBundleInterface
   */
  protected $productBundle;

  /**
   * A rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $ratePlan;

  /**
   * The default org timezone.
   *
   * @var string
   */
  protected $orgDefaultTimezone = 'America/Los_Angeles';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function setUp(): void {
    parent::setUp();

    // If the user doesn't have the "view own purchased_plan" permission, they
    // should get access denied.
    $this->developer = $this->createAccount([]);

    $this->drupalLogin($this->developer);

    $this->productBundle = $this->createProductBundle();
    $this->ratePlan = $this->createRatePlan($this->productBundle);
  }

  /**
   * Tests for `My Plans - Active Plans`.
   *
   * @throws \Exception
   */
  public function testPurchasedPlanActiveStatus() {
    $purchased_plan = $this->createPurchasedPlan($this->developer, $this->ratePlan);

    $this->stack
      ->queueMockResponse(['get_developer_purchased_plans' => ['purchased_plans' => [$purchased_plan]]]);

    $this->drupalGet(Url::fromRoute('entity.purchased_plan.developer_collection', [
      'user' => $this->developer->id(),
    ]));
    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my purchased plans table columns.
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-status', 'Active');

  }

  /**
   * Tests for `My Plans - Expired Plans`.
   *
   * @throws \Exception
   */
  public function testPurchasedPlanEndedStatus() {
    $purchased_plan = $this->createPurchasedPlan($this->developer, $this->ratePlan);

    // Ending the plan by setting the end date as today.
    $end_date = $purchased_plan->getStartDate();
    $end_date->setTimezone($purchased_plan->getRatePlan()->getOrganization()->getTimezone());
    $purchased_plan->setEndDate($end_date);

    $this->stack
      ->queueMockResponse(['get_developer_purchased_plans' => ['purchased_plans' => [$purchased_plan]]]);

    $this->drupalGet(Url::fromRoute('entity.purchased_plan.developer_collection', [
      'user' => $this->developer->id(),
    ]));
    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my purchased plans table columns.
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-status', 'Ended');

  }

  /**
   * Tests for `My Plans - Future plan`.
   *
   * @throws \Exception
   */
  public function testPurchasedPlanGetFutureStatus() {
    $purchased_plan = $this->createPurchasedPlan($this->developer, $this->ratePlan);

    // Purchased plan with future date.
    $start_date = new \DateTimeImmutable('today +2 day', new \DateTimeZone($this->orgDefaultTimezone));
    $purchased_plan->setStartDate($start_date);

    $this->stack
      ->queueMockResponse(['get_developer_purchased_plans' => ['purchased_plans' => [$purchased_plan]]]);

    $this->drupalGet(Url::fromRoute('entity.purchased_plan.developer_collection', [
      'user' => $this->developer->id(),
    ]));
    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my purchased plans table columns.
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-status', 'Future');

  }

  /**
   * Tests for `My Plans - Active plans with timezone different than Apigee's timezone`.
   *
   * @throws \Exception
   */
  public function testActivePurchasedPlanWithChangedTimezone() {
    $original_timezone = date_default_timezone_get();
    $currentTimezone = 'Australia/Sydney';
    // We change the timezone before we would do anything else to ensure
    // any subsequent calls as working properly.
    date_default_timezone_set($currentTimezone);

    $purchased_plan = $this->createPurchasedPlan($this->developer, $this->ratePlan);

    $this->stack
      ->queueMockResponse(['get_developer_purchased_plans' => ['purchased_plans' => [$purchased_plan]]]);

    $this->drupalGet(Url::fromRoute('entity.purchased_plan.developer_collection', [
      'user' => $this->developer->id(),
    ]));
    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my purchased plans table columns.
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-status', 'Active');

    // Restoring the original timezone.
    date_default_timezone_set($original_timezone);

  }

  /**
   * Tests for `My Plans - Expired plan with timezone different than Apigee's timezone`.
   *
   * @throws \Exception
   */
  public function testExpiredPurchasedPlanWithChangedTimezone() {
    $original_timezone = date_default_timezone_get();
    $currentTimezone = 'Australia/Sydney';
    // We change the timezone before we would do anything else to ensure
    // any subsequent calls as working properly.
    date_default_timezone_set($currentTimezone);

    $purchased_plan = $this->createPurchasedPlan($this->developer, $this->ratePlan);

    // Ending the plan by setting the end date as today.
    $end_date = $purchased_plan->getStartDate();
    $end_date->setTimezone($purchased_plan->getRatePlan()->getOrganization()->getTimezone());
    $purchased_plan->setEndDate($end_date);

    $this->stack
      ->queueMockResponse(['get_developer_purchased_plans' => ['purchased_plans' => [$purchased_plan]]]);

    $this->drupalGet(Url::fromRoute('entity.purchased_plan.developer_collection', [
      'user' => $this->developer->id(),
    ]));
    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my purchased plans table columns.
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-status', 'Ended');

    // Restoring the original timezone.
    date_default_timezone_set($original_timezone);
  }

  /**
   * Tests for `My Plans - Future plan with timezone different than Apigee's timezone`.
   *
   * @throws \Exception
   */
  public function testFuturePurchasedPlanWithChangedTimezone() {
    $original_timezone = date_default_timezone_get();
    $currentTimezone = 'Australia/Sydney';
    // We change the timezone before we would do anything else to ensure
    // any subsequent calls as working properly.
    date_default_timezone_set($currentTimezone);

    $purchased_plan = $this->createPurchasedPlan($this->developer, $this->ratePlan);

    // Purchased plan with future date.
    $start_date = new \DateTimeImmutable('today +2 day', new \DateTimeZone($this->orgDefaultTimezone));
    $purchased_plan->setStartDate($start_date);

    $this->stack
      ->queueMockResponse(['get_developer_purchased_plans' => ['purchased_plans' => [$purchased_plan]]]);

    $this->drupalGet(Url::fromRoute('entity.purchased_plan.developer_collection', [
      'user' => $this->developer->id(),
    ]));
    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my purchased plans table columns.
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-status', 'Future');

    // Restoring the original timezone.
    date_default_timezone_set($original_timezone);
  }

}
