<?php

/*
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

namespace Drupal\Tests\apigee_m10n\Functional\ApigeeX;

use Drupal\Core\Url;

/**
 * Functional test for prepaid balance report.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class PrepaidBalanceTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

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
    // If the user has "view own prepaid balance" permission, they should be
    // able to see some prepaid balances.
    $this->developer = $this->createAccount();

    $this->drupalLogin($this->developer);
    $this->warmApigeexOrganizationCache();

    $this->queueApigeexDeveloperResponse($this->developer);
    $this->setBillingType($this->developer);

    $this->queueApigeexDeveloperResponse($this->developer);

    $this->stack->queueMockResponse([
      'get-apigeex-prepaid-balances' => [
        "current_units_aud" => "20",
        "current_nano_aud" => "560000000",

        "current_units_usd" => "15",
        "current_nano_usd" => "340000000",
      ],
    ]);
    $this->drupalGet(Url::fromRoute('apigee_monetization.xbilling', [
      'user' => $this->developer->id(),
    ]));

    $this->assertSession()->responseNotContains('Access denied');

    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-AUD > td:nth-child(1)', 'AUD');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-AUD > td:nth-child(2)', 'A$20.56');

    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-USD > td:nth-child(1)', 'USD');
    $this->assertSession()->elementTextContains('css', 'tr.apigee-balance-row-USD > td:nth-child(2)', '$15.34');
  }

}
