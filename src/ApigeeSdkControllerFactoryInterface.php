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

use Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceControllerInterface;

/**
 * Interface for the `apigee_m10n.sdk_controller_factory` service.
 *
 * @package Drupal\apigee_m10n
 */
interface ApigeeSdkControllerFactoryInterface {

  /**
   * Creates a developer prepaid balance controller.
   *
   * @param string $developer_id
   *    Developer UUID or email address
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceControllerInterface
   */
  public function developerBalanceController(string $developer_id): DeveloperPrepaidBalanceControllerInterface;

  /**
   * Creates a company prepaid balance controller.
   *
   * @param string $company_id
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface
   */
  public function companyBalanceController(string $company_id): CompanyPrepaidBalanceControllerInterface;

  /**
   * Creates a package controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface
   *   The controller.
   */
  public function apiPackageController(): ApiPackageControllerInterface;
  
}
