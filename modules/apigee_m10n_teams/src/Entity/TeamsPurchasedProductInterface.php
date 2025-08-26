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

namespace Drupal\apigee_m10n_teams\Entity;

use Drupal\apigee_edge_teams\Entity\TeamInterface;

/**
 * Team specific additions to the the purchased_product entity.
 */
interface TeamsPurchasedProductInterface {

  const PURCHASED_PRODUCT_TYPE_TEAM      = 'TEAM';
  const PURCHASED_PRODUCT_TYPE_DEVELOPER = 'DEVELOPER';

  /**
   * Get's the purchased_product type.
   *
   * @return string
   *   The purchased_product type.
   */
  public function purchasedProductType();

  /**
   * Get's whether or not this is a team purchased_product.
   *
   * @return bool
   *   Whether this is a team purchased_product.
   */
  public function isTeamPurchasedProduct(): bool;

  /**
   * Get the team entity if it exists.
   *
   * @return \Drupal\apigee_edge_teams\Entity\TeamInterface
   *   Returns the team.
   */
  public function getTeamEntity(): ?TeamInterface;

  /**
   * Loads purchased products by team ID.
   *
   * @param string $team_id
   *   The `team` ID.
   *
   * @return array
   *   An array of purchased_product entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadByTeamId(string $team_id): array;

}
