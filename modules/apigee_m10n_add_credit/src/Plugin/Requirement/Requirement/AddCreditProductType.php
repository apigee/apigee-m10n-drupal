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

namespace Drupal\apigee_m10n_add_credit\Plugin\Requirement\Requirement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\requirement\Plugin\RequirementBase;

/**
 * Check that the "Add credit" product type has been configured.
 *
 * @Requirement(
 *   id = "add_credit_product_type",
 *   group="apigee_m10n_add_credit",
 *   label = "Add Credit product type",
 *   description = "Configure an add credit product type to handle prepaid balance top ups.",
 *   action_button_label="Create product type",
 *   severity="error",
 *   dependencies={
 *      "apigee_edge_connection",
 *   }
 * )
 */
class AddCreditProductType extends RequirementBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('An add credit product type will be created. Are you sure you want to continue?'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Get or create the order item type.
    $order_item_type_storage = $this->getEntityTypeManager()
      ->getStorage('commerce_order_item_type');
    $order_item_type = $order_item_type_storage->load('add_credit');

    if (!$order_item_type) {
      $order_item_type = $order_item_type_storage->create([
        'status' => 1,
        'id' => 'add_credit',
        'label' => 'Add credit',
        'orderType' => 'default',
        'purchasableEntityType' => 'commerce_product_variation',
      ]);
      $order_item_type->save();

      $display_repository
        ->getFormDisplay('commerce_order_item', 'add_credit', 'add_to_cart')
        ->removeComponent('quantity')
        ->setComponent('add_credit_target', [
          'type' => 'add_credit_target_entity',
          'weight' => 1,
          'region' => 'content',
        ])
        ->setComponent('purchased_entity', [
          'type' => 'commerce_product_variation_attributes',
          'weight' => 0,
          'region' => 'content',
        ])
        ->setComponent('unit_price', [
          'type' => 'commerce_unit_price',
          'weight' => 2,
          'region' => 'content',
        ])
        ->save();
    }

    // Get or create the variation type.
    $variation_type_storage = $this->getEntityTypeManager()
      ->getStorage('commerce_product_variation_type');
    $variation_type = $variation_type_storage->load('add_credit');

    if (!$variation_type) {
      $variation_type = $variation_type_storage->create([
        'status' => 1,
        'id' => 'add_credit',
        'label' => 'Add credit',
        'orderItemType' => $order_item_type->id(),
        'generateTitle' => FALSE,
      ]);
      $variation_type->save();
    }

    // Get or create the product type.
    $product_type_storage = $this->getEntityTypeManager()
      ->getStorage('commerce_product_type');
    $product_type = $product_type_storage->load('add_credit');

    if (!$product_type) {
      $product_type = $product_type_storage->create([
        'status' => 1,
        'id' => 'add_credit',
        'label' => 'Add credit',
        'description' => 'This product is used to add credit to prepaid balances.',
        'variationType' => $variation_type->id(),
        'multipleVariations' => TRUE,
        'injectVariationFields' => TRUE,
      ])
        ->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit', TRUE)
        ->setThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_skip_cart', TRUE);
      $product_type->save();

      // These functions add the appropriate fields to the type.
      commerce_product_add_body_field($product_type);

      $display = $display_repository->getViewDisplay('commerce_product', 'add_credit', 'default');
      $component = $display->getComponent('variations');
      $component['label'] = 'hidden';
      $display->setComponent('variations', $component)
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(): bool {
    return $this->getModuleHandler()->moduleExists('apigee_m10n_add_credit');
  }

  /**
   * {@inheritdoc}
   */
  public function isCompleted(): bool {
    // Check if we have an add credit product type.
    return count($this->getEntityTypeManager()
      ->getStorage('commerce_product_type')
      ->loadByProperties([
        'third_party_settings.apigee_m10n_add_credit.apigee_m10n_enable_add_credit' => TRUE,
      ]));
  }

}
