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

namespace Drupal\Tests\apigee_m10n\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Traits\PrepaidBalanceReportsDownloadFormTestTrait;

/**
 * UI tests for the prepaid balance reports download form.
 *
 * @package Drupal\Tests\apigee_m10n\Functional
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Form\PrepaidBalanceReportsDownloadForm
 */
class PrepaidBalanceReportsDownloadFormFunctionalJavascriptTest extends MonetizationFunctionalJavascriptTestBase {

  use PrepaidBalanceReportsDownloadFormTestTrait;

  /**
   * Drupal user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Test that a user without required permission cannot view the download form.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function testDownloadPrepaidBalanceReportsAccessDenied() {
    // Create a user that can access the billing page.
    $this->account = $this->createAccount([
      'view own prepaid balance',
    ]);

    $this->drupalLogin($this->account);

    $this->queueMockResponses(['get-prepaid-balances']);

    $this->queueOrg();
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->account->id(),
    ]));

    // A user without the 'download prepaid balance reports' cannot see the form.
    $this->assertSession()
      ->elementNotExists('css', 'form.previous-balances-reports-download-form');
  }

  /**
   * Test the UI for prepaid balance download form.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function testPrepaidBalanceReportsDownloadForm() {
    // Create a user that can access the billing page and download report.
    $this->account = $this->createAccount([
      'view own prepaid balance',
      'download prepaid balance reports',
    ]);

    $this->drupalLogin($this->account);

    $this->queueOrg();
    $this->queueMockResponses([
      'get-prepaid-balances',
      'get-supported-currencies',
      'get-billing-documents-months',
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->account->id(),
    ]));

    // A user with the 'download prepaid balance reports' permission can see the form.
    $this->assertSession()
      ->elementExists('css', 'form.previous-balances-reports-download-form');

    // Check for account options.
    $this->assertSession()->optionExists('edit-currency', 'usd');
    $this->assertSession()->optionExists('edit-currency', 'aud');

    // Check for year options.
    $this->assertSession()->optionExists('edit-year', 2017);
    $this->assertSession()->optionExists('edit-year', 2018);

    $page = $this->getSession()->getPage();

    // Select an account.
    $page->selectFieldOption('edit-currency', 'usd');

    // Test month field when year is selected.
    $page->selectFieldOption('edit-year', 2017);
    $this->assertSession()->waitForElementVisible('css', 'select#edit-month-2017');
    $this->assertSession()->optionExists('month_2017', '2017-12');

    // Test month field when year is changed.
    $page->selectFieldOption('edit-year', 2018);
    $this->assertSession()->waitForElementVisible('css', 'select#edit-month-2018');
    $this->assertSession()->optionExists('month_2018', '2018-8');
    $this->assertSession()->optionExists('month_2018', '2018-9');
  }

}
