<?php

/**
 * Copyright 2019 Google Inc.
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

namespace Drupal\Tests\apigee_m10n_teams\Kernel\Entity\Access;

use Apigee\Edge\Api\Monetization\Entity\Company;
use Apigee\Edge\Api\Monetization\Entity\CompanyRatePlan;
use Apigee\Edge\Api\Monetization\Entity\Developer;
use Apigee\Edge\Api\Monetization\Entity\DeveloperCategory;
use Apigee\Edge\Api\Monetization\Entity\DeveloperCategoryRatePlan;
use Apigee\Edge\Api\Monetization\Entity\DeveloperRatePlan;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;

/**
 * Class TeamRatePlanAccessTest.
 *
 * @coversDefaultClass \Drupal\apigee_m10n_teams\Entity\Access\TeamRatePlanAccessControlHandler
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class TeamRatePlanAccessControlHandlerTest extends MonetizationTeamsKernelTestBase {

  use TeamProphecyTrait;

  /**
   * The user with access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * The control user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);

    // Create root user.
    $this->createAccount();

    $this->developer = $this->createAccount(['view rate_plan', 'purchase rate_plan']);
    $this->createCurrentUserSession($this->developer);

    $this->user = $this->createAccount(['view rate_plan', 'purchase rate_plan']);
  }

  /**
   * @covers ::checkAccess
   * @throws \Exception
   */
  public function testAccess() {
    // Ensure non-team access still works as expected.
    $this->assertStandardRatePlan();
    $this->assertDeveloperRatePlan();
    $this->assertDeveloperCategoryRatePlan();
  }

  /**
   * Test \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface::TYPE_STANDARD rate plan.
   *
   * @throws \Exception
   */
  public function assertStandardRatePlan() {
    $plan = $this->createRatePlan($this->createProductBundle());

    \Drupal::entityTypeManager()->getAccessControlHandler('rate_plan')->resetCache();
    $this->assertTrue($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());
  }

  /**
   * Tests \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface::TYPE_DEVELOPER rate plan.
   */
  public function assertDeveloperRatePlan() {
    $developer_rate_plan = new DeveloperRatePlan([]);
    $developer_rate_plan->setPackage($this->createProductBundle());
    $developer_rate_plan->setDeveloper(new Developer([
      'email' => $this->developer->getEmail(),
      'name' => $this->developer->getDisplayName(),
    ]));
    $plan = RatePlan::createFrom($developer_rate_plan);

    \Drupal::entityTypeManager()->getAccessControlHandler('rate_plan')->resetCache();
    $this->assertTrue($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());
    $this->assertFalse($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->user, TRUE)->isAllowed());
  }

  /**
   * Tests \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface::TYPE_DEVELOPER_CATEGORY rate plan.
   */
  public function assertDeveloperCategoryRatePlan() {
    $category = new DeveloperCategory([
      'id' => $this->getRandomUniqueId(),
      'name' => $this->randomString(10),
    ]);
    $developer_category_rate_plan = new DeveloperCategoryRatePlan([]);
    $developer_category_rate_plan->setDeveloperCategory($category);
    $plan = RatePlan::createFrom($developer_category_rate_plan);

    \Drupal::entityTypeManager()->getAccessControlHandler('rate_plan')->resetCache();
    $this->assertFalse($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->developer, TRUE)->isAllowed());
    $this->assertFalse($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->user, TRUE)->isAllowed());

    // Set the developer category and test access.
    /** @var \Drupal\apigee_edge\Entity\Storage\DeveloperStorageInterface $developer_storage */
    $developer_storage = $this->container->get('entity_type.manager')->getStorage('developer');
    $developer = $developer_storage->load($this->developer->getEmail());
    $developer->decorated()->setAttribute('MINT_DEVELOPER_CATEGORY', $category->id());

    \Drupal::entityTypeManager()->getAccessControlHandler('rate_plan')->resetCache();
    $this->assertTrue($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());
    $this->assertFalse($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->user, TRUE)->isAllowed());
  }

  /**
   * Tests \Apigee\Edge\Api\Monetization\Entity\CompanyRatePlanInterface rate plan.
   */
  public function testTeamRatePlan() {
    $team = $this->createTeam();
    $this->addUserToTeam($team, $this->developer);

    $company = new Company([
      'id' => $team->id(),
      'legalName' => $team->getName(),
    ]);
    $company_rate_plan = new CompanyRatePlan([
      'id' => $this->randomMachineName(),
      'company' => $company,
    ]);

    $plan = RatePlan::createFrom($company_rate_plan);

    $this->assertTrue($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());
    $this->assertFalse($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->user, TRUE)->isAllowed());
  }

  /**
   * Tests \Apigee\Edge\Api\Monetization\Entity\DeveloperCategoryRatePlanInterface rate plan for teams.
   */
  public function testTeamCategoryRatePlan() {
    $category = new DeveloperCategory([
      'id' => $this->getRandomUniqueId(),
      'name' => $this->randomString(10),
    ]);

    $team = $this->createTeam();

    /** @var \Drupal\apigee_edge_teams\Entity\Storage\TeamStorageInterface $team_storage */
    $team_storage = $this->container->get('entity_type.manager')->getStorage('team');
    $team = $team_storage->load($team->id());
    $team->decorated()->setAttribute('MINT_DEVELOPER_CATEGORY', $category->id());

    $this->developer = $this->createAccount(['view rate_plan', 'purchase rate_plan']);
    $this->createCurrentUserSession($this->developer);

    $this->addUserToTeam($team, $this->developer);
    $this->setCurrentTeamRoute($team);

    $developer_category_rate_plan = new DeveloperCategoryRatePlan([]);
    $developer_category_rate_plan->setDeveloperCategory($category);
    $plan = RatePlan::createFrom($developer_category_rate_plan);

    $this->assertTrue($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());
    $this->assertFalse($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->user, TRUE)->isAllowed());
  }

}
