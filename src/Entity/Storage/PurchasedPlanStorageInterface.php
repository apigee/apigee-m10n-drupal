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

use Drupal\apigee_m10n\Entity\PurchasedPlanInterface;

/**
 * Defines an interface for the purchased_plan entity storage class.
 */
interface PurchasedPlanStorageInterface {

  /**
   * Load a purchased_plan by developer_id and the purchased_plan ID.
   *
   * @param string $developer_id
   *   The developer ID the purchased_plan belongs to.
   * @param string $id
   *   The purchased_plan ID.
   *
   * @return \Drupal\apigee_m10n\Entity\PurchasedPlanInterface
   *   The purchased_plan.
   */
  public function loadById(string $developer_id, string $id): ?PurchasedPlanInterface;

  /**
   * Load purchases by developer ID.
   *
   * @param string $developer_id
   *   Either the developer UUID or email address.
   *
   * @return array
   *   An array of `purchased_plan` entities for the given developer.
   */
  public function loadByDeveloperId(string $developer_id): array;

}
