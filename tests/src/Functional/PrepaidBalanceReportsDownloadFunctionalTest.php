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

namespace Drupal\Tests\apigee_m10n\Functional;

use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Traits\PrepaidBalanceReportsDownloadFormTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for the prepaid balance reports download route.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Form\PrepaidBalanceReportsDownloadForm
 */
class PrepaidBalanceReportsDownloadFunctionalTest extends MonetizationFunctionalTestBase {

  use PrepaidBalanceReportsDownloadFormTestTrait;

  /**
   * Drupal user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The datetime for the 1st of last month.
   *
   * @var \DateTimeImmutable
   */
  protected $lastMonth;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->account = $this->createAccount([
      'view own prepaid balance',
      'download prepaid balance reports',
    ]);

    $this->drupalLogin($this->account);

    $this->lastMonth = new \DateTimeImmutable('first day of last month 00:00:00');
  }

  /**
   * Test for an account with no reports.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testAccountWithNoReports() {
    $year = $this->lastMonth->format('Y');
    $month = $this->lastMonth->format('F');
    $month_numeric = $this->lastMonth->format('m');

    $this->queueDeveloperResponse($this->account);
    $this->queueMockResponses([
      'get-prepaid-balances',
      'get-supported-currencies',
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->account->id(),
    ]));

    $this->queueMockResponses([
      'get-prepaid-balances',
      'get-supported-currencies',
      'get_not_found',
    ]);

    $edit = [
      'currency' => 'test',
      'year' => $year,
      "month_{$year}" => "{$year}-{$month_numeric}",
    ];
    $this->submitForm($edit, 'Download CSV');

    $this->assertSession()
      ->pageTextContains("There are no prepaid balance reports for account TEST (Test) for {$month} {$year}.");
  }

  /**
   * Test for a date with no reports.
   *
   * @throws \Exception
   */
  public function testDateWithNoReport() {
    $year = $this->lastMonth->format('Y');
    $month = $this->lastMonth->format('F');
    $month_numeric = $this->lastMonth->format('m');

    $this->queueDeveloperResponse($this->account);
    $this->queueMockResponses([
      'get-prepaid-balances',
      'get-supported-currencies',
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->account->id(),
    ]));

    $this->queueMockResponses([
      'get-prepaid-balances',
      'get-supported-currencies',
      'get_not_found',
    ]);

    $edit = [
      'currency' => 'usd',
      'year' => $year,
      "month_{$year}" => "{$year}-{$month_numeric}",
    ];
    $this->submitForm($edit, 'Download CSV');

    $this->assertSession()
      ->pageTextContains("There are no prepaid balance reports for account USD (United States Dollars) for {$month} {$year}.");
  }

  /**
   * Test the prepaid balance download form for a valid account and date.
   *
   * @throws \Exception
   */
  public function testValidAccountAndDate() {
    $year = $this->lastMonth->format('Y');
    $month_numeric = $this->lastMonth->format('m');

    $this->queueDeveloperResponse($this->account);
    $this->queueMockResponses([
      'get-prepaid-balances',
      'get-supported-currencies',
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->account->id(),
    ]));

    $this->queueMockResponses([
      'get-prepaid-balances',
      'get-supported-currencies',
      'post-prepaid-balance-reports.csv.twig',
    ]);

    $edit = [
      'currency' => 'usd',
      'year' => $year,
      "month_{$year}" => "{$year}-{$month_numeric}",
    ];
    $this->submitForm($edit, 'Download CSV');

    $filename = "prepaid-balance-report-{$year}-{$month_numeric}.csv";
    $this->assertStatusCodeEquals(Response::HTTP_OK);
    $this->assertHeaderEquals('text/csv; charset=UTF-8', $this->getSession()->getResponseHeader('Content-Type'));
    $this->assertHeaderEquals("attachment; filename=$filename", $this->getSession()->getResponseHeader('Content-Disposition'));
  }

}
