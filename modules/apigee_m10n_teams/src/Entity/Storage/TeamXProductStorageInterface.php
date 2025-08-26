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

namespace Drupal\apigee_m10n_teams\Entity\Storage;

/**
 * An overridden storage controller interface for the `xproduct` entity.
 */
interface TeamXProductStorageInterface {

  /**
   * Gets a list of xproduct entities for a team.
   *
   * @param string $team_id
   *   The SDK company ID.
   *
   * @return array
   *   A list of xproduct entities that would have be available to a team.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getAvailablexProductsByTeam($team_id);

}
