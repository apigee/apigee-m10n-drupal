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

use Apigee\Edge\Api\Management\Entity\CompanyInterface;
use Apigee\Edge\Api\ApigeeX\Controller\ApiProductControllerInterface as ApixProductControllerInterface;
use Apigee\Edge\Api\ApigeeX\Controller\RatePlanControllerInterface as ApigeexRatePlanControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\ApiProductControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\DeveloperAcceptedRatePlanController;
use Apigee\Edge\Api\ApigeeX\Controller\DeveloperAcceptedRatePlanController as ApigeeXDeveloperAcceptedRatePlanController;
use Apigee\Edge\Api\ApigeeX\Controller\DeveloperBillingTypeController;
use Apigee\Edge\Api\Monetization\Controller\DeveloperController;
use Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\ApigeeX\Controller\DeveloperPrepaidBalanceControllerInterface as ApigeeXDeveloperPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\DeveloperReportDefinitionControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\DeveloperTermsAndConditionsController;
use Apigee\Edge\Api\Monetization\Controller\SupportedCurrencyControllerInterface;
use Apigee\Edge\Api\ApigeeX\Controller\SupportedCurrencyControllerInterface as ApigeeXSupportedCurrencyControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\TermsAndConditionsControllerInterface;
use Drupal\user\UserInterface;

/**
 * Interface for the `apigee_m10n.sdk_controller_factory` service.
 */
interface ApigeeSdkControllerFactoryInterface {

  /**
   * Creates a monetization developer controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\DeveloperController
   *   The developer controller.
   */
  public function developerController(): DeveloperController;

  /**
   * Creates developer terms and conditions controller.
   *
   * @param string $developer_id
   *   Developer ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\DeveloperTermsAndConditionsController
   *   The developer controller.
   */
  public function developerTermsAndConditionsController(string $developer_id): DeveloperTermsAndConditionsController;

  /**
   * Creates a developer prepaid balance controller.
   *
   * @param \Drupal\user\UserInterface $developer
   *   The developer drupal user.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceControllerInterface
   *   The controller.
   */
  public function developerBalanceController(UserInterface $developer): DeveloperPrepaidBalanceControllerInterface;

  /**
   * Creates a ApigeeX developer prepaid balance controller.
   *
   * @param \Drupal\user\UserInterface $developer
   *   The developer drupal user.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Controller\DeveloperPrepaidBalanceControllerInterface
   *   The controller.
   */
  public function developerBalancexController(UserInterface $developer): ApigeexDeveloperPrepaidBalanceControllerInterface;

  /**
   * Creates a ApigeeX developer billing type controller.
   *
   * @param string $developer_id
   *   The developer email address.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Controller\DeveloperBillingTypeController
   *   The controller.
   */
  public function developerBillingTypeController(string $developer_id): DeveloperBillingTypeController;

  /**
   * Creates a company prepaid balance controller.
   *
   * @param \Apigee\Edge\Api\Management\Entity\CompanyInterface $company
   *   The company.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface
   *   The company balance controller.
   */
  public function companyBalanceController(CompanyInterface $company): CompanyPrepaidBalanceControllerInterface;

  /**
   * Creates a product controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\ApiProductControllerInterface
   *   The controller.
   */
  public function apiProductController(): ApiProductControllerInterface;

  /**
   * Creates an API package SDK controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface
   *   The SDK controller.
   */
  public function apiPackageController(): ApiPackageControllerInterface;

  /**
   * Creates an API xproduct SDK controller.
   *
   * @return \Apigee\Edge\Api\ApigeeeX\Controller\ApiProductControllerInterface
   *   The SDK controller.
   */
  public function apixProductController(): ApixProductControllerInterface;

  /**
   * Creates a rate plan controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface
   *   The controller.
   */
  public function ratePlanController($product_bundle_id): RatePlanControllerInterface;

  /**
   * Creates a rate plan controller.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Controller\RatePlanControllerInterface
   *   The controller.
   */
  public function xratePlanController($product_bundle_id): ApigeexRatePlanControllerInterface;

  /**
   * Creates a developer accepted rate plan controller.
   *
   * @param string $developer_id
   *   The email or id of the developer.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\DeveloperAcceptedRatePlanController
   *   A developer accepted rate plan controller.
   */
  public function developerAcceptedRatePlanController(string $developer_id): DeveloperAcceptedRatePlanController;

  /**
   * Creates a developer accepted rate plan controller.
   *
   * @param string $developer_id
   *   The email or id of the developer.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Controller\DeveloperAcceptedRatePlanController
   *   A developer accepted rate plan controller.
   */
  public function developerAcceptedRatePlanxController(string $developer_id): ApigeexDeveloperAcceptedRatePlanController;

  /**
   * Creates terms and conditions controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\TermsAndConditionsControllerInterface
   *   Terms and conditions.
   */
  public function termsAndConditionsController(): TermsAndConditionsControllerInterface;

  /**
   * Creates a supported currency controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\SupportedCurrencyControllerInterface
   *   The controller.
   */
  public function supportedCurrencyController(): SupportedCurrencyControllerInterface;

  /**
   * Creates a supported currency controller.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Controller\SupportedCurrencyControllerInterface
   *   The controller.
   */
  public function supportedCurrencyxController(): ApigeeXSupportedCurrencyControllerInterface;

  /**
   * Creates a prepaid balance reports controller.
   *
   * @param string $developer_id
   *   UUID or email address of a developer.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\DeveloperReportDefinitionControllerInterface
   *   The controller.
   */
  public function developerReportDefinitionController(string $developer_id): DeveloperReportDefinitionControllerInterface;

}
