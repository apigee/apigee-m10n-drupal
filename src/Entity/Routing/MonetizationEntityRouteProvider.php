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

namespace Drupal\apigee_m10n\Entity\Routing;

use Drupal\apigee_m10n\Entity\Controller\ContextDependentEntityController;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Route provider for monetization entities.
 */
class MonetizationEntityRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($developer_route = $this->getDeveloperRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.developer", $developer_route);
    }

    return $collection;
  }

  /**
   * Gets the developer route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDeveloperRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('developer') && $entity_type->hasViewBuilderClass()) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('developer'));
      $route
        ->addDefaults([
          '_entity_view' => "{$entity_type_id}.full",
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        ])
        ->setRequirement('_entity_developer_access', "{$entity_type_id}.view")
        ->setOption('parameters', [
          'user' => ['type' => 'entity:user'],
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }
      return $route;
    }
  }

  /**
   * Overrides the the canonical route.
   *
   * The canonical route will not have developer context so we have to redirect
   * to the developer route. Keeping the canonical route will allow a permalink
   * to the entity without developer context.
   *
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('canonical') && $entity_type->hasViewBuilderClass()) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('canonical'));
      $route
        ->addDefaults([
          '_controller' => ContextDependentEntityController::class . '::developerRedirect',
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setOption('entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }
      return $route;
    }
  }

}
