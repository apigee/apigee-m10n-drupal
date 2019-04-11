<?php

/**
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

namespace Drupal\Tests\apigee_m10n_teams\Functional;

use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\Core\Url;

/**
 * Class BillingDetailsTest.
 *
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_functional
 */
class BillingDetailsTest extends MonetizationTeamsFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function setUp() {
    parent::setUp();
    $this->developer = $this->createAccount([]);
    $this->drupalLogin($this->developer);
  }

  /**
   * Tests permissions for `Billing Details` page.
   */
  public function testBillingDetailsAccessDenied() {
    $this->drupalGet(Url::fromRoute('apigee_m10n_teams.team_billing_details', [
      'team' => $this->developer->id(),
    ]));

    $this->assertSession()->responseContains('Page not found');
  }

  /**
   * Tests for `Billing Details` page.
   *
   * @throws \Exception
   */
  public function testBillingDetailsPageView() {
    // @TODO: Add billing details page test.
  }

}
