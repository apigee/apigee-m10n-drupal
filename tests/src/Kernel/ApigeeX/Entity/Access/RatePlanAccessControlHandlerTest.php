<?php

/**
 * Copyright 2021 Google Inc.
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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Entity\Access;

use Drupal\apigee_m10n\Entity\XRatePlan;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;

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
   * The rate_plan access control handler.
   *
   * @var \Drupal\apigee_m10n\Entity\Access\RatePlanAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup for creating a user.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');

    // Get pre-configured token storage service for testing.
    $this->storeToken();

    $this->accessControlHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('xrate_plan');

    // Create root user.
    $this->createUser();

    $this->developer = $this->createAccount(
      [
        'view rate_plan',
        'purchase rate_plan',
      ]
    );
    $this->setCurrentUser($this->developer);

    // Create use without the permission.
    $this->user = $this->createAccount();

    $this->stack->reset();
    // Warm the ApigeeX organization.
    $this->warmApigeexOrganizationCache();
  }

  /**
   * @covers ::checkAccess
   * @throws \Exception
   */
  public function testAccess() {
    $this->assertStandardRatePlan();
  }

  /**
   * Test \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface::TYPE_STANDARD rate plan.
   *
   * @throws \Exception
   */
  public function assertStandardRatePlan() {
    $this->stack->reset();
    $xproduct = $this->createApigeexProduct();
    $this->stack->reset();
    $plan = $this->createRatePlan($xproduct);

    $this->accessControlHandler->resetCache();
    $this->assertTrue($plan->access('view', $this->developer, TRUE)->isAllowed());
    $this->assertTrue($plan->access('purchase', $this->developer, TRUE)->isAllowed());
    $this->assertFalse($plan->access('view', $this->user, TRUE)->isAllowed());
    $this->assertFalse($plan->access('purchase', $this->user, TRUE)->isAllowed());
  }

}
