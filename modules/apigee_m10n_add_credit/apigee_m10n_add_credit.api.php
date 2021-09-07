<?php

/**
 * @file
 * Copyright 2021 Google Inc.
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


use Drupal\apigee_edge\Entity\Developer;

/**
 * @file
 * Hooks for apigee_m10n_add_credit module.
 */

/**
 * @addtogroup hooks
 * @{
 */


 /**
  * Sets the billing type.
  *
  * @param \Drupal\apigee_edge\Entity\Developer $account
  *   The prepaid balance owner entity.
  */
 function hook_default_billingType_alter(Developer $account) {
 }

/**
 * @} End of "addtogroup hooks".
 */
