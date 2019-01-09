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

namespace Drupal\apigee_m10n_add_credit\Form;

use Drupal\commerce_cart\Form\AddToCartForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApigeeAddCreditAddToCartForm.
 *
 * @package Drupal\apigee_m10n_add_credit
 */
class ApigeeAddCreditAddToCartForm extends AddToCartForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $action = parent::actions($form, $form_state);

    // Update the submit button if skip cart.
    if ($this->shouldSkipCart()) {
      $action['submit']['#value'] = $this->t('Checkout');
    }

    return $action;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Go to the review step is skip cart.
    if ($this->shouldSkipCart()) {
      $form_state->setRedirect('commerce_checkout.form', [
        'commerce_order' => $this->entity->getOrderId(),
        'step' => 'review',
      ]);
    }
  }

  /**
   * Helper to determine if should skip cart.
   *
   * @return bool
   *   TRUE is should skip cart.
   */
  protected function shouldSkipCart() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->entity;
    /** @var \Drupal\commerce\PurchasableEntityInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();

    try {
      $product_type = $this->entityTypeManager->getStorage('commerce_product_type')->load($purchased_entity->bundle());

      if ($product_type->getThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_skip_cart')) {
        return TRUE;
      }
    }
    catch (\Exception $exception) {
      $this->logger('apigee_m10n_add_credit')->notice($exception->getMessage());
    }

    return FALSE;
  }

}
