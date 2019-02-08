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

use Apigee\Edge\Entity\EntityInterface;

/**
 * Some additional loaders for the subscription SDK storage controller proxy.
 */
interface TeamAcceptedRatePlanSdkControllerProxyInterface {

  /**
   * Loads all subscriptions for a given team.
   *
   * @param string $team_id
   *   The team ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlanInterface[]
   *   A list of subscriptions keyed by ID.
   */
  public function loadByTeamId(string $team_id): array;

  /**
   * Loads a subscription by ID.
   *
   * @param string $team_id
   *   The team ID.
   * @param string $id
   *   The subscription ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlanInterface
   *   The subscription.
   */
  public function loadTeamSubscriptionById(string $team_id, string $id): ?EntityInterface;

}
