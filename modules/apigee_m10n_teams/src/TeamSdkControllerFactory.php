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
use Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceController;
use Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface;
use Drupal\apigee_m10n\ApigeeSdkControllerFactory;

/**
 * An `apigee_m10n.sdk_controller_factory` overridden service class.
 */
class TeamSdkControllerFactory extends ApigeeSdkControllerFactory implements TeamSdkControllerFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function companyAcceptedRatePlanController(string $company_id): CompanyAcceptedRatePlanController {
    if (empty($this->controllers[__FUNCTION__][$company_id])) {
      // Don't assume the bucket has been initialized.
      $this->controllers[__FUNCTION__] = $this->controllers[__FUNCTION__] ?? [];
      // Create a new balance controller.
      $this->controllers[__FUNCTION__][$company_id] = new CompanyAcceptedRatePlanController(
        $company_id,
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__][$company_id];
  }

  /**
   * {@inheritdoc}
   */
  public function companyTermsAndConditionsController(string $company_id): CompanyTermsAndConditionsController {
    if (empty($this->controllers[__FUNCTION__][$company_id])) {
      // Don't assume the bucket has been initialized.
      $this->controllers[__FUNCTION__] = $this->controllers[__FUNCTION__] ?? [];
      // Create a new balance controller.
      $this->controllers[__FUNCTION__][$company_id] = new CompanyTermsAndConditionsController(
        $company_id,
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__][$company_id];
  }

  /**
   * {@inheritdoc}
   */
  public function teamBalanceController($team_id): CompanyPrepaidBalanceControllerInterface {
    if (empty($this->controllers[__FUNCTION__][$team_id])) {
      // Don't assume the bucket has been initialized.
      $this->controllers[__FUNCTION__] = $this->controllers[__FUNCTION__] ?? [];
      // Create a new balance controller.
      $this->controllers[__FUNCTION__][$team_id] = new CompanyPrepaidBalanceController(
        $team_id,
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__][$team_id];
  }

}
