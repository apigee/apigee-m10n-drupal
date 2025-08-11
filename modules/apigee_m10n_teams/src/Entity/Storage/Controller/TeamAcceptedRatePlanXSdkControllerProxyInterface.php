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

/**
 * Some additional loaders for the purchased_product SDK storage controller proxy.
 */
interface TeamAcceptedRatePlanXSdkControllerProxyInterface {

  /**
   * Loads all purchased products for a given team.
   *
   * @param string $team_id
   *   The team ID.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Entity\AppGroupAcceptedRatePlanInterface[]
   *   A list of purchased products keyed by ID.
   */
  public function loadByTeamId(string $team_id): array;

  /**
   * Loads a purchased_product by ID.
   *
   * @param string $team_id
   *   The team ID.
   * @param string $id
   *   The purchased_product ID.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Entity\AppGroupAcceptedRatePlanInterface
   *   The purchased_product.
   */
  public function loadTeamPurchasedProductById(string $team_id, string $id): ?EntityInterface;

}
