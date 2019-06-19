<?php

/*
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Drupal\apigee_m10n\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class for managing `apigee_m10n.purchased_plan.config` settings.
 *
 * @package Drupal\apigee_m10n\Form
 */
class PurchasedPlanConfigForm extends ConfigFormBase {

  /**
   * The config named used by this form.
   */
  const CONFIG_NAME = 'apigee_m10n.purchased_plan.config';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return preg_replace('/[^a-zA-Z0-9_]+/', '_', static::CONFIG_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the working configuration.
    $config = $this->config(static::CONFIG_NAME);

    $form['purchase_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Purchase rate plan label'),
      '#description' => $this->t('If configured to be displayed, the heading label used in the purchase rate plan form.'),
      '#default_value' => $config->get('purchase_label'),
    ];
    $form['purchase_form_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Purchase rate plan form title'),
      '#description' => $this->t('Title of the purchase rate plan form. For example: "Purchase %rate_plan".'),
      '#default_value' => $config->get('purchase_form_title'),
    ];
    $form['purchase_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Purchase rate plan button label'),
      '#description' => $this->t('Button label for purchasing a rate plan.'),
      '#default_value' => $config->get('purchase_button_label'),
    ];
    $form['cancel_question'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel rate plan form title'),
      '#description' => $this->t('Title of the cancel form. For example: "Cancel %rate_plan".'),
      '#default_value' => $config->get('cancel_question'),
    ];
    $form['cancel_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel rate plan form prompt'),
      '#description' => $this->t('Prompt that appears as a subtitle on the cancel rate plan form.'),
      '#default_value' => $config->get('cancel_description'),
    ];
    $form['cancel_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel rate plan button label'),
      '#description' => $this->t('Button label for canceling a rate plan.'),
      '#default_value' => $config->get('cancel_button_label'),
    ];
    $form['already_purchased_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Already subscribed label'),
      '#description' => $this->t('Text to display if already subscribed to a rate plan. For example: "Already subscribed to %rate_plan".'),
      '#default_value' => $config->get('already_purchased_label'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config(static::CONFIG_NAME)
      ->set('purchase_label', $form_state->getValue('purchase_label'))
      ->set('purchase_form_title', $form_state->getValue('purchase_form_title'))
      ->set('purchase_button_label', $form_state->getValue('purchase_button_label'))
      ->set('cancel_question', $form_state->getValue('cancel_question'))
      ->set('cancel_description', $form_state->getValue('cancel_description'))
      ->set('cancel_button_label', $form_state->getValue('cancel_button_label'))
      ->set('already_purchased_label', $form_state->getValue('already_purchased_label'))
      ->save();
  }

}
