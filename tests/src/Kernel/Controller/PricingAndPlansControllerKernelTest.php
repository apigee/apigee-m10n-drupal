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

use Drupal\apigee_edge\Entity\ApiProduct;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
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
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * A user that doesn't have access to do anything.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user_no_access;

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
      'apigee_m10n',
    ]);

    // Enable the Classy theme.
    \Drupal::service('theme_handler')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    $this->developer = $this->createAccount([
      'view package',
      'view own subscription',
      'view rate_plan',
    ]);

    $this->user_no_access = $this->createAccount();
  }

  /**
   * Tests the redirects for accessing plans for the current user.
   */
  public function testMyRedirects() {
    // Test a user with no access.
    $this->setCurrentUser($this->user_no_access);
    // Test the plans redirect.
    $request = Request::create(Url::fromRoute('apigee_monetization.my_plans')->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);
    // Make sure a 404 was returned.
    static::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // Test the plans redirect.
    $this->setCurrentUser($this->developer);
    $request = Request::create(Url::fromRoute('apigee_monetization.my_plans')->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    // Queue up a monetized org response.
    $this->stack->queueMockResponse('get_monetized_org');
    $response = $kernel->handle($request);
    static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    static::assertSame('http://localhost/user/' . $this->developer->id() . '/plans', $response->headers->get('location'));
  }

  /**
   * Tests the plan controller response.
   */
  public function testControllerResposne() {
    // Test a user with no access.
    $this->setCurrentUser($this->user_no_access);
    // Test the plans redirect.
    $request = Request::create(Url::fromRoute('apigee_monetization.plans', ['user' => $this->user_no_access->id()])->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    // Queue up a monetized org response.
    $response = $kernel->handle($request);
    // Make sure a 404 was returned.
    static::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // Set up packages and plans for the developer.
    $rate_plans = [];
    /** @var \Apigee\Edge\Api\Monetization\Entity\ApiPackage[] $packages */
    $packages = [
      $this->createPackage(),
      $this->createPackage(),
    ];
    // Some of the entities referenced will be loaded from the API unless we
    // warm the static cache with them.
    $entity_static_cache = \Drupal::service('entity.memory_cache');
    // Create a random number of rate plans for each package.
    foreach ($packages as $package) {
      // Warm the static cache for each package.
      $entity_static_cache->set("values:package:{$package->id()}", $package);
      // Warm the static cache for each package product.
      foreach ($package->decorated()->getApiProducts() as $product) {
        $entity_static_cache->set("values:api_product:{$product->id()}", ApiProduct::createFrom($product));
      }
      $rate_plans[$package->id()] = [];
      for ($i = rand(1, 3); $i > 0; $i--) {
        $rate_plans[$package->id()][] = $this->createPackageRatePlan($package);
      }
    }

    // Queue up a monetized org response.
    $this->stack->queueMockResponse('get_monetized_org');
    // Queue the package response.
    $this->stack->queueMockResponse(['get_monetization_packages' => ['packages' => $packages]]);
    foreach ($rate_plans as $package_id => $plans) {
      $this->stack->queueMockResponse(['get_monetization_package_plans' => ['plans' => $plans]]);
    }

    // Test the controller output for a user with plans.
    $this->setCurrentUser($this->developer);
    $request = Request::create(Url::fromRoute('apigee_monetization.plans', ['user' => $this->developer->id()])->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);
    $this->setRawContent($response->getContent());
    // Test the response.
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertTitle('Pricing and Plans | ');
    $rate_plan_css_index = 1;
    foreach ($rate_plans as $package_id => $plan_list) {
      foreach ($plan_list as $rate_plan) {
        $prefix = "div.rate-plan-list .apigee-package-rate-plan:nth-child({$rate_plan_css_index})";
        // Check the plan name.
        $this->assertCssElementText("{$prefix} .field--name-displayname a", $rate_plan->getDisplayName());
        // Check the plan description.
        $this->assertCssElementText("{$prefix} .field--name-description .field__item", $rate_plan->getDescription());
        // Check the package products.
        foreach ($rate_plan->get('packageProducts') as $index => $product) {
          $css_index = $index + 1;
          $this->assertCssElementText("{$prefix} .field--name-packageproducts .field__item:nth-child({$css_index})", $product->entity->getDisplayName());
        }
        // Check the purchase link.
        $this->assertLink('Purchase Plan', $rate_plan_css_index - 1);

        // Make sure undesired field are not shown.
        static::assertEmpty($this->cssSelect("{$prefix} .field--name-package"));
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
