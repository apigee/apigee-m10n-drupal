<?php

/**
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

namespace Drupal\Tests\apigee_m10n\Functional;

use Drupal\apigee_m10n\Monetization;

/**
 * Tests the monetization enable check on routes.
 *
 * @package Drupal\Tests\apigee_m10n\Functional
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 *
 * @coversDefaultClass \Drupal\apigee_m10n\EventSubscriber\ValidateMonetizationEnabledSubscriber
 */
class RouteValidateMonetizationEnabledFunctionalTest extends MonetizationFunctionalTestBase {

  /**
   * Tests routes when monetization is disabled.
   *
   * @dataProvider routes
   */
  public function testRoutesWithMonetizationDisabled($route_name, $is_monetizable) {
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');

    if ($route = $route_provider->getRouteByName($route_name)) {
      $this->drupalGet($route->getPath());

      $this->assertSession()
        ->{$is_monetizable ? 'pageTextContains' : 'pageTextNotContains'}(Monetization::MONETIZATION_DISABLED_ERROR_MESSAGE);
    }
  }

  /**
   * Tests routes when monetization is enabled.
   *
   * @dataProvider routes
   */
  public function testRoutesWithMonetizationEnabled($route_name, $is_monetizable) {
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');

    if ($route = $route_provider->getRouteByName($route_name)) {
      $this->queueOrg();

      $this->assertTrue($this->container->get('apigee_m10n.monetization')->isMonetizationEnabled());

      $this->drupalGet($route->getPath());

      $this->assertSession()->pageTextNotContains(Monetization::MONETIZATION_DISABLED_ERROR_MESSAGE);
    }
  }

  /**
   * Data provider for tests.
   *
   * @return array
   *   An array of routes to test for monetization.
   */
  public function routes() {
    return [
      ['apigee_m10n_test.monetization', TRUE],
      ['apigee_m10n_test.non_monetization', FALSE],
    ];
  }

}
