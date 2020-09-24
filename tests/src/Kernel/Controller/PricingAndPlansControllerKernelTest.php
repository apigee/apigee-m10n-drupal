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

namespace Drupal\Tests\apigee_m10n\Kernel\Controller;

use Apigee\Edge\Api\Monetization\Entity\Developer;
use Apigee\Edge\Api\Monetization\Entity\DeveloperRatePlan;
use Apigee\Edge\Api\Monetization\Entity\StandardRatePlan;
use Drupal\apigee_edge\Entity\ApiProduct;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for the pricing and plans controller.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class PricingAndPlansControllerKernelTest extends MonetizationKernelTestBase {

  /**
   * Accounts to use for testing.
   *
   * @var array
   */
  private $accounts = [];

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
      'system',
      'apigee_m10n',
    ]);

    // Enable the Classy theme.
    \Drupal::service('theme_handler')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    // User install is going to try to create a developer for the root user.
    $this->stack->queueMockResponse([
      'get_not_found' => [
        'status_code' => 404,
        'code' => 'developer.service.DeveloperIdDoesNotExist',
        'message' => 'DeveloperId v1 does not exist in organization foo-org',
      ],
    ])->queueMockResponse([
      'get_developer_mint' => [
        'status_code' => 201,
      ],
    ])->queueMockResponse([
      // The call to save happens twice in a row because of `setStatus()`.
      // See: \Drupal\apigee_edge\Entity\Storage\DeveloperStorage::doSave()`.
      'get_developer_mint' => [
        'status_code' => 201,
      ],
    ]);
    // Install user 0 and user 1. Workaround because or `user_install()` errors.
    User::create([
      'uid' => 0,
      'status' => 0,
      'name' => '',
      'mail' => 'prevent-apigee_edge_user_presave-error',
    ])->save();
    Database::getConnection()->update('users_field_data')->fields(['mail' => NULL])->condition('uid', 0)->execute();
    User::create([
      'uid' => 1,
      'name' => 'placeholder-for-uid-1',
      'mail' => 'placeholder-for-uid-1',
      'status' => TRUE,
    ])->save();

    $this->accounts['anon'] = User::load(0);
    $this->accounts['admin'] = User::load(1);
    // Assume admin has no purchased plans initially.
    $this->warmPurchasedPlanCache($this->accounts['admin']);

    // Create user 2 as a developer.
    $this->accounts['developer'] = $this->createAccount([
      'view product_bundle',
      'view own purchased_plan',
      'view rate_plan',
    ]);
    // Create user 3 as a user with no permissions.
    $this->accounts['no_access'] = $this->createAccount();
  }

  /**
   * Tests the redirects for accessing plans for the current user.
   */
  public function testMyRedirects() {
    // Queue up a monetized org response.
    $this->stack->queueMockResponse('get_monetized_org');

    $this->assertRedirectNoAccess($this->accounts['anon']);
    $this->assertRedirectNoAccess($this->accounts['no_access']);
    $this->assertRedirect($this->accounts['admin']);
    $this->assertRedirect($this->accounts['developer']);
  }

  /**
   * Tests the plan controller response.
   */
  public function testControllerResponse() {
    // Queue up a monetized org response.
    $this->stack->queueMockResponse('get_monetized_org');
    // Warm the cache for the monetized org check.
    \Drupal::service('apigee_m10n.monetization')->isMonetizationEnabled();

    $this->assertPlansNoAccess($this->accounts['anon']);
    $this->assertPlansNoAccess($this->accounts['no_access']);
    $this->assertPlansPage($this->accounts['developer']);
    $this->assertPlansPage($this->accounts['admin']);
  }

  /**
   * Check plan filtering.
   *
   * Check that only plans that developer in the route can subscribe to are
   * displayed on the page.
   */
  public function testPlansFiltering() {
    $this->warmOrganizationCache();

    $rate_plans = [];
    $product_bundles = [
      $this->createProductBundle(),
      $this->createProductBundle(),
    ];

    foreach ($product_bundles as $product_bundle) {
      $rate_plans[$product_bundle->id()] = [];
      $rate_plans[$product_bundle->id()]['standard'] = $this->createRatePlan($product_bundle, RatePlanInterface::TYPE_STANDARD);
      $rate_plans[$product_bundle->id()]['developer'] = $this->createRatePlan($product_bundle, RatePlanInterface::TYPE_DEVELOPER, NULL, [
        'developer' => new Developer([
          'email' => $this->accounts['developer']->getEmail(),
          'name' => $this->accounts['developer']->getDisplayName(),
          'billingType' => 'PREPAID',
          'type' => 'UNTRUSTED',
        ]),
      ]);

      $this->assertSame(StandardRatePlan::class, get_class($rate_plans[$product_bundle->id()]['standard']->decorated()));
      $this->assertSame(DeveloperRatePlan::class, get_class($rate_plans[$product_bundle->id()]['developer']->decorated()));
    }

    // Queue the product bundle response.
    $this->stack->queueMockResponse(['get_monetization_packages' => ['packages' => $product_bundles]]);
    foreach ($rate_plans as $product_bundle_id => $plans) {
      $this->stack->queueMockResponse(['get_monetization_package_plans' => ['plans' => $plans]]);
    }
    foreach ($rate_plans as $product_bundle_id => $plans) {
      foreach ($plans as $plan) {
        if (get_class($plan->decorated()) == DeveloperRatePlan::class) {
          /* @var DeveloperRatePlan $decorated */
          $decorated = $plan->decorated();
          $this->assertSame($this->accounts['developer']->getEmail(), $decorated->getDeveloper()->getEmail(), 'Email is the same');
        }
      }
    }

    // Test the controller output for a user that can purchase plans for others
    // but not subscribe to other developers plans.
    $this->setCurrentUser($this->accounts['admin']);
    $request = Request::create(Url::fromRoute('apigee_monetization.plans', ['user' => $this->accounts['admin']->id()])
      ->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->setRawContent($response->getContent());

    // Test the response.
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertTitle('Pricing and Plans | ');
    foreach ($rate_plans as $product_bundle_id => $plans) {
      $this->assertText($plans['standard']->label());
      $this->assertNoText($plans['developer']->label());
    }

    // Test the same but for a different developer context.

    // Queue the product bundle response.
    $this->stack->queueMockResponse(['get_monetization_packages' => ['packages' => $product_bundles]]);
    foreach ($rate_plans as $product_bundle_id => $plans) {
      $this->stack->queueMockResponse(['get_monetization_package_plans' => ['plans' => $plans]]);
    }
    foreach ($rate_plans as $product_bundle_id => $plans) {
      foreach ($plans as $plan) {
        if (get_class($plan->decorated()) == DeveloperRatePlan::class) {
          /* @var DeveloperRatePlan $decorated */
          $decorated = $plan->decorated();
          $this->assertSame($this->accounts['developer']->getEmail(), $decorated->getDeveloper()->getEmail(), 'Email is the same');
        }
      }
    }

    $request = Request::create(Url::fromRoute('apigee_monetization.plans', ['user' => $this->accounts['developer']->id()])
      ->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->setRawContent($response->getContent());

    // Test the response.
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertTitle('Pricing and Plans | ');
    foreach ($rate_plans as $product_bundle_id => $plans) {
      $this->assertText($plans['standard']->label());
      $this->assertText($plans['developer']->label());
    }
  }

  /**
   * Assert a user cannot access a redirect link.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @throws \Exception
   */
  protected function assertRedirectNoAccess(UserInterface $user) {
    // Test a user with no access.
    $this->setCurrentUser($user);
    // Test the plans redirect.
    $request = Request::create(Url::fromRoute('apigee_monetization.my_plans')
      ->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);
    // Make sure a 404 was returned.
    static::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
  }

  /**
   * Assert a user CAN access a redirect link.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @throws \Exception
   */
  protected function assertRedirect(UserInterface $user) {
    // Test the plans redirect.
    $this->setCurrentUser($user);
    $request = Request::create(Url::fromRoute('apigee_monetization.my_plans')
      ->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);
    static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    static::assertSame('http://localhost/user/' . $user->id() . '/plans', $response->headers->get('location'));
  }

  /**
   * Assert a user cannot access the plans page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @throws \Exception
   */
  protected function assertPlansNoAccess(UserInterface $user) {
    // Test a user with no access.
    $this->setCurrentUser($user);
    // Test the plans redirect.
    $request = Request::create(Url::fromRoute('apigee_monetization.plans', ['user' => $user->id()])
      ->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);
    // Make sure a 404 was returned.
    static::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
  }

  /**
   * Assert plans page response for a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @throws \Exception
   */
  protected function assertPlansPage(UserInterface $user) {

    // Set up product bundles and plans for the developer.
    $rate_plans = [];
    /** @var \Drupal\apigee_m10n\Entity\ProductBundleInterface[] $product_bundles */
    $product_bundles = [
      $this->createProductBundle(),
      $this->createProductBundle(),
    ];
    // Some of the entities referenced will be loaded from the API unless we
    // warm the static cache with them.
    $entity_static_cache = \Drupal::service('entity.memory_cache');
    // Create a random number of rate plans for each product bundle.
    foreach ($product_bundles as $product_bundle) {
      // Warm the static cache for each product bundle.
      $entity_static_cache->set("values:product_bundle:{$product_bundle->id()}", $product_bundle);
      // Warm the static cache for each product bundle product.
      foreach ($product_bundle->decorated()->getApiProducts() as $product) {
        $entity_static_cache->set("values:api_product:{$product->id()}", ApiProduct::create([
          'id' => $product->id(),
          'name' => $product->getName(),
          'displayName' => $product->getDisplayName(),
          'description' => $product->getDescription(),
        ]));
      }
      $rate_plans[$product_bundle->id()] = [];
      for ($i = rand(1, 3); $i > 0; $i--) {
        $rate_plans[$product_bundle->id()][] = $this->createRatePlan($product_bundle);
      }
    }

    // Queue the product bundle response.
    $this->stack->queueMockResponse(['get_monetization_packages' => ['packages' => $product_bundles]]);
    foreach ($rate_plans as $product_bundle_id => $plans) {
      $this->stack->queueMockResponse(['get_monetization_package_plans' => ['plans' => $plans]]);
    }

    // Test the controller output for a user with plans.
    $this->setCurrentUser($user);
    $request = Request::create(Url::fromRoute('apigee_monetization.plans', ['user' => $user->id()])
      ->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);
    $this->setRawContent($response->getContent());
    // Test the response.
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertTitle('Pricing and Plans | ');
    $rate_plan_css_index = 1;
    foreach ($rate_plans as $product_bundle_id => $plan_list) {
      foreach ($plan_list as $rate_plan) {
        $prefix = ".pricing-and-plans > .pricing-and-plans__item:nth-child({$rate_plan_css_index}) > .rate-plan";
        // Check the plan name.
        $this->assertCssElementText("{$prefix} h2 a", $rate_plan->getDisplayName());
        // Check the product bundle products.
        foreach ($rate_plan->get('products') as $index => $product) {
          $css_index = $index + 1;
          $this->assertCssElementText("{$prefix} .field--name-products .field__item:nth-child({$css_index})", $product->entity->getDisplayName());
        }
        // Check the purchase link.
        $this->assertLink('Purchase Plan', $rate_plan_css_index - 1);

        // Make sure undesired field are not shown.
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-productbundle"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-name"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-currency"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-id"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-organization"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-rateplandetails"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-enddate"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-startdate"));

        $rate_plan_css_index++;
      }
    }
  }

}
