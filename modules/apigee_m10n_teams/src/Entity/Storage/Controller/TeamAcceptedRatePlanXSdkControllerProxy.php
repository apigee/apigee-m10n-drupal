<?php

/*
 * Copyright 2025 Google Inc.
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

use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_m10n\Entity\Storage\Controller\DeveloperAcceptedRatePlanXSdkControllerProxy;

/**
 * The `apigee_m10n.sdk_controller_proxy.rate_plan` service class.
 *
 * Responsible for proxying calls to the appropriate rate plan controllers. Rate
 * plan controllers require a product bundle ID for instantiation so we
 * sometimes need to get a controller at runtime for a given rate plan.
 */
class TeamAcceptedRatePlanXSdkControllerProxy extends DeveloperAcceptedRatePlanXSdkControllerProxy implements TeamAcceptedRatePlanXSdkControllerProxyInterface
{

  /**
   * {@inheritdoc}
   */
  public function loadByTeamId(string $team_id): array
  {
    // Get all purchases for this team.
    return $this->getPurchasedProductControllerByTeamId($team_id)
      ->getAllAcceptedRatePlans();
  }

  /**
   * {@inheritdoc}
   */
  public function loadTeamPurchasedProductById(string $team_id, string $id): ?EntityInterface
  {
    // Get all purchases for this team.
    return $this->getPurchasedProductControllerByTeamId($team_id)->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void
  {
    /** @var \Drupal\apigee_m10n_teams\Entity\TeamsPurchasedProductInterface $entity */
    if ($entity->isTeamPurchasedProduct()) {
      $controller = $this->getPurchasedProductControllerByTeamId($entity->getTeamEntity()->id());
      $acceptedRatePlan = $entity->decorated();
    } else {
      $controller = $this->getPurchasedProductController($entity);
      $acceptedRatePlan = $entity;
    }
    $controller->updateSubscription($acceptedRatePlan);
  }

  /**
   * Gets the purchased_plan controller by team ID.
   *
   * @param string $team_id
   *   The name of the team who has accepted the rate plan.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Controller\AcceptedRatePlanControllerInterface
   *   The purchased_plan controller.
   */
  protected function getPurchasedProductControllerByTeamId($team_id)
  {
    // Cache the controllers here for privacy.
    static $controller_cache = [];
    // Make sure a controller is cached.
    $controller_cache[$team_id] = $controller_cache[$team_id]
      ?? $this->controllerFactory()->appGroupAcceptedRatePlanController($team_id);

    return $controller_cache[$team_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedProductController(EntityInterface $entity)
  {
    /** @var \Apigee\Edge\Api\ApigeeX\Entity\DeveloperAcceptedRatePlanInterface $entity */
    return parent::getPurchasedProductController($entity);
  }
}
