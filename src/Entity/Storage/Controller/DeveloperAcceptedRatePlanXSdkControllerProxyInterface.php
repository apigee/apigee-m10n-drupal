<?php

/*
 * Copyright 2021 Google Inc.
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

namespace Drupal\apigee_m10n\Entity\Storage\Controller;

use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface;

/**
 * Some additional loaders for the purchased_product SDK storage controller proxy.
 */
interface DeveloperAcceptedRatePlanXSdkControllerProxyInterface extends EdgeEntityControllerInterface {

  /**
   * Loads all purchased product for a given developer.
   *
   * @param string $developer_id
   *   The developer ID.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Entity\DeveloperAcceptedRatePlanInterface[]
   *   A list of purchased products keyed by ID.
   */
  public function loadByDeveloperId(string $developer_id): array;

  /**
   * Loads a purchased_product by ID.
   *
   * @param string $developer_id
   *   The developer email or ID.
   * @param string $id
   *   The purchased_product ID.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Entity\DeveloperAcceptedRatePlanInterface
   *   The purchased_product.
   */
  public function loadById(string $developer_id, string $id): ?EntityInterface;

  /**
   * Creates an entity in Apigee Edge.
   *
   * Applies incoming values from Apigee Edge in $entity.
   *
   * @param \Apigee\Edge\Entity\EntityInterface $entity
   *   The created entity.
   */
  public function doCreate(EntityInterface $entity): void;

}
