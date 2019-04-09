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

namespace Drupal\Tests\apigee_m10n\Kernel\Entity\ParamConverter;

use Drupal\apigee_m10n\Entity\ParamConverter\RatePlanConverter;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
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
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
  }

  /**
   * Test entities are converted.
   */
  public function testConvert() {
    // Create an admin user.
    $developer = $this->createAccount(array_keys(\Drupal::service('user.permissions')->getPermissions()));
    $this->setCurrentUser($developer);
    $api_package = $this->createPackage();
    $rate_plan = $this->createPackageRatePlan($api_package);

    $request = Request::create(Url::fromRoute('entity.rate_plan.canonical', [
      'user' => $developer->id(),
      'package' => $api_package->id(),
      'rate_plan' => $rate_plan->id(),
    ])->toString());

    // Match the request.
    \Drupal::service('router')->matchRequest($request);

    $this->assertEquals($rate_plan->id(), $request->get('rate_plan')->id());
  }

  /**
   * Test the route converter applies.
   */
  public function testApplies() {
    /** @var \Drupal\Core\ParamConverter\ParamConverterManagerInterface $converter_manager */
    $converter_manager = \Drupal::service('paramconverter_manager');

    $converter = $converter_manager->getConverter('paramconverter.entity.rate_plan');
    static::assertInstanceOf(RatePlanConverter::class, $converter);

    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');
    $route = $route_provider->getRouteByName('entity.rate_plan.subscribe');

    static::assertTrue($converter->applies(['type' => 'entity:rate_plan'], 'rate_plan', $route));
  }

}
