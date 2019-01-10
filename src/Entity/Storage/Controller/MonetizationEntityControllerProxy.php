<?php

/*
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

namespace Drupal\apigee_m10n\Entity\Storage\Controller;

use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface;

/**
 * Monetization API specific default entity controller implementation.
 */
class MonetizationEntityControllerProxy implements EdgeEntityControllerInterface {

  /**
   * The decorated controller from the SDK.
   *
   * @var \Apigee\Edge\Controller\EntityCrudOperationsControllerInterface|\Apigee\Edge\Controller\NonPaginatedEntityListingControllerInterface|\Apigee\Edge\Controller\PaginatedEntityListingControllerInterface
   */
  private $controller;

  /**
   * ManagementApiEntityControllerBase constructor.
   *
   * @param \Apigee\Edge\Controller\EntityControllerInterface $controller
   *   A controller from the SDK.
   */
  public function __construct($controller) {
    $this->controller = $controller;
  }

  /**
   * {@inheritdoc}
   */
  public function create(EntityInterface $entity): void {
    $this->controller->create($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(string $id): EntityInterface {
    return $this->controller->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void {
    $this->controller->update($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $id): void {
    // Ignore returned entity object by Apigee Edge.
    $this->controller->delete($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll(): array {
    // Luckily we solved in the PHP API client that even on paginated endpoints
    // all entities can be retrieved with one single method call.
    return $this->controller->getEntities();
  }

}
