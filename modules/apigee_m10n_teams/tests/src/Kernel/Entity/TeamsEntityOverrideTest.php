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

namespace Drupal\Tests\apigee_m10n_teams\Kernel\Entity;

use Drupal\apigee_edge\Entity\EdgeEntityType;
use Drupal\apigee_m10n\Entity\ProductBundle;
use Drupal\apigee_m10n_teams\Entity\Routing\MonetizationTeamsEntityRouteProvider;
use Drupal\apigee_m10n_teams\Entity\Storage\TeamPurchasedPlanStorage;
use Drupal\apigee_m10n_teams\Entity\TeamProductBundle;
use Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlan;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the module affected overrides are overridden properly.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class TeamsEntityOverrideTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'key',
    'user',
    'apigee_edge',
    'apigee_edge_teams',
    'apigee_m10n',
    'apigee_m10n_teams',
  ];

  /**
   * Tests the package entity overrides.
   *
   * @throws \Exception
   */
  public function testPackageEntityOverrides() {
    $random = $this->getRandomGenerator();
    $team_id = strtolower($this->randomMachineName(8) . '-' . $this->randomMachineName(4));

    // Set the current route match with a mock.
    $route_match = $this->prophesize(CurrentRouteMatch::class);
    $route_match->getRouteName()->willReturn('entity.team.canonical');
    $route_match->getParameter('user')->willReturn(NULL);
    $route_match->getParameter('team')->willReturn($team_id);
    $this->container->set('current_route_match', $route_match->reveal());

    // Create a package entity.
    $product_bundle = ProductBundle::create([
      'id' => strtolower($random->word(8) . '-' . $random->word(4)),
      'displayName' => $random->name(12),
      'description' => $random->sentences(12),
    ]);

    $entity_type = $this->container->get('entity_type.manager')->getDefinition('package');
    static::assertInstanceOf(EdgeEntityType::class, $entity_type);

    // Make sure our entity class has taken over.
    static::assertSame(TeamProductBundle::class, $entity_type->getClass());
    // Check for the `team` link template.
    static::assertNotEmpty($entity_type->getLinkTemplate('team'));
    // Make sure we are overriding the route provider.
    static::assertSame(MonetizationTeamsEntityRouteProvider::class, $entity_type->getRouteProviderClasses()['html']);

    // Make sure we get a team context when getting a package url.
    $url = $product_bundle->toUrl('canonical');
    static::assertSame("/teams/{$team_id}/monetization/package/{$product_bundle->id()}", $url->toString());
    static::assertSame('entity.package.team', $url->getRouteName());
  }

  /**
   * Tests the package entity overrides.
   *
   * @throws \Exception
   */
  public function testPurchasedPlanEntityOverrides() {
    $entity_type = $this->container->get('entity_type.manager')->getDefinition('purchased_plan');
    static::assertInstanceOf(EdgeEntityType::class, $entity_type);

    // Make sure our entity class has taken over.
    static::assertSame(TeamsPurchasedPlan::class, $entity_type->getClass());
    // Make sure our entity storage class has taken over.
    static::assertSame(TeamPurchasedPlanStorage::class, $entity_type->getStorageClass());
  }

}
