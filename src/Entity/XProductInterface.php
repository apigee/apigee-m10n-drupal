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

namespace Drupal\apigee_m10n\Entity;

use Apigee\Edge\Api\ApigeeX\Entity\ApiProductInterface;
use Drupal\apigee_edge\Entity\FieldableEdgeEntityInterface;

/**
 * Defines the interface for the `xproduct` entity objects.
 */
interface XProductInterface extends ApiProductInterface, FieldableEdgeEntityInterface {

  /**
   * Gets a list of product entities for a developer.
   *
   * @param string $developer_id
   *   The SDK developer ID.
   *
   * @return array
   *   A list of product entities that would have be available to the
   *   developer.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getAvailablexProductByDeveloper($developer_id);

  /**
   * Gets all product bundle entities.
   *
   * @return array
   *   A list of all product entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function loadAll();

}
