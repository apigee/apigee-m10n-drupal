<?php
/**
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

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApigeeAddCreditConfigForm.
 */
class ApigeeAddCreditConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'apigee_m10n_add_credit.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'apigee_add_credit_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('apigee_m10n_add_credit.config');

    // Use the site default if an email hasn't been saved.
    $default_email = $config->get('error_recipient');
    $default_email = $default_email ?: $this->configFactory()->get('system.site')->get('mail');

    // Whether or not to sent an email if there is an error adding credit.
    $form['mail_on_error'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mail on error?'),
      '#description' => $this->t('Send an email if an error occurs while adding credit to the developer\'s account.'),
      '#default_value' => $config->get('mail_on_error'),
    ];
    // Allow an email address to be set for the error report.
    $form['error_recipient'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('The email address to send errors to.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $default_email,
      '#states' => [
        'visible' => [
          ':input[name="mail_on_error"]' => [
            'checked' => TRUE,
          ],
        ],
      ]
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('apigee_m10n_add_credit.config')
      ->set('mail_on_error', $form_state->getValue('mail_on_error'))
      ->set('error_recipient', $form_state->getValue('error_recipient'))
      ->save();
  }

}
