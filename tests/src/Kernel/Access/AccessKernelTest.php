<?php

/*
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

namespace Drupal\Tests\apigee_m10n\Kernel\Access;

use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests access control for `apigee_m10n` routes.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class AccessKernelTest extends MonetizationKernelTestBase {

  /**
   * A Drupal admin user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $administrator;

  /**
   * Drupal developer user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $developer;

  /**
   * Drupal developer user account with billing type as POSTPAID.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $postpaid;

  /**
   * Drupal anonymous user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymous;

  /**
   * A product bundle.
   *
   * @var \Drupal\apigee_m10n\Entity\ProductBundleInterface
   */
  protected $product_bundle;

  /**
   * A rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
    ]);

    // Spend the user with ID = 1 to avoid so we are going just on permissions.
    $this->createAccount();

    // An admin.
    $admin = $this->createAccount(array_keys(\Drupal::service('user.permissions')->getPermissions()));
    $this->administrator = new UserSession([
      'uid' => $admin->id(),
      'name' => $admin->getAccountName(),
      'roles' => $admin->getRoles(),
      'mail' => $admin->getEmail(),
    ]);
    // Developer.
    $developer = $this->createAccount(MonetizationInterface::DEFAULT_AUTHENTICATED_PERMISSIONS);
    $this->developer = new UserSession([
      'uid' => $developer->id(),
      'name' => $developer->getAccountName(),
      'roles' => $developer->getRoles(),
      'mail' => $developer->getEmail(),
    ]);
    // Anonymous.
    $this->anonymous = new AnonymousUserSession();

    $this->product_bundle = $this->createProductBundle();
    $this->rate_plan = $this->createRatePlan($this->product_bundle);

    // Create a postpaid developer.
    $this->postpaid = $this->createAccount([
      'view product_bundle',
      'view own purchased_plan',
      'view rate_plan',
    ], TRUE, '', ['billing_type' => 'POSTPAID']);

    $this->prophesizeCurrentUser([]);
  }

  /**
   * Run all assertions in this test class.
   */
  public function testAll() {
    $this->assertPermissionList();
    $this->assertAdminRoutes();
    $this->assertPricingAndPlanRoutes();
    $this->assertProductBundleRoutes();
    $this->assertRatePlanRoutes();
    $this->assertPurchasedPlanRoutes();
    $this->assertBillingRoutes();
    $this->assertReportsRoute();
  }

  /**
   * Tests permissions for billing routes.
   */
  public function assertBillingRoutes() {
    // Own prepaid balance.
    $prepaid_balance_url = Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->developer->id(),
    ]);
    $this->queueDeveloperResponse(user_load_by_mail($this->developer->getEmail()));
    static::assertTrue($prepaid_balance_url->access($this->administrator));
    static::assertTrue($prepaid_balance_url->access($this->developer));
    static::assertFalse($prepaid_balance_url->access($this->anonymous));

    // Any prepaid balance.
    $prepaid_balance_url = Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->administrator->id(),
    ]);
    $this->queueDeveloperResponse(user_load_by_mail($this->administrator->getEmail()));
    static::assertTrue($prepaid_balance_url->access($this->administrator));
    static::assertFalse($prepaid_balance_url->access($this->developer));
    static::assertFalse($prepaid_balance_url->access($this->anonymous));

    // Prepaid balance pages should return access denied for a postpaid dev.
    $prepaid_balance_url = Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->postpaid->id(),
    ]);
    $this->queueDeveloperResponse(user_load_by_mail($this->postpaid->getEmail()));
    static::assertFalse($prepaid_balance_url->access($this->postpaid));
    static::assertFalse($prepaid_balance_url->access($this->developer));
    static::assertFalse($prepaid_balance_url->access($this->anonymous));

    // Own billing details.
    $billing_details_url = Url::fromRoute('apigee_monetization.profile', [
      'user' => $this->developer->id(),
    ]);
    static::assertTrue($billing_details_url->access($this->administrator));
    static::assertTrue($billing_details_url->access($this->developer));
    static::assertFalse($billing_details_url->access($this->anonymous));

    // Any billing details.
    $billing_details_url = Url::fromRoute('apigee_monetization.profile', [
      'user' => $this->administrator->id(),
    ]);
    static::assertTrue($billing_details_url->access($this->administrator));
    static::assertFalse($billing_details_url->access($this->developer));
    static::assertFalse($billing_details_url->access($this->anonymous));
  }

  /**
   * Tests purchased_plan entity route permissions.
   */
  public function assertPurchasedPlanRoutes() {
    // Cancel purchased plan route.
    $purchased_plan = $this->createPurchasedPlan(User::load($this->developer->id()), $this->rate_plan);
    $cancel_url = Url::fromRoute('entity.purchased_plan.developer_cancel_form', [
      'user' => $this->developer->id(),
      'purchased_plan' => $purchased_plan->id(),
    ]);
    static::assertTrue($cancel_url->access($this->administrator));
    static::assertTrue($cancel_url->access($this->developer));
    static::assertFalse($cancel_url->access($this->anonymous));

    // Cancel purchased plan route for testing `any` permission.
    $purchased_plan = $this->createPurchasedPlan(User::load($this->administrator->id()), $this->rate_plan);
    $cancel_url = Url::fromRoute('entity.purchased_plan.developer_cancel_form', [
      'user' => $this->administrator->id(),
      'purchased_plan' => $purchased_plan->id(),
    ]);
    static::assertTrue($cancel_url->access($this->administrator));
    static::assertFalse($cancel_url->access($this->developer));
    static::assertFalse($cancel_url->access($this->anonymous));

    // Purchased plan listing.
    $collection_url = Url::fromRoute('entity.purchased_plan.developer_collection', [
      'user' => $this->developer->id(),
    ]);
    static::assertTrue($collection_url->access($this->administrator));
    static::assertTrue($collection_url->access($this->developer));
    static::assertFalse($collection_url->access($this->anonymous));

    // Purchased plan listing for `any` permission.
    $collection_url = Url::fromRoute('entity.purchased_plan.developer_collection', [
      'user' => $this->administrator->id(),
    ]);
    static::assertTrue($collection_url->access($this->administrator));
    static::assertFalse($collection_url->access($this->developer));
    static::assertFalse($collection_url->access($this->anonymous));
  }

  /**
   * Tests rate_plan entity route permissions.
   */
  public function assertRatePlanRoutes() {
    // Rate plan canonical route.
    $plan_url = Url::fromRoute('entity.rate_plan.canonical', [
      'user' => $this->developer->id(),
      'product_bundle' => $this->product_bundle->id(),
      'rate_plan' => $this->rate_plan->id(),
    ]);
    static::assertTrue($plan_url->access($this->administrator));
    static::assertTrue($plan_url->access($this->developer));
    static::assertFalse($plan_url->access($this->anonymous));

    // Rate plan canonical route for testing `any` permission.
    $plan_url = Url::fromRoute('entity.rate_plan.canonical', [
      'user' => $this->administrator->id(),
      'product_bundle' => $this->product_bundle->id(),
      'rate_plan' => $this->rate_plan->id(),
    ]);
    static::assertTrue($plan_url->access($this->administrator));
    static::assertFalse($plan_url->access($this->developer));
    static::assertFalse($plan_url->access($this->anonymous));

    // Rate plan purchase route.
    $purchase_url = Url::fromRoute('entity.rate_plan.purchase', [
      'user' => $this->developer->id(),
      'product_bundle' => $this->product_bundle->id(),
      'rate_plan' => $this->rate_plan->id(),
    ]);
    static::assertTrue($purchase_url->access($this->administrator));
    static::assertTrue($purchase_url->access($this->developer));
    static::assertFalse($purchase_url->access($this->anonymous));

    // Rate plan purchase route for testing `any` permission.
    $purchase_url = Url::fromRoute('entity.rate_plan.purchase', [
      'user' => $this->administrator->id(),
      'product_bundle' => $this->product_bundle->id(),
      'rate_plan' => $this->rate_plan->id(),
    ]);
    static::assertTrue($purchase_url->access($this->administrator));
    static::assertFalse($purchase_url->access($this->developer));
    static::assertFalse($purchase_url->access($this->anonymous));
  }

  /**
   * Tests product bundle entity route permissions.
   */
  public function assertProductBundleRoutes() {
    // Create a product bundle.
    $product_bundle = $this->createProductBundle();

    // Developer route as developer.
    $product_bundle_route = Url::fromRoute('entity.product_bundle.developer', ['user' => $this->developer->id(), 'product_bundle' => $product_bundle->id()]);
    static::assertTrue($product_bundle_route->access($this->administrator));
    static::assertFalse($product_bundle_route->access($this->developer));
    static::assertFalse($product_bundle_route->access($this->anonymous));

    // Developer route as developer.
    $product_bundle_route = Url::fromRoute('entity.product_bundle.developer', ['user' => $this->administrator->id(), 'product_bundle' => $product_bundle->id()]);
    static::assertTrue($product_bundle_route->access($this->administrator));
    static::assertFalse($product_bundle_route->access($this->developer));
    static::assertFalse($product_bundle_route->access($this->anonymous));
  }

  /**
   * Tests pricing and plans route permissions.
   */
  public function assertPricingAndPlanRoutes() {
    // Test the redirect route.
    $redirect_route = Url::fromRoute('apigee_monetization.my_plans');
    static::assertTrue($redirect_route->access($this->administrator));
    static::assertTrue($redirect_route->access($this->developer));
    static::assertFalse($redirect_route->access($this->anonymous));

    // Collection route.
    $collection_route = Url::fromRoute('apigee_monetization.plans', ['user' => $this->developer->id()]);
    static::assertTrue($collection_route->access($this->administrator));
    static::assertTrue($collection_route->access($this->developer));
    static::assertFalse($collection_route->access($this->anonymous));
  }

  /**
   * Tests admin route permissions.
   */
  public function assertAdminRoutes() {
    /** @var \Drupal\Core\Url[] $admin_routes */
    $admin_routes = [
      Url::fromRoute('apigee_m10n.settings'),
      Url::fromRoute('apigee_m10n.settings.rate_plan'),
      Url::fromRoute('apigee_m10n.settings.product_bundle'),
      Url::fromRoute('apigee_m10n.settings.prepaid_balance'),
      Url::fromRoute('apigee_m10n.settings.purchased_plan'),
      Url::fromRoute('entity.product_bundle.collection'),
    ];

    // Make sure only the admin account has access to all admin routes.
    foreach ($admin_routes as $route) {
      static::assertTrue($route->access($this->administrator));
      static::assertFalse($route->access($this->developer));
      static::assertFalse($route->access($this->anonymous));
    }
  }

  /**
   * Tests permissions for billing routes.
   */
  public function assertReportsRoute() {
    // Own reports.
    $prepaid_balance_url = Url::fromRoute('apigee_monetization.reports', [
      'user' => $this->developer->id(),
    ]);
    static::assertTrue($prepaid_balance_url->access($this->administrator));
    static::assertTrue($prepaid_balance_url->access($this->developer));
    static::assertFalse($prepaid_balance_url->access($this->anonymous));

    // Any reports.
    $prepaid_balance_url = Url::fromRoute('apigee_monetization.reports', [
      'user' => $this->administrator->id(),
    ]);
    static::assertTrue($prepaid_balance_url->access($this->administrator));
    static::assertFalse($prepaid_balance_url->access($this->developer));
    static::assertFalse($prepaid_balance_url->access($this->anonymous));
  }

  /**
   * Tests that the permission list is correct.
   */
  public function assertPermissionList() {
    // Get all permissions.
    $all_permissions = \Drupal::service('user.permissions')->getPermissions();
    // Buffer for `apigee_m10n` permissions.
    $permissions = [];

    // Isoloate `apigee_m10n` permissions.
    array_walk($all_permissions, function ($permission, $permission_id) use (&$permissions) {
      if ($permission['provider'] === 'apigee_m10n') {
        $permissions[$permission_id] = (string) $permission['title'];
      }
    });

    $expected_permissions = [
      // Admin permission.
      'administer apigee monetization' => 'Administer Apigee Monetization',
      // Purchased plans.
      'update any purchased_plan' => 'Cancel any purchased plan',
      'update own purchased_plan' => 'Cancel own purchased plans',
      'view any purchased_plan' => 'View any purchased plan',
      'view own purchased_plan' => 'View own purchased plans',

      // Billing.
      'refresh any prepaid balance' => 'Refresh any prepaid balance',
      'refresh own prepaid balance' => 'Refresh own prepaid balance',
      'download prepaid balance reports' => 'Download prepaid balance reports',
      'view any billing details' => 'View any billing details',
      'view any prepaid balance' => 'View any prepaid balance',
      'view own billing details' => 'View own billing details',
      'view own prepaid balance' => 'View own prepaid balance',
      // Product bundles.
      'view product_bundle' => 'View product bundles',
      'view product_bundle as anyone' => 'View product bundles as any developer',
      // XProduct.
      'view xproduct' => 'View apigeex product',
      'view xproduct as anyone' => 'View apigeex product as any developer',
      // Rate plans.
      'view rate_plan' => 'View rate plans',
      'view rate_plan as anyone' => 'View rate plans as any developer',
      'purchase rate_plan' => 'Purchase a rate plan',
      'purchase rate_plan as anyone' => 'Purchase a rate plan as any developer',
      'download any reports' => 'Download any reports',
      'download own reports' => 'Download own reports',
    ];

    // Sort both for comparison.
    ksort($permissions);
    ksort($expected_permissions);
    static::assertSame($permissions, $expected_permissions);
  }

}
