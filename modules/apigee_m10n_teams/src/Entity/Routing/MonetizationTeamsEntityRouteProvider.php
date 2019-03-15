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

namespace Drupal\apigee_m10n_teams\Entity\Routing;

use Drupal\apigee_m10n\Entity\Routing\MonetizationEntityRouteProvider;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Route provider for monetization entities.
 */
class MonetizationTeamsEntityRouteProvider extends MonetizationEntityRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    // Add a team context aware route.
    if ($team_route = $this->getTeamRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.team", $team_route);
    }

    return $collection;
  }

  /**
   * Gets the team route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getTeamRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('team') && $entity_type->hasViewBuilderClass()) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('team'));
      $route
        ->addDefaults([
          '_entity_view' => "{$entity_type_id}.full",
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setOption('parameters', [
          'team' => ['type' => 'entity:team'],
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      // Set the corresponding route.
      if ($entity_type->hasLinkTemplate('developer')) {
        $route->setOption('_apigee_developer_route', "entity.{$entity_type_id}.developer");
      }
      elseif ($entity_type->hasLinkTemplate('canonical')) {
        $route->setOption('_apigee_developer_route', "entity.{$entity_type_id}.canonical");
      }

      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }
      return $route;
    }
  }

}
