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

use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\apigee_m10n_teams\Entity\TeamsRatePlan;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;

/**
 * Tests the team rate plan entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class RatePlanEntityKernelTest extends MonetizationTeamsKernelTestBase {

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
   * A test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

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
    $this->rate_plan = $this->createPackageRatePlan($this->package);
  }

  /**
   * Test team rate plan entity overrides.
   *
   * @throws \Exception
   */
  public function testRatePlanEntity() {
    $this->setCurrentTeamRoute($this->team);

    // Check team access.
    static::assertTrue($this->rate_plan->access('view', $this->developer));

    // Make sure we get a team context when getting a package url.
    $url = $this->rate_plan->toUrl('team');
    static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}/plan/{$this->rate_plan->id()}", $url->toString());
    static::assertSame('entity.rate_plan.team', $url->getRouteName());

    // Load the cached rate plan.
    $rate_plan = RatePlan::loadById($this->package->id(), $this->rate_plan->id());

    static::assertInstanceOf(TeamsRatePlan::class, $rate_plan);
    // Compare the loaded rate plan with the object comparator.
    static::assertEquals($rate_plan->decorated(), $this->rate_plan->decorated());

    // Render the package.
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('rate_plan')
      ->view($this->rate_plan);

    $this->setRawContent((string) \Drupal::service('renderer')->renderRoot($build));

    // Rate plans as rendered.
    static::assertSame($rate_plan->getDisplayName(), trim($this->cssSelect('.apigee-package-rate-plan > div > a')[0]));
    static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}/plan/{$rate_plan->id()}", (string) $this->cssSelect('.apigee-package-rate-plan > div > a')[0]->attributes()['href']);
    static::assertSame($rate_plan->getDescription(), trim($this->cssSelect('.apigee-package-rate-plan > div:nth-child(2) > div:nth-child(2)')[0]));
    static::assertSame('Purchase Plan', trim($this->cssSelect('.apigee-package-rate-plan > div:nth-child(4) > div:nth-child(2) > a')[0]));
    static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}/plan/{$rate_plan->id()}/subscribe", (string) $this->cssSelect('.apigee-package-rate-plan > div:nth-child(4) > div:nth-child(2) > a')[0]->attributes()['href']);

    // Plan details.
    $details = $rate_plan->getRatePlanDetails()[0];
    static::assertSame(strtolower("{$details->getDuration()} {$details->getDurationType()}"), trim($this->cssSelect('.apigee-package-rate-plan .rate-plan-detail td:nth-child(2) > span')[0]));
    static::assertSame($details->getOrganization()->getDescription(), trim($this->cssSelect('.apigee-package-rate-plan .rate-plan-detail td:nth-child(2) > span')[1]));
    static::assertSame($details->getOrganization()->getCountry(), trim($this->cssSelect('.apigee-package-rate-plan .rate-plan-detail td:nth-child(2) > span')[2]));
    static::assertSame($details->getCurrency()->getName(), trim($this->cssSelect('.apigee-package-rate-plan .rate-plan-detail td:nth-child(2) > span')[3]));
  }

}
