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

namespace Drupal\Tests\apigee_m10n_teams\Kernel;

use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\AccountProphecyTrait;

/**
 * The base class for Monetization teams kernel tests.
 */
class MonetizationTeamsKernelTestBase extends MonetizationKernelTestBase {

  use AccountProphecyTrait;

  public static $modules = [
    'key',
    'user',
    'apigee_edge',
    'apigee_edge_teams',
    'apigee_m10n',
    'apigee_m10n_teams',
  ];

  /**
   * Create a new team.
   *
   * @return \Drupal\apigee_edge_teams\Entity\TeamInterface
   *   The team.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createTeam(): TeamInterface {
    $team = Team::create([
      'name' => $this->randomMachineName(),
      'displayName' => "{$this->getRandomGenerator()->word(8)} {$this->getRandomGenerator()->word(4)}",
    ]);

    $this->stack->queueMockResponse(['company' => ['company' => $team]]);
    $this->stack->queueMockResponse(['company' => ['company' => $team]]);
    $team->save();

    $this->stack->queueMockResponse(['company' => ['company' => $team]]);
    return Team::load($team->getName());
  }

}
