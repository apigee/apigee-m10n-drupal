<?php

/**
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

namespace Drupal\apigee_m10n\Entity\Storage;

use Apigee\Edge\Api\Monetization\Controller\AcceptedRatePlanControllerInterface;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;

/**
 * Defines an interface for the subscription entity storage class.
 */
interface SubscriptionStorageInterface extends FieldableMonetizationEntityStorageInterface {

  /**
   * Load Subscriptions by developer ID.
   *
   * @param string $developer_id
   *  Either the developer UUID or email address.
   *
   * @return array
   */
  public function loadByDeveloperId(string $developer_id): array;

  /**
   * @param string $developer_id
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\AcceptedRatePlanControllerInterface
   */
  public function getController(string $developer_id): AcceptedRatePlanControllerInterface;

  /**
   * Since entity storage classes are used during install, this is used instead
   * of dependency injection  to avoid an issue with the sdk controller
   * credentials not yet being available when the entity storage controller is
   * accessed.
   *
   * @return \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface
   *   The controller factory.
   */
  public function controllerFactory(): ApigeeSdkControllerFactoryInterface;

}
