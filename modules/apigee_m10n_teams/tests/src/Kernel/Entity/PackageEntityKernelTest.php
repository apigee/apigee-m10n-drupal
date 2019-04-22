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
class PackageEntityKernelTest extends MonetizationTeamsKernelTestBase {

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
    ]);
    // Makes sure the new user isn't root.
    $this->createAccount();
    $this->developer = $this->createAccount();
    $this->team = $this->createTeam();
    $this->addUserToTeam($this->team, $this->developer);

    $this->createCurrentUserSession($this->developer);

    $this->package = $this->createPackage();
  }

  /**
   * Test loading a rate plan from drupal entity storage.
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
    static::assertSame(gettype($package), gettype($this->package));

    // Check properties.
    static::assertSame($this->package->id(), $package->id());
    static::assertSame($this->package->getDisplayName(), $package->getDisplayName());
    static::assertSame($this->package->getName(), $package->getName());
    static::assertSame($this->package->getDescription(), $package->getDescription());
    static::assertSame($this->package->getStatus(), $package->getStatus());
    // Get the package products.
    $products = $package->getApiProducts();
    static::assertGreaterThan(0, $this->count($products));
    static::assertCount(count($this->package->getApiProducts()), $products);

    foreach ($this->package->getApiProducts() as $product_id => $product) {
      static::assertArrayHasKey($product_id, $products);
    }
  }

}
