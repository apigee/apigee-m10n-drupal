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

namespace Drupal\Tests\apigee_m10n_teams\Kernel;

use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;

/**
 * Tests the billing details.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class BillingDetailsTest extends MonetizationTeamsKernelTestBase {

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
    $this->installEntitySchema('team_member_role');
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

    $this->createCurrentUserSession($this->developer);
  }

  /**
   * Tests permissions for `Billing Details` page.
   */
  public function testBillingDetailsAccessDenied() {
    // If companies are empty, the developer cache will be cleared and reloaded.
    $this->queueDeveloperResponse($this->developer);
    // Check access for the current user.
    static::assertFalse(Url::fromRoute('apigee_m10n_teams.team_billing_details', ['team' => $this->team->id()])->access());
  }

}
