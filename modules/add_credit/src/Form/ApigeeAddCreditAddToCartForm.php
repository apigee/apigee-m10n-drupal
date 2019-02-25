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
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Populate values from request.
    /** @var \Drupal\user\UserInterface $user */
    if ($user = \Drupal::request()->get('user')) {
      $this->entity->set('field_target', [
        'target_type' => 'developer',
        'target_id' => $user->getEmail(),
      ]);
    }

    // Build the form.
    $form = parent::buildForm($form, $form_state);

    // If the target field is visible, set a default value.
    if ($user && isset($form['field_target'])) {
      $form['field_target']['widget']['#default_value'] = [
        'developer:' . $user->getEmail(),
      ];
    }

    return $form;
  }

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
    return isset($this->entity->purchased_entity->entity->product_id->entity->type->entity)
      ? $this->entity->purchased_entity->entity->product_id->entity->type->entity->getThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_skip_cart', FALSE)
      : FALSE;
  }

}
