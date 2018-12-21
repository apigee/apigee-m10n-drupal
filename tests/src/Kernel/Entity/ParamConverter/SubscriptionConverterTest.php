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

use Drupal\apigee_m10n\Entity\ParamConverter\SubscriptionConverter;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Tests the subscription param converter.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class SubscriptionConverterTest extends MonetizationKernelTestBase {

  /**
   * Test entities are converted.
   */
  public function testConvert() {
  }

  /**
   * Test the route converter applies.
   */
  public function testApplies() {
    /** @var \Drupal\Core\ParamConverter\ParamConverterManagerInterface $converter_manager */
    $converter_manager = \Drupal::service('paramconverter_manager');

    $converter = $converter_manager->getConverter('paramconverter.entity.subscription');
    static::assertInstanceOf(SubscriptionConverter::class, $converter);

    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');
    $route = $route_provider->getRouteByName('entity.subscription.unsubscribe_form');

    static::assertTrue($converter->applies(['type' => 'entity:subscription'], 'subscription', $route));
  }

}
