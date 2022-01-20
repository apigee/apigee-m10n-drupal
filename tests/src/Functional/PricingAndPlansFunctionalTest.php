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

namespace Drupal\Tests\apigee_m10n\Functional;

use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Functional\MonetizationFunctionalTestBase;

/**
 * UI tests for the Pricing and Plans page.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class PricingAndPlansFunctionalTest extends MonetizationFunctionalTestBase {

  /**
   * An account user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $accountUser;

  /**
   * An account admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $accountAdmin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Checking warning messsage about future plan warning with non-admin user.
   */
  public function testPricingAndPlansForNonAdmin() {
    $this->accountUser = $this->createAccount(['view rate_plan']);
    $this->drupalLogin($this->accountUser);

    $this->warmOrganizationCache();
    $this->queuePackagesAndPlansResponse();

    $this->drupalGet(Url::fromRoute('apigee_monetization.plans', [
      'user' => $this->accountUser->id(),
    ]));
    $this->assertSession()->pageTextNotContains('This page will not list plans with a start date in the future. If you are expecting a package to be available, please verify your start date in the Apigee.');
  }

  /**
   * Checking warning messsage about future plan warning with admin user.
   */
  public function testPricingAndPlansForAdmin() {
    $this->accountAdmin = $this->createAccount(['view rate_plan', 'administer apigee monetization']);
    $this->drupalLogin($this->accountAdmin);

    $this->warmOrganizationCache();
    $this->queuePackagesAndPlansResponse();

    $this->drupalGet(Url::fromRoute('apigee_monetization.plans', [
      'user' => $this->accountAdmin->id(),
    ]));
    $this->assertSession()->pageTextContains('This page will not list plans with a start date in the future. If you are expecting a package to be available, please verify your start date in the Apigee.');
  }

}
