<?php

/*
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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Access;

use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;
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
   * Drupal anonymous user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymous;

  /**
   * Apigee X Product.
   *
   * @var \Drupal\apigee_m10n\Entity\XProductInterface
   */
  protected $xproduct;

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
    $this->setCurrentUser($this->developer);

    // Anonymous.
    $this->anonymous = new AnonymousUserSession();

    // Get pre-configured token storage service for testing.
    $this->storeToken();

    $this->stack->reset();
    $this->xproduct = $this->createApigeexProduct();
    $this->stack->reset();
    $this->xrate_plan = $this->createRatePlan($this->xproduct);
    $this->stack->reset();
  }

  /**
   * Run all assertions in this test class.
   */
  public function testAll() {
    $this->assertPermissionList();
    $this->assertAdminRoutes();
    $this->assertBuyApiRoutes();
    $this->assertProductRoutes();
    $this->assertRatePlanRoutes();
    $this->assertPurchasedProductRoutes();
  }

  /**
   * Tests purchased_product entity route permissions.
   */
  public function assertPurchasedProductRoutes() {
    // Warm the ApigeeX organization.
    $this->warmApigeexOrganizationCache();

    $this->stack->reset();
    $purchased_product = $this->createPurchasedProduct(User::load($this->administrator->id()), $this->xrate_plan);
    // Purchased plan listing.
    $collection_url = Url::fromRoute('entity.purchased_product.developer_product_collection', [
      'user' => $this->developer->id(),
    ]);
    static::assertTrue($collection_url->access($this->administrator));
    static::assertTrue($collection_url->access($this->developer));
    static::assertFalse($collection_url->access($this->anonymous));

    // Purchased plan listing for `any` permission.
    $collection_url = Url::fromRoute('entity.purchased_product.developer_product_collection', [
      'user' => $this->administrator->id(),
    ]);
    static::assertTrue($collection_url->access($this->administrator));
    static::assertFalse($collection_url->access($this->developer));
    static::assertFalse($collection_url->access($this->anonymous));

    // TODO: test the cancel page.
  }

  /**
   * Tests xrate_plan entity route permissions.
   */
  public function assertRatePlanRoutes() {
    // Rate plan canonical route.
    $plan_url = Url::fromRoute('entity.xrate_plan.canonical', [
      'user' => $this->developer->id(),
      'xproduct' => $this->xproduct->id(),
      'xrate_plan' => $this->xrate_plan->id(),
    ]);
    static::assertTrue($plan_url->access($this->administrator));
    static::assertTrue($plan_url->access($this->developer));
    static::assertFalse($plan_url->access($this->anonymous));

    // Rate plan canonical route for testing `any` permission.
    $plan_url = Url::fromRoute('entity.xrate_plan.canonical', [
      'user' => $this->administrator->id(),
      'xproduct' => $this->xproduct->id(),
      'xrate_plan' => $this->xrate_plan->id(),
    ]);
    static::assertTrue($plan_url->access($this->administrator));
    static::assertFalse($plan_url->access($this->developer));
    static::assertFalse($plan_url->access($this->anonymous));

    // Rate plan purchase route.
    $purchase_url = Url::fromRoute('entity.xrate_plan.purchase', [
      'user' => $this->developer->id(),
      'xproduct_' => $this->xproduct->id(),
      'xrate_plan' => $this->xrate_plan->id(),
    ]);
    static::assertTrue($purchase_url->access($this->administrator));
    static::assertTrue($purchase_url->access($this->developer));
    static::assertFalse($purchase_url->access($this->anonymous));

    // Rate plan purchase route for testing `any` permission.
    $purchase_url = Url::fromRoute('entity.xrate_plan.purchase', [
      'user' => $this->administrator->id(),
      'xproduct_' => $this->xproduct->id(),
      'xrate_plan' => $this->xrate_plan->id(),
    ]);
    static::assertTrue($purchase_url->access($this->administrator));
    static::assertFalse($purchase_url->access($this->developer));
    static::assertFalse($purchase_url->access($this->anonymous));
  }

  /**
   * Tests apigee X product entity route permissions.
   */
  public function assertProductRoutes() {
    // Developer route as developer.
    $xproduct_route = Url::fromRoute('entity.xproduct.developer',
      [
        'user' => $this->developer->id(),
        'xproduct' => $this->xproduct->id()
      ]
    );
    static::assertTrue($xproduct_route->access($this->administrator));
    static::assertFalse($xproduct_route->access($this->developer));
    static::assertFalse($xproduct_route->access($this->anonymous));

    // Developer route as Admin.
    $xproduct_route = Url::fromRoute('entity.xproduct.developer',
      [
        'user' => $this->administrator->id(),
        'xproduct' => $this->xproduct->id()
      ]
    );
    static::assertTrue($xproduct_route->access($this->administrator));
    static::assertFalse($xproduct_route->access($this->developer));
    static::assertFalse($xproduct_route->access($this->anonymous));
  }

  /**
   * Tests pricing and plans route permissions.
   */
  public function assertBuyApiRoutes() {
    // Test the redirect route.
    $redirect_route = Url::fromRoute('apigee_monetization.my_plans');
    static::assertTrue($redirect_route->access($this->administrator));
    static::assertTrue($redirect_route->access($this->developer));
    static::assertFalse($redirect_route->access($this->anonymous));

    // Collection route.
    $collection_route = Url::fromRoute('apigee_monetization.xplans', ['user' => $this->developer->id()]);
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
      Url::fromRoute('apigee_m10n.settings.rate_plan_x'),
      Url::fromRoute('apigee_m10n.settings.xproduct'),
      Url::fromRoute('apigee_m10n.settings.purchased_product'),
      Url::fromRoute('entity.xproduct.collection'),
    ];

    // Make sure only the admin account has access to all admin routes.
    foreach ($admin_routes as $route) {
      static::assertTrue($route->access($this->administrator));
      static::assertFalse($route->access($this->developer));
      static::assertFalse($route->access($this->anonymous));
    }
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
