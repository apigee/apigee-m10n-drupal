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

namespace Drupal\Tests\apigee_m10n\Kernel;

use Drupal\Core\Form\FormState;

/**
 * AutoAssignLegalNameKernelTest tests.
 */
class AutoAssignLegalNameKernelTest extends MonetizationKernelTestBase {

  /**
   * The developer drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    // Setup for creating a user.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
    $this->developer = $this->createAccount(['view rate_plan']);
    $this->setCurrentUser($this->developer);

  }

  /**
   * Tests legal name auto assignment routine.
   */
  public function testAutoAssignLegalName() {
    $package = $this->createProductBundle();
    $rate_plan = $this->createRatePlan($package);
    $subscription = $this->createPurchasedPlan($this->developer, $rate_plan);
    $dev = $this->convertUserToEdgeDeveloper($this->developer, ['MINT_DEVELOPER_LEGAL_NAME' => $this->developer->getEmail()]);
    \Drupal::cache('apigee_edge_entity')->delete("values:developer:{$dev->id()}");
    $this->warmOrganizationCache();
    $this->stack
      ->queueMockResponse([
        'developer_mint' => ['developer' => $dev],
        'get_developer_subscriptions' => ['subscriptions' => [$subscription]],
        'get_package_rate_plan' => ['plan' => $rate_plan],
        'get_terms_conditions' => [],
        'get_developer_terms_conditions' => [],
      ]);
    static::assertSame($this->developer->getEmail(), $dev->getAttributeValue('MINT_DEVELOPER_LEGAL_NAME'));
  }

}
