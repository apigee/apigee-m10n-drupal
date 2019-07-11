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

namespace Drupal\apigee_m10n_teams\Entity\Storage\Controller;

use Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlanInterface;
use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_m10n\Entity\Storage\Controller\DeveloperAcceptedRatePlanSdkControllerProxy;

/**
 * The `apigee_m10n.sdk_controller_proxy.rate_plan` service class.
 *
 * Responsible for proxying calls to the appropriate rate plan controllers. Rate
 * plan controllers require a product bundle ID for instantiation so we
 * sometimes need to get a controller at runtime for a given rate plan.
 */
class TeamAcceptedRatePlanSdkControllerProxy extends DeveloperAcceptedRatePlanSdkControllerProxy implements TeamAcceptedRatePlanSdkControllerProxyInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByTeamId(string $team_id): array {
    // Get all purchases for this team.
    return $this->getPurchasedPlanControllerByTeamId($team_id)
      ->getAllAcceptedRatePlans();
  }

  /**
   * {@inheritdoc}
   */
  public function loadTeamPurchasedPlanById(string $team_id, string $id): ?EntityInterface {
    // Get all purchases for this team.
    return $this->getPurchasedPlanControllerByTeamId($team_id)->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void {
    $controller = $entity instanceof CompanyAcceptedRatePlanInterface
      ? $this->getPurchasedPlanControllerByTeamId($entity->getCompany()->id())
      : $this->getPurchasedPlanController($entity);

    $controller->updateSubscription($entity);
  }

  /**
   * Gets the purchased_plan controller by team ID.
   *
   * @param string $team_id
   *   The name of the team who has accepted the rate plan.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\AcceptedRatePlanControllerInterface
   *   The purchased_plan controller.
   */
  protected function getPurchasedPlanControllerByTeamId($team_id) {
    // Cache the controllers here for privacy.
    static $controller_cache = [];
    // Make sure a controller is cached.
    $controller_cache[$team_id] = $controller_cache[$team_id]
      ?? $this->controllerFactory()->companyAcceptedRatePlanController($team_id);

    return $controller_cache[$team_id];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPurchasedPlanController(EntityInterface $entity) {
    if ($entity instanceof CompanyAcceptedRatePlanInterface) {
      /** @var \Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlanInterface $entity */
      if (!($company = $entity->getCompany())) {
        // If the team ID is not set, we have no way to get the controller
        // since it depends on the team ID.
        throw new RuntimeException('The team must be set to create a purchased_plan controller.');
      }
      // Get the controller.
      return $this->getPurchasedPlanControllerByTeamId($company->id());
    }
    else {
      /** @var \Apigee\Edge\Api\Monetization\Entity\DeveloperAcceptedRatePlanInterface $entity */
      return parent::getPurchasedPlanController($entity);
    }
  }

}
