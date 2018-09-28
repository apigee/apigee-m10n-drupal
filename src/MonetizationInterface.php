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

use Apigee\Edge\Api\Monetization\Entity\CompanyInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

interface MonetizationInterface {

  /**
   * Tests whether the current organization has monetization enabled (a requirement for using this module).
   *
   * @return bool
   */
  function isMonetizationEnabled(): bool;

  /**
   * Checks access to a product for a given account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function apiProductAssignmentAccess(EntityInterface $entity, AccountInterface $account): AccessResultInterface ;

  /**
   * Takes in a developer UUID or email address, and a date specifying the report month and year,
   * and returns an array of prepaid balances.
   *
   * @param \Drupal\user\UserInterface $developer
   * @param \DateTimeImmutable $billingDate
   *
   * @return array|null
   */
  public function getDeveloperPrepaidBalances(UserInterface $developer, \DateTimeImmutable $billingDate): ?array;

  /**
   * Takes in a company name, and a date specifying the report month and year,
   * and returns an array of prepaid balances.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\CompanyInterface $company
   * @param \DateTimeImmutable $billingDate
   *
   * @return array|null
   */
  public function getCompanyPrepaidBalances(CompanyInterface $company, \DateTimeImmutable $billingDate): ?array;

  /**
   * Use the commerceguys internationalization library to format a currency based on a currency id.
   *
   * @param string $amount
   * @param string $currency_id
   *  Currency ID as defined by commerceguys/intl @see CommerceGuys\Intl\Currency\CurrencyRepository::getBaseDefinitions
   *
   * @return string
   */
  public function formatCurrency(string $amount, string $currency_id): string;
}
