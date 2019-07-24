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

namespace Drupal\Tests\apigee_m10n\Kernel\Entity\Access;

use Apigee\Edge\Api\Monetization\Entity\Developer;
use Apigee\Edge\Api\Monetization\Entity\DeveloperCategory;
use Apigee\Edge\Api\Monetization\Entity\DeveloperCategoryRatePlan;
use Apigee\Edge\Api\Monetization\Entity\DeveloperRatePlan;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Class RatePlanAccessTest.
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Entity\Access\RatePlanAccessControlHandler
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class RatePlanAccessControlHandlerTest extends MonetizationKernelTestBase {

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
   * The super admin user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $admin;

  /**
   * The rate_plan access control handler.
   *
   * @var \Drupal\apigee_m10n\Entity\Access\RatePlanAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup for creating a user.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
    $this->accessControlHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('rate_plan');

    // Create root user.
    $this->createUser();

    $this->admin = $this->createAccount(['view rate_plan as anyone', 'purchase rate_plan as anyone']);
    $this->developer = $this->createAccount(['view rate_plan', 'purchase rate_plan']);
    $this->user = $this->createAccount(['view rate_plan', 'purchase rate_plan']);
  }

  /**
   * @covers ::checkAccess
   * @throws \Exception
   */
  public function testAccess() {
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

    $this->accessControlHandler->resetCache();
    $this->assertTrue($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());

    $this->assertTrue($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->user, TRUE)->isAllowed());

    $this->assertTrue($plan->access('view', $this->admin, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->admin, TRUE)->isAllowed());
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

    $this->accessControlHandler->resetCache();

    $this->assertTrue($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());

    $this->assertFalse($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->user, TRUE)->isAllowed());

    $this->assertFalse($plan->access('view', $this->admin, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->admin, TRUE)->isAllowed());
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

    $this->accessControlHandler->resetCache();
    $this->assertFalse($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->developer, TRUE)->isAllowed());
    $this->assertFalse($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->user, TRUE)->isAllowed());

    // Set the developer category and test access.
    /** @var \Drupal\apigee_edge\Entity\Storage\DeveloperStorageInterface $developer_storage */
    $developer_storage = $this->container->get('entity_type.manager')->getStorage('developer');
    $developer = $developer_storage->load($this->developer->getEmail());
    $developer->decorated()->setAttribute('MINT_DEVELOPER_CATEGORY', $category->id());

    $this->accessControlHandler->resetCache();

    $this->assertTrue($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());

    $this->assertFalse($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->user, TRUE)->isAllowed());

    $this->assertFalse($plan->access('view', $this->admin, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->admin, TRUE)->isAllowed());
  }

}
