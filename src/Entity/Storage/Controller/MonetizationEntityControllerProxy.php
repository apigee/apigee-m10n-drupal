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

use Apigee\Edge\Controller\EntityControllerInterface;
use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryAwareTrait;

/**
 * Monetization API specific default entity controller implementation.
 */
abstract class MonetizationEntityControllerProxy implements EdgeEntityControllerInterface {

  use ApigeeSdkControllerFactoryAwareTrait;

  /**
   * Get's the controller for this entity type.
   *
   * SDK Entity storage controllers have to be lazy loaded because they take the
   * API client as part of their constructor. Loading the API client without
   * properly configured credentials causes an error.
   *
   * @return \Apigee\Edge\Controller\EntityControllerInterface
   *   The entity controller.
   */
  abstract protected function controller(): EntityControllerInterface;

  /**
   * {@inheritdoc}
   */
  public function create(EntityInterface $entity): void {
    $this->controller()->create($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(string $id): EntityInterface {
    return $this->controller()->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void {
    $this->controller()->update($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $id): void {
    // Ignore returned entity object by Apigee Edge.
    $this->controller()->delete($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll(): array {
    // Luckily we solved in the PHP API client that even on paginated endpoints
    // all entities can be retrieved with one single method call.
    return $this->controller()->getEntities();
  }

}
