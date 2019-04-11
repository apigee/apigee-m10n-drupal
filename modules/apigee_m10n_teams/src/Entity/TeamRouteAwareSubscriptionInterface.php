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

namespace Drupal\apigee_m10n_teams\Entity;

/**
 * Team specific additions to the the subscription entity.
 */
interface TeamRouteAwareSubscriptionInterface {

  const SUBSCRIPTION_TYPE_TEAM      = 'TEAM';
  const SUBSCRIPTION_TYPE_DEVELOPER = 'DEVELOPER';

  /**
   * Get's the subscription type.
   *
   * @return string
   *   The subscription type.
   */
  public function subscriptionType();

  /**
   * {@inheritdoc}
   */
  public function getTeam();

  /**
   * Get's whether or not this is a team subscription.
   *
   * @return bool
   *   Whether this is a team subscription.
   */
  public function isTeamSubscription(): bool;

  /**
   * Loads subscriptions by team ID.
   *
   * @param string $team_id
   *   The `team` ID.
   *
   * @return array
   *   An array of Subscription entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadByTeamId(string $team_id): array;

}
