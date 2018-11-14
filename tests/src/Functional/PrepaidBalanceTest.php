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

namespace Drupal\Tests\apigee_m10n\Functional;

use Drupal\Core\Url;

/**
 * Functional test for prepaid balance report.
 *
 * @package Drupal\Tests\apigee_m10n\Functional
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class PrepaidBalanceTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Test access.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function testPrepaidBalancesAccessDenied() {

    // If the user doesn't have the "view mint prepaid reports" permission, they should get access denied.
    $this->account = $this->createAccount([]);

    $this->queueOrg();

    $this->drupalLogin($this->account);
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->account->id(),
    ]));

    $this->assertSession()->responseContains('Access denied');
  }

  /**
   * Test the prepaid balance response.
   *
   * @throws \Behat\Mink\Exception\ElementTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function testPrepaidBalancesView() {
    // If the user has "view mint prepaid reports" permission, they should be
    // able to see some prepaid balances.
    $this->account = $this->createAccount(['view mint prepaid reports']);

    $this->queueOrg();

    $this->drupalLogin($this->account);

    $this->stack->queueMockResponse([
      'get-prepaid-balances' => [
        "current_aud" => 100.0000,
        "current_total_aud" => 200.0000,
        "current_usage_aud" => 50.0000,
        "topups_aud" => 50.0000,

        "current_usd" => 72.2000,
        "current_total_usd" => 120.0200,
        "current_usage_usd" => 47.8200,
        "topups_usd" => 30.0200,
      ],
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->account->id(),
    ]));

    $this->assertSession()->responseNotContains('Access denied');

    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-aud > td:nth-child(1)', 'AUD');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-aud > td:nth-child(2)', 'A$0.00');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-aud > td:nth-child(3)', 'A$50.00');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-aud > td:nth-child(4)', 'A$50.00');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-aud > td:nth-child(5)', 'A$0.00');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-aud > td:nth-child(6)', 'A$100.00');

    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-usd > td:nth-child(1)', 'USD');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-usd > td:nth-child(2)', '$0.00');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-usd > td:nth-child(3)', '$30.02');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-usd > td:nth-child(4)', '$47.82');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-usd > td:nth-child(5)', '$0.00');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-usd > td:nth-child(6)', '72.20');
  }

}
