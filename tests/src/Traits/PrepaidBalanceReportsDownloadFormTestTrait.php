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

namespace Drupal\Tests\apigee_m10n\Traits;

use Apigee\Edge\Api\Monetization\Entity\SupportedCurrency;

/**
 * Helpers to test the prepaid balance reports.
 */
trait PrepaidBalanceReportsDownloadFormTestTrait {

  /**
   * Helper to mock responses.
   *
   * @param array $response_ids
   *   An array of response ids.
   */
  protected function queueMockResponses(array $response_ids) {
    $mock_responses = $this->getMockResponses();
    foreach ($response_ids as $response_id) {
      if (isset($mock_responses[$response_id])) {
        $this->stack->queueMockResponse([
          $response_id => $mock_responses[$response_id],
        ]);
      }
    }
  }

  /**
   * Returns an array of mock responses.
   *
   * @return array
   *   An array of mock responses.
   */
  public function getMockResponses() {
    return [
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
      'get-supported-currencies' => [
        'currencies' => [
          new SupportedCurrency([
            "description" => "United States Dollars",
            "displayName" => "United States Dollars",
            "id" => "usd",
            "minimumTopupAmount" => 10.0000,
            "name" => "USD",
            "status" => "ACTIVE",
            "virtualCurrency" => FALSE,
          ]),
          new SupportedCurrency([
            "description" => "Australia Dollars",
            "displayName" => "Australia Dollars",
            "id" => "aud",
            "minimumTopupAmount" => 10.0000,
            "name" => "AUD",
            "status" => "ACTIVE",
            "virtualCurrency" => FALSE,
          ]),
          new SupportedCurrency([
            "description" => "test",
            "displayName" => "Test",
            "id" => "test",
            "minimumTopupAmount" => 10.0000,
            "name" => "TEST",
            "status" => "ACTIVE",
            "virtualCurrency" => FALSE,
          ]),
        ],
      ],
      'get-billing-documents-months' => [
        'documents' => [
          [
            "month" => 9,
            "monthEnum" => "SEPTEMBER",
            "status" => "OPEN",
            "year" => 2018,
          ],
          [
            "month" => 8,
            "monthEnum" => "AUGUST",
            "status" => "OPEN",
            "year" => 2018,
          ],
          [
            "month" => 12,
            "monthEnum" => "DECEMBER",
            "status" => "OPEN",
            "year" => 2017,
          ],
        ],
      ],
      'post-prepaid-balance-reports' => [
        'from' => '2018-09-01',
        'to' => '2018-09-30',
        'developer' => 'Doe, John',
        'application' => 'All',
        'currency' => 'Local',
        'type' => 'Detailed Balance Top-up report',
        'balances' => [
          [
            'developer' => 'John Doe',
            'developer_id' => 'f67ea7a0-0839-4057-84fc-40a1a38a44e1',
            'currency' => 'USD',
            'transaction_type' => 'BALANCE',
            'provider_status' => 'SUCCESS',
            'charged_data' => 10.0000,
            'transaction_id' => 'e9dde609-ec39-4793-98ed-24ff47d6ce22',
            'payment_reference' => 'e9dde609-ec39-4793-98ed-24ff47d6ce22',
            'start_time' => '2018-09-13 17:05:16',
            'end_time' => '2018-09-13 17:05:16',
            'batch_size' => 1,
          ],
          [
            'developer' => 'John Doe',
            'developer_id' => 'f67ea7a0-0839-4057-84fc-40a1a38a44e1',
            'currency' => 'USD',
            'transaction_type' => 'BALANCE',
            'provider_status' => 'SUCCESS',
            'charged_data' => 210.9900,
            'transaction_id' => '99dbed33-1e58-4924-933e-349b0afbaee1',
            'payment_reference' => '99dbed33-1e58-4924-933e-349b0afbaee1',
            'start_time' => '2018-09-13 17:07:32',
            'end_time' => '2018-09-13 17:07:32',
            'batch_size' => 1,
          ],
        ],
      ],
    ];
  }

}
