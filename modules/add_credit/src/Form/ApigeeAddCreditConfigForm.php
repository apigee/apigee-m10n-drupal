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

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ApigeeAddCreditConfigForm.
 */
class ApigeeAddCreditConfigForm extends ConfigFormBase {

  /**
   * The default name for the `apigee_m10n_add_credit` module.
   *
   * @var string
   */
  public const CONFIG_NAME = 'apigee_m10n_add_credit.config';

  /**
   * The "Always" value for `apigee_m10n_add_credit.config.notify_on`.
   *
   * @var string
   */
  public const NOTIFY_ALWAYS = 'always';

  /**
   * The "Only on error" value for `apigee_m10n_add_credit.config.notify_on`.
   *
   * @var string
   */
  public const NOTIFY_ON_ERROR = 'error_only';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::$CONFIG_NAME];
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
    $config = $this->config(static::CONFIG_NAME);

    // Use the site default if an email hasn't been saved.
    $default_email = $config->get('notification_recipient');
    $default_email = $default_email ?: $this->configFactory()->get('system.site')->get('mail');

    $form['notifications'] = [
      '#type' => 'fieldset',
      '#title' => $this
        ->t('Notifications'),
    ];
    // Whether or not to sent an email if there is an error adding credit.
    $description = 'This notification is sent when an %add_credit product is 
                    processed. Normally this would result in the application of 
                    the purchased amount to the developer or the team account. 
                    If an error occurs while attempting to apply the credit to 
                    the users account the following recipient will be notified. 
                    Select the %always_option to receive a notification even if 
                    the credit is applied successfully.';

    $form['notifications']['notify_on'] = [
      '#type' => 'radios',
      '#title' => $this->t('When to notify an administrator'),
      '#options' => [
        static::NOTIFY_ALWAYS => $this->t('Always'),
        static::NOTIFY_ON_ERROR => $this->t('Only on error'),
      ],
      '#description' => $this->t($description, [
        '%add_credit' => 'Add credit',
        '%always_option' => 'Always',
      ]),
      '#default_value' => $config->get('notify_on'),
    ];
    // Allow an email address to be set for the error report.
    $form['notifications']['notification_recipient'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('The email recipient of %add_credit notifications.', ['%add_credit' => 'Add credit']),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $default_email,
      '#required' => TRUE,
    ];
    // Add a note about configuring notifications in drupal commerce.
    $note = '<div class="apigee-add-credit-notification-note"><strong><em>Note:</em></strong><br />
             <p>It is possible to configure drupal commerce to send 
             an email to the purchaser upon order completion.
             <br />
             See @commerce_notification_link for more information.</p></div>';
    $form['notifications']['note'] = [
      '#markup' => $this->t($note, [
        '@commerce_notification_link' => Link::fromTextAndUrl('Drupal commerce documentation', Url::fromUri('https://docs.drupalcommerce.org/commerce2/user-guide/orders/customer-emails', ['external' => TRUE]))->toString(),
      ]),
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

    $this->config(static::CONFIG_NAME)
      ->set('notify_on', $form_state->getValue('notify_on'))
      ->set('notification_recipient', $form_state->getValue('notification_recipient'))
      ->save();
  }

}
