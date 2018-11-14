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

use Apigee\Edge\Api\Management\Controller\OrganizationControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface;
use Apigee\Edge\Api\Monetization\Entity\CompanyInterface;
use Drupal\user\UserInterface;

/**
 * Interface for the `apigee_m10n.sdk_controller_factory` service.
 *
 * @package Drupal\apigee_m10n
 */
interface ApigeeSdkControllerFactoryInterface {

  /**
   * Gets and org controller.
   *
   * @return \Apigee\Edge\Api\Management\Controller\OrganizationControllerInterface
   *   The organization controller.
   */
  public function organizationController(): OrganizationControllerInterface;

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
   * Creates a company prepaid balance controller.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\CompanyInterface $company
   *   The company.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface
   *   The company balance controller.
   */
  public function companyBalanceController(CompanyInterface $company): CompanyPrepaidBalanceControllerInterface;

  /**
   * Creates a package controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface
   *   The controller.
   */
  public function apiPackageController(): ApiPackageControllerInterface;

  /**
   * Creates a rate plan controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface
   *   The controller.
   */
  public function packageRatePlanController($package_id): RatePlanControllerInterface;

}
