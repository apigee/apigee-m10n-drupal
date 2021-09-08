<?php

/**
 * Copyright 2021 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_m10n_add_credit;

use Drupal\apigee_edge\Entity\DeveloperInterface;
use Drupal\apigee_edge\Structure\DeveloperToUserConversionResult;
use Drupal\apigee_edge\Structure\UserToDeveloperConversionResult;
use Drupal\apigee_edge\UserDeveloperConverterInterface;
use Drupal\apigee_m10n_add_credit\Form\GeneralSettingsConfigForm;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\user\UserInterface;

/**
 * Class AddCreditDeveloperBillingType.
 *
 * Decorates the convertUser method to add default billing type.
 *
 * @package Drupal\apigee_m10n_add_credit
 */
class AddCreditDeveloperBillingType implements UserDeveloperConverterInterface {

  /**
   * The decorated service.
   *
   * @var \Drupal\apigee_edge\UserDeveloperConverterInterface
   */
  protected $userDeveloper;

  /**
   * The monetization service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * Constructor for AddCreditDeveloperBillingType.
   *
   * @param \Drupal\apigee_edge\UserDeveloperConverterInterface $user_developer
   *   The decorated UserDeveloperConverter service.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   */
  public function __construct(UserDeveloperConverterInterface $user_developer, MonetizationInterface $monetization) {
    $this->userDeveloper = $user_developer;
    $this->monetization = $monetization;
  }

  /**
   * {@inheritdoc}
   */
  public function convertDeveloper(DeveloperInterface $developer): DeveloperToUserConversionResult {
    return $this->userDeveloper->convertDeveloper($developer);
  }

  /**
   * {@inheritdoc}
   */
  public function convertUser(UserInterface $user): UserToDeveloperConversionResult {
    // Setting billing type only for ApigeeX org.
    if (TRUE === $this->monetization->isOrganizationApigeeXorHybrid()) {
      $convertedDeveloper = $this->userDeveloper->convertUser($user);
      if ($convertedDeveloper->getDeveloper()->getdeveloperId()) {
        $default_billingtype = \Drupal::config(GeneralSettingsConfigForm::CONFIG_NAME)->get('billing.billingtype');
        $default_billingtype = $default_billingtype ? $default_billingtype : 'postpaid';
        \Drupal::service('apigee_m10n.monetization')->updateBillingType($convertedDeveloper->getDeveloper()->getEmail(), strtoupper($default_billingtype));
      }
    }
    return $this->userDeveloper->convertUser($user);
  }

}
