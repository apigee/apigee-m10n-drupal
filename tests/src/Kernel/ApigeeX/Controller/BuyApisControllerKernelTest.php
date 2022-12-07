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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Controller;

use Apigee\Edge\Api\ApigeeX\Entity\StandardRatePlan;
use Drupal\apigee_edge\Entity\ApiProduct;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for the buy apis controller.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class BuyApisControllerKernelTest extends MonetizationKernelTestBase {

  /**
   * Accounts to use for testing.
   *
   * @var array
   */
  private $accounts = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
    \Drupal::service('theme_installer')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    // Get pre-configured token storage service for testing.
    $this->storeToken();

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
    //$this->warmPurchasedProductCache($this->accounts['admin']);

    // Create user 2 as a developer.
    $this->accounts['developer'] = $this->createAccount([
      'view product_bundle',
      'view xproduct',
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
    $this->stack->reset();
    $this->stack->queueMockResponse('get_apigeex_organization');

    $this->assertRedirectNoAccess($this->accounts['anon']);
    $this->assertRedirectNoAccess($this->accounts['no_access']);
    $this->assertRedirect($this->accounts['admin']);
    $this->assertRedirect($this->accounts['developer']);
  }

  /**
   * Tests the plan controller response.
   */
  public function testControllerResponse() {
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
    // Set up X product and plans for the developer.
    $rate_plans = [];
    /** @var \Drupal\apigee_m10n\Entity\XProductInterface[] $xproducts */
    $xproducts = [];
    $this->stack->reset();
    $xproducts[] = $this->createApigeexProduct();
    $this->stack->reset();
    $xproducts[] = $this->createApigeexProduct();
    $this->stack->reset();

    // Some of the entities referenced will be loaded from the API unless we
    // warm the static cache with them.
    $entity_static_cache = \Drupal::service('entity.memory_cache');
    // Create a random number of rate plans for each X product.
    foreach ($xproducts as $xproduct) {
      // Warm the static cache for each product.
      $entity_static_cache->set("values:xproduct:{$xproduct->id()}", $xproduct);
      $this->stack->reset();
      // Warm the static cache for each product.
      $rate_plans[$xproduct->decorated()->id()] = [];
      $rate_plans[$xproduct->decorated()->id()]['standard'] = $this->createRatePlan($xproduct, RatePlanInterface::TYPE_STANDARD);
      $this->assertSame(StandardRatePlan::class, get_class($rate_plans[$xproduct->decorated()->id()]['standard']->decorated()));
    }

    // Queue the X product response.
    $this->stack->queueMockResponse(['get_monetization_apigeex_plans' => ['plans' => $rate_plans]]);
    $this->stack->queueMockResponse(['get_apigeex_monetization_package'=> ['xproducts' => $xproducts]]);
    // Test the controller output for a user that can purchase plans for others
    // but not subscribe to other developers plans.
    $this->setCurrentUser($this->accounts['admin']);
    $request = Request::create(Url::fromRoute('apigee_monetization.xplans', ['user' => $this->accounts['admin']->id()])
      ->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $response = $this->container->get('http_kernel')->handle($request);
    $this->setRawContent($response->getContent());

    // Test the response.
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertTitle('Buy APIs | ');
    foreach ($rate_plans as $product_bundle_id => $plans) {
      $this->assertSame(StandardRatePlan::class, get_class($plans['standard']->decorated()));
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
    static::assertSame('http://localhost/user/' . $user->id() . '/xplans', $response->headers->get('location'));
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
    $request = Request::create(Url::fromRoute('apigee_monetization.xplans', ['user' => $user->id()])
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

    // Set up X product and plans for the developer.
    $rate_plans = [];
    /** @var \Drupal\apigee_m10n\Entity\XProductInterface[] $xproducts */
    $xproducts = [];
    $this->stack->reset();
    $xproducts[] = $this->createApigeexProduct();
    $this->stack->reset();
    $xproducts[] = $this->createApigeexProduct();
    // Some of the entities referenced will be loaded from the API unless we
    // warm the static cache with them.
    $entity_static_cache = \Drupal::service('entity.memory_cache');
    // Create a random number of rate plans for each X product.
    foreach ($xproducts as $xproduct) {
      // Warm the static cache for each product.
      $entity_static_cache->set("values:xproduct:{$xproduct->id()}", $xproduct);
      // Warm the static cache for each product.
      $product = $xproduct->decorated();
      $entity_static_cache->set("values:api_product:{$product->id()}", ApiProduct::create([
        'id' => $product->id(),
        'name' => $product->getName(),
        'displayName' => $product->getDisplayName(),
        'description' => $product->getDescription(),
      ]));
      $this->stack->reset();
      $rate_plans[$xproduct->id()] = [];
      $rate_plans[$xproduct->id()] = $this->createRatePlan($xproduct);
    }

    // Queue the X product response.
    $this->stack->queueMockResponse(['get_monetization_apigeex_plans' => ['plans' => $rate_plans]]);
    $this->stack->queueMockResponse(['get_apigeex_monetization_package'=> ['xproducts' => $xproducts]]);
    // Test the controller output for a user with plans.
    $this->setCurrentUser($user);
    $request = Request::create(Url::fromRoute('apigee_monetization.xplans', ['user' => $user->id()])
      ->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);
    $this->setRawContent($response->getContent());
    // Test the response.
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertTitle('Buy APIs | ');
    $rate_plan_css_index = 1;
    $product = [];
    foreach ($rate_plans as $rate_plan) {
        $prefix = ".pricing-and-plans > .pricing-and-plans__item:nth-child({$rate_plan_css_index}) > .xrate-plan";
        // Check the rate plan x products.
        foreach ($xproducts as $xproduct) {
          $product = $xproduct->decorated();
          if ($product->getName() == $rate_plan->getApiProduct()) {
            // Check the plan name.
            $this->assertCssElementText("{$prefix} h2 a", $product->getDisplayName());
          }
        }
        // Make sure undesired field are not shown.
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-displayname"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-id"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-setupfees"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-recurringfees"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-paymentfundingmodel"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-endtime"));
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-starttime"));

        $rate_plan_css_index++;
    }
    // Clear cache as rate plan is cached.
    drupal_flush_all_caches();
  }
}
