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

namespace Drupal\Tests\apigee_m10n\Functional\Apigeex;

use Drupal\Core\Url;

/**
 * Class PurchasedProductStatusTest.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class PurchasedProductStatusTest extends MonetizationApigeexFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

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
  public function setUp() {
    parent::setUp();

    // Warm the ApigeeX organization.
    $this->warmApigeexOrganizationCache();

    // If the user doesn't have the "view own purchased_product" permission, they
    // should get access denied.
    $this->developer = $this->createAccount([]);

    $this->drupalLogin($this->developer);
  }

  /**
   * Tests for `Apigee X My Plans - Active Plans`.
   *
   * @throws \Exception
   */
  public function testPurchasedProductActiveStatus() {
    // Warm the ApigeeX organization.
    $this->warmApigeexOrganizationCache();

    $xproduct = $this->createApigeexProduct();
    $xrate_plan = $this->createRatePlan($xproduct);
    $purchased_product = $this->createPurchasedProduct($this->developer, $xrate_plan);

    $this->stack->queueMockResponse([
      'get_developer_purchased_products' => [
        'purchased_products' => [$purchased_product],
      ],
    ])->queueMockResponse([
      'get_monetization_apigeex_plans' => [
        'plans' => [$xrate_plan]
      ]
    ]);

    $this->drupalGet(Url::fromRoute('entity.purchased_product.developer_product_collection', [
      'user' => $this->developer->id(),
    ]));
    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my purchased plans table columns.
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-status', 'Active');

  }

  /**
   * Tests for `Apigee X My Plans - Expired Plans`.
   *
   * @throws \Exception
   */
  public function testPurchasedProductEndedStatus() {
    // Warm the ApigeeX organization.
    $this->warmApigeexOrganizationCache();

    $xproduct = $this->createApigeexProduct();
    $xrate_plan = $this->createRatePlan($xproduct);
    $purchased_product = $this->createPurchasedProduct($this->developer, $xrate_plan);

    // Ending the plan by setting the end time as today.
    $default_timezone = new \DateTimeZone($this->org_default_timezone);
    $currentTimestamp = (new \DateTimeImmutable())->setTimezone($default_timezone);
    $end_time = (int) ($currentTimestamp->getTimestamp() . $currentTimestamp->format('v'));

    $purchased_product->setEndTime($end_time);

    $this->stack->queueMockResponse([
      'get_developer_purchased_products' => [
        'purchased_products' => [$purchased_product],
      ],
    ])->queueMockResponse([
      'get_monetization_apigeex_plans' => [
        'plans' => [$xrate_plan]
      ]
    ]);

    $this->drupalGet(Url::fromRoute('entity.purchased_product.developer_product_collection', [
      'user' => $this->developer->id(),
    ]));
    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    // Checking my purchased products table columns.
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-status', 'Ended');

  }

}
