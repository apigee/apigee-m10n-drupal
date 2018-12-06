<?php

/*
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Drupal\apigee_m10n\SDK\Controller;

/**
 * TODO: Extract this to the apigee-client-php package.
 *
 * Interface ReportsControllerInterface.
 *
 * @package Drupal\apigee_m10n\Controller
 */
interface PrepaidBalanceReportsControllerInterface {

  /**
   * Returns the prepaid balance reports for a given month and currency.
   *
   * @param \DateTimeImmutable $billingMonth
   *   The billing month.
   * @param string $currencyCode
   *   The current code. Example: usd or cad.
   *
   * @return null|string
   *   The report as a CSV string.
   */
  public function getReport(\DateTimeImmutable $billingMonth, string $currencyCode): ?string;

}
