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

use Apigee\Edge\Api\Monetization\Entity\Balance;

class FormattedBalance {

  private $balance;

  private $currency_formatter;

  public function __construct($balance, $currency_formatter) {
    $this->balance = $balance;
    $this->currency_formatter = $currency_formatter;
  }

  public function getCurrencyName() {
    return $this->balance->getCurrency()->getName();
  }

  public function getPreviousBalance() {
    return $this->format($this->balance->getPreviousBalance());
  }

  public function getTopups() {
    return $this->format($this->balance->getTopups());
  }

  public function getUsage() {
    return $this->format($this->balance->getUsage());
  }

  public function getTax() {
    return $this->format($this->balance->getTax());
  }

  public function getCurrentBalance() {
    return $this->format($this->balance->getCurrentBalance());
  }

  private function format(float $num) {
    return $this->currency_formatter->format($num, $this->balance->getCurrency()->getName());
  }
}