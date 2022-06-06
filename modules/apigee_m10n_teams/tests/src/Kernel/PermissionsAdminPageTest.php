<?php

/*
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

namespace Drupal\Tests\apigee_m10n_teams\Kernel;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the team permissions admin page.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class PermissionsAdminPageTest extends MonetizationTeamsKernelTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('team_member_role');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);

    // Admin user is user 1.
    $this->admin = $this->createAccount();

    $this->createCurrentUserSession($this->admin);
  }

  /**
   * Ensure this module properly extends the team permissions page UI.
   */
  public function testPermissionsAdminPage() {
    $request = Request::create(Url::fromRoute('apigee_edge_teams.settings.team.permissions')->toString(), 'GET');
    $response = $this->container
      ->get('http_kernel')
      ->handle($request);

    $this->setRawContent($response->getContent());

    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

    // Check that the permission group labels for this module are visible.
    $this->assertText('Purchase a rate plan');
    $this->assertText('Update a purchased plan');
    $this->assertText('View purchased plans');
    $this->assertText('View rate plans');
    $this->assertText('View product bundle');
    $this->assertText('Edit billing details');
    $this->assertText('View prepaid balance');
    $this->assertText('View prepaid balance report');
    $this->assertText('Refresh prepaid balance');
  }

}
