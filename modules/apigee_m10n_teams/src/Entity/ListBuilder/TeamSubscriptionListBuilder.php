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

namespace Drupal\apigee_m10n_teams\Entity\ListBuilder;

use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\ListBuilder\SubscriptionListBuilder;
use Drupal\apigee_m10n\Entity\SubscriptionInterface;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwareSubscription;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Entity list builder for team subscriptions.
 */
class TeamSubscriptionListBuilder extends SubscriptionListBuilder {

  /**
   * The team that is used to load subscriptions.
   *
   * @var \Drupal\apigee_edge_teams\Entity\TeamInterface
   */
  protected $team;

  /**
   * {@inheritdoc}
   */
  public function render(TeamInterface $team = NULL) {
    // From this point forward `$this->user` is a safe assumption.
    $this->team = $team;

    return parent::render();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function load() {
    return TeamRouteAwareSubscription::loadByTeamId($this->team->id());
  }

  /**
   * {@inheritdoc}
   */
  protected function unsubscribeUrl(SubscriptionInterface $subscription) {
    return $this->ensureDestination(Url::fromRoute('entity.subscription.team_unsubscribe_form', [
      'team' => $this->team->id(),
      'subscription' => $subscription->id(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function ratePlanUrl(SubscriptionInterface $subscription) {
    return $this->ensureDestination(Url::fromRoute('entity.rate_plan.team', [
      'team' => $this->team->id(),
      'package' => $subscription->getRatePlan()->getPackage()->id(),
      'rate_plan' => $subscription->getRatePlan()->id(),
    ]));
  }

}
