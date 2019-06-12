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

namespace Drupal\Tests\apigee_m10n_teams\Kernel\Controller;

use Drupal\apigee_edge\Entity\ApiProduct;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the pricing and plans controller.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class TeamPricingAndPlansControllerKernelTest extends MonetizationTeamsKernelTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * An apigee team.
   *
   * @var \Drupal\apigee_edge_teams\Entity\TeamInterface
   */
  protected $team;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
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

    // Makes sure the new user isn't root.
    $this->createAccount();
    $this->developer = $this->createAccount();
    $this->team = $this->createTeam();
    $this->addUserToTeam($this->team, $this->developer);

    $this->createCurrentUserSession($this->developer);
  }

  /**
   * Tests the rendered package response.
   */
  public function testTeamPricingAndPlansController() {
    $this->assertNonMemberAccessDenied();
    $this->assertTeamPlansPage();
  }

  /**
   * Assert that a non team member can't access a team's plans page.
   */
  protected function assertNonMemberAccessDenied() {
    // Check access for a non-member.
    $non_member = $this->createAccount();
    // Queue a developer response because the access check reloads the developer
    // if it has no teams.
    $this->queueDeveloperResponse($non_member);
    static::assertFalse(Url::fromRoute('apigee_monetization.team_plans', ['team' => $this->team->id()])->access($this->createUserSession($non_member)));
  }

  /**
   * Tests the team page response.
   */
  protected function assertTeamPlansPage() {
    // Check access for a team member.
    static::assertTrue(Url::fromRoute('apigee_monetization.team_plans', ['team' => $this->team->id()])->access());

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
        $entity_static_cache->set("values:api_product:{$product->id()}", ApiProduct::create([
          'id' => $product->id(),
          'name' => $product->getName(),
          'displayName' => $product->getDisplayName(),
          'description' => $product->getDescription(),
        ]));
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

    // Test the controller output for a team with plans.
    $response = $this->container
      ->get('http_kernel')
      ->handle(Request::create(Url::fromRoute('apigee_monetization.team_plans', ['team' => $this->team->id()])->toString(), 'GET'));

    $this->setRawContent($response->getContent());
    // Test the response.
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertTitle('Pricing and Plans | ');
    $rate_plan_css_index = 1;
    foreach ($rate_plans as $package_id => $plan_list) {
      foreach ($plan_list as $rate_plan) {
        $prefix = ".pricing-and-plans > .pricing-and-plans__item:nth-child({$rate_plan_css_index}) > .apigee-package-rate-plan";
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
