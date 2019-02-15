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

namespace Drupal\apigee_m10n_teams\Controller;

use Drupal\apigee_edge_teams\Entity\TeamInterface;

/**
 * Controller for team balances.
 *
 * This is modeled after an entity list builder with some additions.
 * See: `\Drupal\Core\Entity\EntityListBuilder`
 */
class TeamPrepaidBalanceController extends PrepaidBalanceControllerBase {

  /**
   * View prepaid balance and account statements for teams.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The Apigee Edge team.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array or a redirect response.
   *
   * @throws \Exception
   */
  public function teamBalancePage(TeamInterface $team) {
    // Set the entity for this page call.
    $this->entity = $team;

    return $this->render();
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $balance_controller = $this->teamControllerFactory()->teamBalanceController($this->entity->id());
    return $balance_controller->getPrepaidBalance(new \DateTimeImmutable('now'));
  }

}
