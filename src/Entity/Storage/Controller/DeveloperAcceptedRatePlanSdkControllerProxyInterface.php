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

namespace Drupal\apigee_m10n\Entity\Storage\Controller;

use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface;

/**
 * Some additional loaders for the subscription SDK storage controller proxy.
 */
interface DeveloperAcceptedRatePlanSdkControllerProxyInterface extends EdgeEntityControllerInterface {

  /**
   * Loads all subscriptions for a given developer.
   *
   * @param string $developer_id
   *   The developer ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\DeveloperAcceptedRatePlanInterface[]
   *   A list of subscriptions keyed by ID.
   */
  public function loadByDeveloperId(string $developer_id): array;

  /**
   * Loads a subscription by ID.
   *
   * @param string $developer_id
   *   The developer email or ID.
   * @param string $id
   *   The subscription ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\DeveloperAcceptedRatePlanInterface
   *   The subscription.
   */
  public function loadById(string $developer_id, string $id): ?EntityInterface;

  /**
   * Creates an entity in Apigee Edge.
   *
   * Applies incoming values from Apigee Edge in $entity.
   *
   * @param \Apigee\Edge\Entity\EntityInterface $entity
   *   The created entity.
   * @param bool $suppress_warning
   *   Set to TRUE to suppress warnings. See \Apigee\Edge\Api\Monetization\Controller\AcceptedRatePlanControllerInterface::acceptRatePlan.
   */
  public function doCreate(EntityInterface $entity, bool $suppress_warning = FALSE): void;

}
