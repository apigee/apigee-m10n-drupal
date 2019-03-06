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

namespace Drupal\apigee_m10n_teams\Entity\Storage;

use Drupal\apigee_m10n_teams\Entity\TeamRouteAwareSubscriptionInterface;

/**
 * Interface for the overridden `subscription` storage controller.
 */
interface TeamSubscriptionStorageInterface {

  /**
   * Load all subscriptions for a team.
   *
   * @param string $team_id
   *   The team ID.
   *
   * @return array
   *   An array of subscriptions for the given team.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
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
   * @return \Drupal\apigee_m10n_teams\Entity\TeamRouteAwareSubscriptionInterface
   *   The subscription.
   */
  public function loadTeamSubscriptionById(string $team_id, string $id): ?TeamRouteAwareSubscriptionInterface;

}
