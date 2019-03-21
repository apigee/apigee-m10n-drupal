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

namespace Drupal\apigee_m10n_teams;

use Apigee\Edge\Api\Monetization\Controller\CompanyAcceptedRatePlanController;
use Apigee\Edge\Api\Monetization\Controller\CompanyTermsAndConditionsController;
use Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface;

/**
 * Interface for the `apigee_m10n_teams.sdk_controller_factory` service.
 */
interface TeamSdkControllerFactoryInterface {

  /**
   * Creates a company accepted rate plan controller.
   *
   * @param string $company_id
   *   The name of the company.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\CompanyAcceptedRatePlanController
   *   A company accepted rate plan controller.
   */
  public function companyAcceptedRatePlanController(string $company_id): CompanyAcceptedRatePlanController;

  /**
   * Creates company terms and conditions controller.
   *
   * @param string $company_id
   *   Company ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\CompanyTermsAndConditionsController
   *   The developer controller.
   */
  public function companyTermsAndConditionsController(string $company_id): CompanyTermsAndConditionsController;

  /**
   * Creates a team prepaid balance controller.
   *
   * @param string $team_id
   *   The team ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface
   *   The controller.
   */
  public function teamBalanceController($team_id): CompanyPrepaidBalanceControllerInterface;

}
