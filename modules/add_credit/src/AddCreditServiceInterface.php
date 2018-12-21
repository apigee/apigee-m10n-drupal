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

namespace Drupal\apigee_m10n_add_credit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * The interface for the add credit service..
 */
interface AddCreditServiceInterface {

  /**
   * Handles the mail callback for the `apigee_m10n_add_credit` module.
   *
   * @param string $key
   *   A key to identify the email sent. The final message ID for email altering
   *   will be {$module}_{$key}.
   * @param string &$message
   *   The email message.
   * @param array|null $params
   *   (optional) Parameters to be used int he email message.
   */
  public function mail($key, &$message, $params);

  /**
   * Implementation for `apigee_m10n_add_credit_commerce_order_item_create()`.
   *
   * When an order item is created, we need to check to see if the product is an
   * add credit item. If it is, we should store a reference to the developer
   * that we are topping up so it can be used to credit the appropriate account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The commerce order item.
   *
   * @todo: Add support for team context.
   */
  public function commerceOrderItemCreate(EntityInterface $entity);

  /**
   * Implementation for `apigee_m10n_add_credit_entity_base_field_info()`.
   *
   * This will add the `apigee_add_credit_enabled` base field to all
   * `commerce_product` entities. The base field will only be used for bundles
   * that have and "Add  credit" enabled.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array|void
   *   Return an array if fields are to be added to this entity type.
   */
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type);

  /**
   * Implementation for `apigee_m10n_add_credit_entity_bundle_field_info()`.
   *
   * This enables the `apigee_add_credit_enabled` base field for bundles that
   * have the `apigee_m10n_enable_add_credit` set.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   * @param array $base_field_definitions
   *   Existing base field definition.
   *
   * @return array|void
   *   Return an array if fields are to be added to this bundle.
   */
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions);

  /**
   * Handles `hook_form_FORM_ID_alter` for the `apigee_m10n_add_credit` module.
   *
   * This will alter the add and edit forms enabling the
   * `apigee_m10n_enable_add_credit` setting to be set for
   * `commerce_product_type` config entities.
   *
   * @param array|null $form
   *   The add or edit form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   */
  public function formCommerceProductTypeEditFormAlter(&$form, FormStateInterface $form_state, $form_id);

}
