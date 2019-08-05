<?php

/*
 * Copyright 2019 Google Inc.
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

namespace Drupal\apigee_m10n_add_credit\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Add Credit Log entities.
 *
 * @ingroup apigee_m10n_add_credit
 */
interface AddCreditLogInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Add Credit Log name.
   *
   * @return string
   *   Name of the Add Credit Log.
   */
  public function getName();

  /**
   * Sets the Add Credit Log name.
   *
   * @param string $name
   *   The Add Credit Log name.
   *
   * @return \Drupal\apigee_m10n_add_credit\Entity\AddCreditLogInterface
   *   The called Add Credit Log entity.
   */
  public function setName($name);

  /**
   * Gets the Add Credit Log creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Add Credit Log.
   */
  public function getCreatedTime();

  /**
   * Sets the Add Credit Log creation timestamp.
   *
   * @param int $timestamp
   *   The Add Credit Log creation timestamp.
   *
   * @return \Drupal\apigee_m10n_add_credit\Entity\AddCreditLogInterface
   *   The called Add Credit Log entity.
   */
  public function setCreatedTime($timestamp);

}
