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

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

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

    // Admin.
    $admin = $this->createAccount(array_keys(\Drupal::service('user.permissions')->getPermissions()));
    $this->administrator = new UserSession([
      'uid' => $admin->id(),
      'name' => $admin->getAccountName(),
      'roles' => $admin->getRoles(),
      'mail' => $admin->getEmail(),
    ]);
    // Developer.
    $developer = $this->createAccount([
      'view package',
    ]);
    $this->developer = new UserSession([
      'uid' => $developer->id(),
      'name' => $developer->getAccountName(),
      'roles' => $developer->getRoles(),
      'mail' => $developer->getEmail(),
    ]);
    // Anonymous.
    $this->anonymous = new AnonymousUserSession();

  }

  /**
   * Run all assertions in this test class.
   */
  public function testAll() {
    $this->assertPermissionList();
    $this->assertAdminRoutes();
    $this->assertPackageRoutes();
  }

  /**
   * Tests admin route permissions.
   */
  public function assertPackageRoutes() {
    // Test the redirect route.
    $redirect_route = Url::fromRoute('apigee_monetization.my_packages');
    static::assertTrue($redirect_route->access($this->administrator));
    static::assertTrue($redirect_route->access($this->developer));
    static::assertFalse($redirect_route->access($this->anonymous));

    // Collection route.
    $collection_route = Url::fromRoute('apigee_monetization.my_packages', ['user' => $this->developer->id()]);
    static::assertTrue($collection_route->access($this->administrator));
    static::assertTrue($collection_route->access($this->developer));
    static::assertFalse($collection_route->access($this->anonymous));

    // Create a package.
    $package = $this->createPackage();

    // Developer route as developer.
    $package_route = Url::fromRoute('entity.package.developer', ['user' => $this->developer->id(), 'package' => $package->id()]);
    $this->stack->queueMockResponse(['package' => ['package' => $package]]);
    static::assertTrue($package_route->access($this->administrator));
    static::assertTrue($package_route->access($this->developer));
    static::assertFalse($package_route->access($this->anonymous));

    // Developer route as developer.
    $package_route = Url::fromRoute('entity.package.developer', ['user' => $this->administrator->id(), 'package' => $package->id()]);
    $this->stack->queueMockResponse(['package' => ['package' => $package]]);
    static::assertTrue($package_route->access($this->administrator));
    static::assertFalse($package_route->access($this->developer));
    static::assertFalse($package_route->access($this->anonymous));

  }

  /**
   * Tests admin route permissions.
   */
  public function assertAdminRoutes() {
    /** @var \Drupal\Core\Url[] $admin_routes */
    $admin_routes = [
      Url::fromRoute('apigee_m10n.settings'),
      Url::fromRoute('apigee_m10n.settings.rate_plan'),
      Url::fromRoute('apigee_m10n.settings.package'),
      Url::fromRoute('apigee_m10n.settings.prepaid_balance'),
      Url::fromRoute('apigee_m10n.settings.subscription'),
      Url::fromRoute('entity.package.collection'),
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
      // Subscriptions.
      'update any subscription' => 'Cancel any purchased plan',
      'update own subscription' => 'Cancel own purchased plans',
      'view any subscription' => 'View any purchased plan',
      'view own subscription' => 'View own purchased plans',
      // Billing.
      'refresh any prepaid balance' => 'Refresh any prepaid balance',
      'refresh own prepaid balance' => 'Refresh own prepaid balance',
      'download prepaid balance reports' => 'Download prepaid balance reports',
      'view any billing details' => 'View any billing details',
      'view any prepaid balance' => 'View any prepaid balance',
      'view own billing details' => 'View own billing details',
      'view own prepaid balance' => 'View own prepaid balance',
      // Packages.
      'view package' => 'View packages',
      'view package as anyone' => 'View packages as any developer',
      // Rate plans.
      'view rate_plan' => 'View rate plans',
      'view rate_plan as anyone' => 'View rate plans',
      'subscribe rate_plan' => 'Purchase a rate plan',
      'subscribe rate_plan as anyone' => 'Purchase a rate plan as any developer',
    ];

    // Sort both for comparison.
    ksort($permissions);
    ksort($expected_permissions);
    static::assertSame($permissions, $expected_permissions);
  }

}
