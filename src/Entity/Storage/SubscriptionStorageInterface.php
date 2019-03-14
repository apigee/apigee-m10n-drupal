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

namespace Drupal\apigee_m10n\Entity\Storage;

use Drupal\apigee_m10n\Entity\SubscriptionInterface;

/**
 * Defines an interface for the subscription entity storage class.
 */
interface SubscriptionStorageInterface {

  /**
   * Load an individual subscription by developer_id and the subscription ID.
   *
   * @param string $developer_id
   *   The developer ID the subscription belongs to.
   * @param string $id
   *   The subscription ID.
   *
   * @return \Drupal\apigee_m10n\Entity\SubscriptionInterface
   *   The subscription.
   */
  public function loadById(string $developer_id, string $id): ?SubscriptionInterface;

  /**
   * Load Subscriptions by developer ID.
   *
   * @param string $developer_id
   *   Either the developer UUID or email address.
   *
   * @return array
   *   An array of subscriptions for the given developer.
   */
  public function loadByDeveloperId(string $developer_id): array;

}
