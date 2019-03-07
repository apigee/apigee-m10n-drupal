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

use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ApigeeAddCreditConfigForm.
 */
class ApigeeAddCreditConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [AddCreditConfig::CONFIG_NAME];
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
    $config = $this->config(AddCreditConfig::CONFIG_NAME);

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General'),
    ];

    $form['general']['use_modal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use modal'),
      '#description' => $this->t('Display the add credit form in a modal.'),
      '#default_value' => $config->get('use_modal'),
    ];

    // Use the site default if an email hasn't been saved.
    $default_email = $config->get('notification_recipient');
    $default_email = $default_email ?: $this->configFactory()->get('system.site')->get('mail');

    $form['notifications'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notifications'),
    ];
    // Whether or not to sent an email if there is an error adding credit.
    $description = 'Notification sent when an %add_credit product is processed 
                    to add credit to a developer or team account. If an error 
                    occurs while applying the credit, a notification is sent to
                    the specified email address. Select %add_credit to send a
                    notification even if the credit is applied successfully.';

    $form['notifications']['notify_on'] = [
      '#type' => 'radios',
      '#title' => $this->t('Notify administrator'),
      '#options' => [
        AddCreditConfig::NOTIFY_ALWAYS => $this->t('Always'),
        AddCreditConfig::NOTIFY_ON_ERROR => $this->t('Only on error'),
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
    $form['notifications']['note'] = [
      '#markup' => $this->t('<div class="apigee-add-credit-notification-note"><div class="label">@note</div><div>@description<br />@see @commerce_notification_link.</div></div>', [
        '@note' => 'Note:',
        '@description' => 'You can configure Drupal Commerce to send an email to the consumer to confirm completion of the order.',
        '@see' => 'See',
        '@commerce_notification_link' => Link::fromTextAndUrl('Drupal commerce documentation', Url::fromUri('https://docs.drupalcommerce.org/commerce2/user-guide/orders/customer-emails', ['external' => TRUE]))->toString(),
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config(AddCreditConfig::CONFIG_NAME)
      ->set('use_modal', $form_state->getValue('use_modal'))
      ->set('notify_on', $form_state->getValue('notify_on'))
      ->set('notification_recipient', $form_state->getValue('notification_recipient'))
      ->save();
  }

}
