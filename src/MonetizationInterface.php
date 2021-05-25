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


namespace Drupal\apigee_m10n;

use Apigee\Edge\Api\Management\Entity\OrganizationInterface;
use Apigee\Edge\Api\Monetization\Entity\CompanyInterface;
use Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface;
use Apigee\Edge\Api\Monetization\Structure\LegalEntityTermsAndConditionsHistoryItem;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\XRatePlanInterface;

/**
 * Interface MonetizationInterface.
 */
interface MonetizationInterface {

  /**
   * A list of permissions that will be given to authenticated users on install.
   */
  const DEFAULT_AUTHENTICATED_PERMISSIONS = [
    'view rate_plan',
    'view xrate_plan',
    'purchase rate_plan',
    'purchase xrate_plan',
    'view own purchased_plan',
    'view own purchased_product',
    'update own purchased_plan',
    'update own purchased_product',
    'view own prepaid balance',
    'refresh own prepaid balance',
    'download prepaid balance reports',
    'view own billing details',
    'download own reports',
  ];

  /**
   * Developer billing type attribute name.
   */
  const BILLING_TYPE_ATTR = 'MINT_BILLING_TYPE';

  /**
   * Tests whether the current organization has monetization enabled.
   *
   * A monitization enabled org is a requirement for using this module.
   *
   * @return bool
   *   Whether or not monetization is enabled.
   */
  public function isMonetizationEnabled(): bool;

  /**
   * Checks access to a product for a given account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The 'api_product'  entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether or not the user has access to the entity.
   */
  public function apiProductAssignmentAccess(EntityInterface $entity, AccountInterface $account): AccessResultInterface;

  /**
   * Get's the prepaid balance for a developer.
   *
   * Takes in a developer UUID or email address, and a date specifying the
   * report month and year, and returns an array of prepaid balances.
   *
   * @param \Drupal\user\UserInterface $developer
   *   The developer user.
   * @param \DateTimeImmutable $billingDate
   *   The date for the billing report.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface[]|null
   *   The balance list or null if no balances are available.
   */
  public function getDeveloperPrepaidBalances(UserInterface $developer, \DateTimeImmutable $billingDate): ?array;

  /**
   * Get's the prepaid balance for a team.
   *
   * Takes in a company name, and a date specifying the report month and year,
   * and returns an array of prepaid balances.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\CompanyInterface $company
   *   The team.
   * @param \DateTimeImmutable $billingDate
   *   The date for the billing report.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface[]|null
   *   The balance list or null if no balances are available.
   */
  public function getCompanyPrepaidBalances(CompanyInterface $company, \DateTimeImmutable $billingDate): ?array;

  /**
   * Format an amount using the `CommerceGuys\Intl` library.
   *
   * Use the commerceguys internationalization library to format a currency
   * based on a currency id.
   *
   * @param string $amount
   *   The money amount.
   * @param string $currency_id
   *   Currency ID as defined by `commerceguys/intl`.
   *
   * @see \CommerceGuys\Intl\Currency\CurrencyRepository::getBaseDefinitions
   *
   * @return string
   *   The formatted amount as a string.
   */
  public function formatCurrency(string $amount, string $currency_id): string;

  /**
   * Get supported currencies for an organization.
   *
   * @return array
   *   An array of supported currency entities.
   */
  public function getSupportedCurrencies(): ?array;

  /**
   * Returns a CSV string for prepaid balances.
   *
   * @param string $developer_id
   *   The developer id.
   * @param \DateTimeImmutable $date
   *   The month for the prepaid balances.
   * @param string $currency
   *   The currency id. Example: usd.
   *
   * @return null|string
   *   A CSV string of prepaid balances.
   */
  public function getPrepaidBalanceReport(string $developer_id, \DateTimeImmutable $date, string $currency): ?string;

  /**
   * Check if developer accepted latest terms and conditions.
   *
   * @param string $developer_id
   *   Developer ID.
   *
   * @return bool|null
   *   User terms and conditions acceptance flag.
   */
  public function isLatestTermsAndConditionAccepted(string $developer_id): ?bool;

  /**
   * Get latest terms and condition.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface
   *   Latest term and condition.
   */
  public function getLatestTermsAndConditions(): ?TermsAndConditionsInterface;

  /**
   * Accepts a terms and conditions by its id.
   *
   * @param string $developer_id
   *   Developer ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Structure\LegalEntityTermsAndConditionsHistoryItem|null
   *   Terms and conditions history item.
   */
  public function acceptLatestTermsAndConditions(string $developer_id): ?LegalEntityTermsAndConditionsHistoryItem;

  /**
   * Check if developer accepted latest terms and conditions.
   *
   * @param string $developer_id
   *   Developer ID.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan
   *   Rate plan entity.
   *
   * @return bool|null
   *   Check if developer is subscribed to a plan.
   */
  public function isDeveloperAlreadySubscribed(string $developer_id, RatePlanInterface $rate_plan): bool;

  /**
   * Check if developer has subscribed to a plan.
   *
   * @param string $developer_id
   *   Developer ID.
   * @param \Drupal\apigee_m10n\Entity\XRatePlanInterface $xrate_plan
   *   Rate plan entity.
   *
   * @return bool|null
   *   Check if developer is subscribed to a plan.
   */
  public function isDeveloperAlreadySubscribedX(string $developer_id, XRatePlanInterface $xrate_plan): bool;

  /**
   * Handles `hook_form_FORM_ID_alter` (user_admin_permissions) for this module.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID (should always be `user_admin_permissions`).
   */
  public function formUserAdminPermissionsAlter(&$form, FormStateInterface $form_state, $form_id);

  /**
   * Handles `hook_ENTITY_TYPE_presave` (user_role) for this module.
   *
   * @param \Drupal\user\RoleInterface $user_role
   *   The user role.
   */
  public function userRolePresave(RoleInterface $user_role);

  /**
   * Gets the Apigee Edge management organization entity.
   *
   * @return \Apigee\Edge\Api\Management\Entity\OrganizationInterface
   *   The organization entity.
   */
  public function getOrganization(): ?OrganizationInterface;

  /**
   * Returns true if developer billing type is prepaid, false if postpaid.
   *
   * @param \Drupal\user\UserInterface $account
   *   The developer account.
   *
   * @return bool
   *   True if developer is prepaid.
   */
  public function isDeveloperPrepaid(UserInterface $account): bool;

  /**
   * Returns true if current organization is ApigeeX, false if otherwise.
   *
   * @return bool
   *   True if organization is ApigeeX.
   */
  public function isOrganizationApigeeX(): bool;

  /**
   * Returns true if current organization is ApigeeX or Hybrid, false otherwise.
   *
   * @return bool
   *   True if organization is ApigeeX or Hybrid.
   */
  public function isOrganizationApigeeXorHybrid(): bool;

}
