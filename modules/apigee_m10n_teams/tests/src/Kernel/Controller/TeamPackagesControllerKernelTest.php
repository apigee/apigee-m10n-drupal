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

use Drupal\apigee_m10n_teams\Controller\TeamPackagesController;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;

/**
 * Tests the billing details.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class TeamPackagesControllerKernelTest extends MonetizationTeamsKernelTestBase {

  use TeamProphecyTrait;

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
    ]);
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
  public function testPackageController() {
    $this->setCurrentTeamRoute($this->team);

    // Check access for a team member.
    static::assertTrue(Url::fromRoute('apigee_monetization.team_packages', ['team' => $this->team->id()])->access());

    // Check access for a non-member.
    $non_member = $this->createAccount();
    // Queue a developer response because the access check reloads the developer
    // if it has no teams.
    $this->queueDeveloperResponse($non_member);
    static::assertFalse(Url::fromRoute('apigee_monetization.team_packages', ['team' => $this->team->id()])->access($this->createUserSession($non_member)));

    // Create packages and plans.
    $packages = [
      $this->createPackage(),
      $this->createPackage(),
    ];
    $sdk_packages = [];
    $rate_plans = [];
    foreach ($packages as $package) {
      $rate_plans[$package->id()] = $this->createPackageRatePlan($package);
      $sdk_packages[] = $package->decorated();
    }

    // Queue packages and plans responses.
    $this->stack
      ->queueMockResponse(['get_monetization_packages' => ['packages' => $sdk_packages]]);

    // Create a controller and render the packages and plans content.
    $controller = TeamPackagesController::create($this->container);
    $renderable = $controller->teamCatalogPage($this->team);
    $this->setRawContent((string) \Drupal::service('renderer')->renderRoot($renderable));

    foreach ($packages as $index => $package) {
      $this->assertPackage($index, $package, $rate_plans[$package->id()]);
    }
  }

  /**
   * Helper that checks package content.
   *
   * @param int $index
   *   The position of the package.
   * @param \Drupal\apigee_m10n\Entity\PackageInterface $package
   *   The package entity.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface[] $rate_plan
   *   The rate plans for this package.
   */
  protected function assertPackage($index, $package, $rate_plan) {
    $css_position = $index + 1;
    $prefix = "ul.apigee-package-list li:nth-child({$css_position}) .apigee-package-details .apigee-package";
    // Package detail as rendered.
    static::assertSame($package->id(), trim($this->cssSelect("{$prefix} > div:nth-child(1) > div:nth-child(2)")[0]));
    static::assertSame($package->getDisplayName(), trim($this->cssSelect("{$prefix} > div:nth-child(2) > div:nth-child(2)")[0]));
    static::assertSame($package->getDescription(), trim($this->cssSelect("{$prefix} > div:nth-child(3) > div:nth-child(2)")[0]));

    // API Products.
    foreach ($package->get('apiProducts') as $product_index => $product) {
      static::assertSame($product->entity->getName(), trim($this->cssSelect("{$prefix} .apigee-sdk-product .apigee-product-details .apigee-product-id")[$product_index]));
      static::assertSame($product->entity->getDisplayName(), trim($this->cssSelect("{$prefix} .apigee-sdk-product .apigee-product-details .apigee-product-display-name .label")[$product_index]));
      static::assertSame($product->entity->getDescription(), trim($this->cssSelect("{$prefix} .apigee-sdk-product .apigee-product-details .apigee-product-description")[$product_index]));
    }
  }

}
