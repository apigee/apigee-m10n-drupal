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
use Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface;
use Drupal\apigee_m10n_teams\Entity\Routing\MonetizationTeamsEntityRouteProvider;
use Drupal\apigee_m10n_teams\Entity\Storage\TeamSubscriptionStorage;
use Drupal\apigee_m10n_teams\Entity\TeamAwareRatePlan;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwarePackage;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwareSubscription;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter\TeamSubscribeFormFormatter;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter\TeamSubscribeLinkFormatter;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The `apigee_m10n.teams` service.
 */
class MonetizationTeams implements MonetizationTeamsInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $route_match;

  /**
   * MonetizationTeams constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->route_match = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeAlter(array &$entity_types) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    if (isset($entity_types['package'])) {
      // Use our class to override the original entity class.
      $entity_types['package']->setClass(TeamRouteAwarePackage::class);
      // Create a link template for team packages.
      $entity_types['package']->setLinkTemplate('team', '/teams/{team}/monetization/package/{package}');
      // Get the entity route providers.
      $route_providers = $entity_types['package']->getRouteProviderClasses();
      // Override the `html` route provider.
      $route_providers['html'] = MonetizationTeamsEntityRouteProvider::class;
      $entity_types['package']->setHandlerClass('route_provider', $route_providers);
    }

    // Overrides for the `rate_plan` entity.
    if (isset($entity_types['rate_plan'])) {
      // Use our class to override the original entity class.
      $entity_types['rate_plan']->setClass(TeamAwareRatePlan::class);
      $entity_types['rate_plan']->setLinkTemplate('team', '/teams/{team}/monetization/package/{package}/plan/{rate_plan}');
      // Get the entity route providers.
      $route_providers = $entity_types['rate_plan']->getRouteProviderClasses();
      // Override the `html` route provider.
      $route_providers['html'] = MonetizationTeamsEntityRouteProvider::class;
      $entity_types['rate_plan']->setHandlerClass('route_provider', $route_providers);
    }

    // Overrides for the subscription entity.
    if (isset($entity_types['subscription'])) {
      // Use our class to override the original entity class.
      $entity_types['subscription']->setClass(TeamRouteAwareSubscription::class);
      // Override the storage class.
      $entity_types['subscription']->setStorageClass(TeamSubscriptionStorage::class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fieldFormatterInfoAlter(array &$info) {
    // Override the subscribe link and form formatters.
    $info['apigee_subscribe_form']['class'] = TeamSubscribeFormFormatter::class;
    $info['apigee_subscribe_link']['class'] = TeamSubscribeLinkFormatter::class;
  }

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($team = $this->currentTeam()) {
      // Get the access result.
      $access = $this->teamAccessCheck()->allowedIfHasTeamPermissions($team, $account, ["{$operation} {$entity->getEntityTypeId()}"]);
      // Team permission results completely override user permissions.
      return $access->isAllowed() ? $access : AccessResult::forbidden($access->getReason());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function currentTeam(): ?TeamInterface {
    // TODO: This call could be much smarter.
    // All team routes have the team ast the first parameter and we could be
    // checking a route list to make sure the team is part of a team route
    // similar to the `_apigee_monetization_route` route option.
    return $this->route_match->getParameter('team');
  }

  /**
   * Helper that gets the `TeamPermissionAccessCheck` service.
   *
   * This would be injected but injection causes a circular reference error when
   * rebuilding the container due to it's dependency on the
   * `apigee_edge_teams.team_permissions` service.
   *
   * See: <https://github.com/apigee/apigee-edge-drupal/pull/138#discussion_r259570088>.
   *
   * @return \Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface
   *   The team permission access checker.
   */
  protected function teamAccessCheck(): TeamPermissionAccessInterface {
    return \Drupal::service('apigee_m10n_teams.access_check.team_permission');
  }

}
