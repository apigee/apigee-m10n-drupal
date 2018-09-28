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

namespace Drupal\apigee_m10n;

use Apigee\Edge\Api\Monetization\Entity\PrepaidBalance;

/**
 * Helper class to add formatting to Apigee\Edge\Api\Monetization\Entity\PrepaidBalance
 * currency methods.
 */
class FormattedBalance {

  /**
   * @var \Apigee\Edge\Api\Monetization\Entity\PrepaidBalance
   */
  private $balance;

  /**
   * @var \Drupal\apigee_m10n\Monetization
   */
  private $monetization;

  public function __construct(PrepaidBalance $balance, Monetization $monetization) {
    $this->balance = $balance;
    $this->monetization = $monetization;
  }

  public function getApproxTaxRate(): int {
    return $this->balance->getApproxTaxRate();
  }

  public function getCurrentBalance(): string {
    return $this->format($this->balance->getCurrentBalance());
  }

  public function getCurrentTotalBalance(): string {
    return $this->format($this->balance->getCurrentTotalBalance());
  }

  public function getCurrentUsage(): string {
    return $this->format($this->balance->getCurrentUsage());
  }

  public function getMonth(): string {
    return $this->balance->getMonth();
  }

  public function getPreviousBalance(): string {
    return $this->format($this->balance->getPreviousBalance());
  }

  public function getTax(): string {
    return $this->format($this->balance->getTax());
  }

  public function getTopups(): string {
    return $this->format($this->balance->getTopups());
  }

  public function getUsage(): string {
    return $this->format($this->balance->getUsage());
  }

  public function getYear(): int {
    return $this->balance->getYear();
  }

  public function getCurrencyName(): string {
    return $this->balance->getCurrency()->getName();
  }

  private function format(float $num) {
    return $this->monetization->formatCurrency($num, $this->balance->getCurrency()->getName());
  }
}