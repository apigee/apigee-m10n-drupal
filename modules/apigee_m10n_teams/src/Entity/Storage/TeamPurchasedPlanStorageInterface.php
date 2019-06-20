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

use Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlanInterface;

/**
 * Interface for the overridden `purchased_plan` storage controller.
 */
interface TeamPurchasedPlanStorageInterface {

  /**
   * Load all purchased plans for a team.
   *
   * @param string $team_id
   *   The team ID.
   *
   * @return array
   *   An array of purchased plans for the given team.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function loadByTeamId(string $team_id): array;

  /**
   * Loads a purchased_plan by ID.
   *
   * @param string $team_id
   *   The team ID.
   * @param string $id
   *   The purchased_plan ID.
   *
   * @return \Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlanInterface
   *   The purchased_plan.
   */
  public function loadTeamPurchasedPlanById(string $team_id, string $id): ?TeamsPurchasedPlanInterface;

}
