<?php

/*
 * @file
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_m10n_teams;

use Drupal\apigee_edge_teams\Entity\TeamInterface;

/**
 * Interface for the `apigee_m10n.teams` service.
 */
interface MonetizationTeamsInterface {

  /**
   * Handles `hook_entity_type_alter` for the `apigee_m10n_teams` module.
   *
   * @param array $entity_types
   *   An array of entity types.
   */
  public function entityTypeAlter(array &$entity_types);

  /**
   * Gets the current team from the route object.
   *
   * @return \Drupal\apigee_edge_teams\Entity\TeamInterface|null
   *   The current team from the route.
   */
  public function currentTeam(): ?TeamInterface;

}
