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

use Drupal\apigee_m10n\Entity\Package;
use Drupal\apigee_m10n_teams\Entity\TeamsPackageInterface;
use Drupal\Tests\apigee_m10n\Traits\RatePlansPropertyEnablerTrait;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;

/**
 * Tests the team package entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class PackageEntityKernelTest extends MonetizationTeamsKernelTestBase {

  use TeamProphecyTrait;
  use RatePlansPropertyEnablerTrait;

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
   * A test package.
   *
   * @var \Drupal\apigee_m10n\Entity\PackageInterface
   */
  protected $package;

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
    // Makes sure the new user isn't root.
    $this->createAccount();
    $this->developer = $this->createAccount();
    $this->team = $this->createTeam();
    $this->addUserToTeam($this->team, $this->developer);

    $this->createCurrentUserSession($this->developer);

    $this->package = $this->createPackage();

    // Enable the `ratePlans` field.
    $this->enableRatePlansForViewDisplay();
  }

  /**
   * Test team package entity rendering.
   *
   * @throws \Exception
   */
  public function testPackageEntity() {
    $this->setCurrentTeamRoute($this->team);

    // Check team access.
    static::assertTrue($this->package->access('view', $this->developer));

    // Make sure we get a team context when getting a package url.
    $url = $this->package->toUrl('team');
    static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}", $url->toString());
    static::assertSame('entity.package.team', $url->getRouteName());

    // Load the cached package.
    $package = Package::load($this->package->id());

    static::assertInstanceOf(TeamsPackageInterface::class, $package);
    // Use the object comparator to compare the loaded package.
    static::assertEquals($this->package, $package);

    // Get the package products.
    static::assertGreaterThan(0, $this->count($package->getApiProducts()));

    // Render the package.
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('package')
      ->view($this->package);

    $rate_plan_1 = $this->createPackageRatePlan($this->package);
    $rate_plan_2 = $this->createPackageRatePlan($this->package);

    $this->stack->queueMockResponse(['get_monetization_package_plans' => ['plans' => [$rate_plan_1, $rate_plan_2]]]);
    $content = \Drupal::service('renderer')->renderRoot($build);

    $this->setRawContent((string) $content);

    // Package detail as rendered.
    static::assertSame($package->id(), trim($this->cssSelect('.apigee-package > div > div')[1]));
    static::assertSame($package->getDisplayName(), trim($this->cssSelect('.apigee-package > div > div')[3]));
    static::assertSame($package->getDescription(), trim($this->cssSelect('.apigee-package > div > div')[5]));

    // API Products.
    foreach ($this->package->get('apiProducts') as $index => $apiProduct) {
      static::assertSame($apiProduct->entity->getName(), trim($this->cssSelect('.apigee-package .apigee-sdk-product .apigee-product-details .apigee-product-id')[$index]));
      static::assertSame($apiProduct->entity->getDisplayName(), trim($this->cssSelect('.apigee-package .apigee-sdk-product .apigee-product-details .apigee-product-display-name .label')[$index]));
      static::assertSame($apiProduct->entity->getDescription(), trim($this->cssSelect('.apigee-package .apigee-sdk-product .apigee-product-details .apigee-product-description')[$index]));
    }

    // Rate plans as rendered.
    foreach ([$rate_plan_1, $rate_plan_2] as $index => $rate_plan) {
      /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */
      static::assertSame($rate_plan->getDisplayName(), trim($this->cssSelect('.apigee-package .apigee-package-rate-plan > div > a')[$index]));
      static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}/plan/{$rate_plan->id()}", (string) $this->cssSelect('.apigee-package .apigee-package-rate-plan > div > a')[$index]->attributes()['href']);
      static::assertSame($rate_plan->getDescription(), trim($this->cssSelect('.apigee-package .apigee-package-rate-plan > div:nth-child(2) > div:nth-child(2)')[$index]));
      static::assertSame('Purchase Plan', trim($this->cssSelect('.apigee-package .apigee-package-rate-plan > div:nth-child(4) > div:nth-child(2) > a')[$index]));
      static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}/plan/{$rate_plan->id()}/subscribe", (string) $this->cssSelect('.apigee-package .apigee-package-rate-plan > div:nth-child(4) > div:nth-child(2) > a')[$index]->attributes()['href']);
    }
  }

}
