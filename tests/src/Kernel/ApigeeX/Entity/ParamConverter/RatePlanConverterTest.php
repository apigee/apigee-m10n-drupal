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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Entity\ParamConverter;

use Drupal\apigee_m10n\Entity\ParamConverter\XRatePlanConverter;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the rate plan param converter.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class RatePlanConverterTest extends MonetizationKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
    // Get pre-configured token storage service for testing.
    $this->storeToken();
  }

  /**
   * Test entities are converted.
   */
  public function testConvert() {
    // Create an admin user.
    $developer = $this->createAccount(array_keys(\Drupal::service('user.permissions')->getPermissions()));
    $this->setCurrentUser($developer);
    $this->stack->reset();
    $xproduct = $this->createApigeexProduct();
    $this->stack->reset();
    $rate_plan = $this->createRatePlan($xproduct);

    $request = Request::create(Url::fromRoute('entity.xrate_plan.canonical', [
      'user' => $developer->id(),
      'xproduct' => $xproduct->id(),
      'xrate_plan' => $rate_plan->id(),
    ])->toString());

    // Match the request.
    \Drupal::service('router')->matchRequest($request);

    $this->assertEquals($rate_plan->id(), $request->get('xrate_plan')->id());

    // Tests an invalid X product.
    $request = Request::create(Url::fromRoute('entity.xrate_plan.canonical', [
      'user' => $developer->id(),
      'xproduct' => 0,
      'xrate_plan' => $rate_plan->id(),
    ])->toString());
    // Match the request.
    try {
      \Drupal::service('router')->matchRequest($request);
    }
    catch (CacheableNotFoundHttpException $ex) {
    }

    // Tests rate plan not found exception.
    $this->stack->reset();
    $plan_id = $this->randomMachineName();
    $request = Request::create(Url::fromRoute('entity.xrate_plan.canonical', [
      'user' => $developer->id(),
      'xproduct' => $this->randomMachineName(),
      'xrate_plan' => $plan_id,
    ])->toString());
    // Queue a not found response.
    $this->stack->queueMockResponse([
      'get_not_found'  => [
        'status_code' => 404,
        'message' => "RatePlan with id [{$plan_id}] does not exist",
      ],
    ]);
    // Match the request.
    try {
      \Drupal::service('router')->matchRequest($request);
    }
    catch (ParamNotConvertedException $ex) {
    }
  }

  /**
   * Test the route converter applies.
   */
  public function testApplies() {
    /** @var \Drupal\Core\ParamConverter\ParamConverterManagerInterface $converter_manager */
    $converter_manager = \Drupal::service('paramconverter_manager');

    $converter = $converter_manager->getConverter('paramconverter.entity.xrate_plan');
    static::assertInstanceOf(XRatePlanConverter::class, $converter);

    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');
    $route = $route_provider->getRouteByName('entity.xrate_plan.purchase');

    static::assertTrue($converter->applies(['type' => 'entity:xrate_plan'], 'xrate_plan', $route));
  }

}
