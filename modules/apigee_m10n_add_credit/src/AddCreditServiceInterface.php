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

use Drupal\apigee_m10n\Entity\PurchasedPlanInterface;
use Drupal\apigee_m10n\Entity\PurchasedProductInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The interface for the add credit service..
 */
interface AddCreditServiceInterface {

  /**
   * A list of permissions that will be given to authenticated users on install.
   */
  const DEFAULT_AUTHENTICATED_PERMISSIONS = [
    'add credit to own developer prepaid balance',
  ];

  /**
   * Handles the mail callback for the `apigee_m10n_add_credit` module.
   *
   * @param string $key
   *   A key to identify the email sent. The final message ID for email altering
   *   will be {$module}_{$key}.
   * @param string &$message
   *   The email message.
   * @param array|null $params
   *   (optional) Parameters to be used in the email message.
   */
  public function mail($key, &$message, $params);

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

  /**
   * Handles `hook_entity_type_alter` for the `apigee_m10n_add_credit` module.
   *
   * @param array $entity_types
   *   An array of entity types.
   */
  public function entityTypeAlter(array &$entity_types);

  /**
   * Handles `hook_inline_entity_form_table_fields_alter` for the `apigee_m10n_add_credit` module.
   *
   * @param array $fields
   *   The fields, keyed by field name.
   * @param array $context
   *   An array with the following keys:
   *   - parent_entity_type: The type of the parent entity.
   *   - parent_bundle: The bundle of the parent entity.
   *   - field_name: The name of the reference field on which IEF is operating.
   *   - entity_type: The type of the referenced entities.
   *   - allowed_bundles: Bundles allowed on the reference field.
   *
   * @see \Drupal\inline_entity_form\InlineFormInterface::getTableFields()
   */
  public function inlineEntityFormTableFieldsAlter(&$fields, $context);

  /**
   * Handles `hook_field_info_alter` for the `apigee_m10n_add_credit` module.
   *
   * @param array $info
   *   Array of information on field types as collected by the "field type" plugin
   *   manager.
   */
  public function fieldInfoAlter(&$info);

  /**
   * Handles `hook_field_widget_form_alter` for the `apigee_m10n_add_credit` module.
   *
   * @param array $element
   *   The field widget form element as constructed by
   *   \Drupal\Core\Field\WidgetBaseInterface::form().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $context
   *   An associative array containing the following key-value pairs:
   *   - form: The form structure to which widgets are being attached. This may be
   *     a full form structure, or a sub-element of a larger form.
   *   - widget: The widget plugin instance.
   *   - items: The field values, as a
   *     \Drupal\Core\Field\FieldItemListInterface object.
   *   - delta: The order of this item in the array of subelements (0, 1, 2, etc).
   *   - default: A boolean indicating whether the form is being shown as a dummy
   *     form to set default values.
   */
  public function fieldWidgetFormAlter(&$element, FormStateInterface $form_state, $context);

  /**
   * Handles `hook_form_alter` for the `apigee_m10n_add_credit` module.
   *
   * @param array $form
   *   The form to alter.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form id.
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id);

  /**
   * Handles `hook_apigee_m10n_prepaid_balance_list_alter` for the `apigee_m10n_add_credit` module.
   *
   * @param array $build
   *   A renderable array representing the list.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The prepaid balance owner entity.
   */
  public function apigeeM10nPrepaidBalanceListAlter(array &$build, EntityInterface $entity);

  /**
   * Handles `hook_commerce_product_access` for the `apigee_m10n_add_credit` module.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The `commerce_product` entity.
   * @param string $operation
   *   The operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface|null
   *   The access result.
   */
  public function commerceProductAccess(EntityInterface $entity, $operation, AccountInterface $account);

  /**
   * Handles `hook_apigee_m10n_insufficient_balance_error_message_alter`.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The original message.
   * @param \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $purchased_plan
   *   The failed purchased_plan.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The altered message.
   */
  public function insufficientBalanceErrorMessageAlter(TranslatableMarkup &$message, PurchasedPlanInterface $purchased_plan);

  /**
   * Handles `hook_apigee_m10n_insufficient_balance_error_purchased_product_message_alter`.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The original message.
   * @param \Drupal\apigee_m10n\Entity\PurchasedProductInterface $purchased_product
   *   The failed purchased_product.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The altered message.
   */
  public function purchasedProductInsufficientBalanceErrorMessageAlter(TranslatableMarkup &$message, PurchasedProductInterface $purchased_product);

}
