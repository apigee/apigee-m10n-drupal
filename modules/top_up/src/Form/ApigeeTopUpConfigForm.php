<?php

namespace Drupal\apigee_m10n_top_up\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApigeeTopUpConfigForm.
 */
class ApigeeTopUpConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'apigee_m10n_top_up.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'apigee_top_up_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('apigee_m10n_top_up.config');

    // Use the site default if an email hasn't been saved.
    $default_email = $config->get('error_recipient');
    $default_email = $default_email ?: $this->configFactory()->get('system.site')->get('mail');

    // Whether or not to sent an email if there is an error applying a recharge.
    $form['mail_on_error'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mail on error?'),
      '#description' => $this->t('Send an email if an error occurs while apply the recharge to the developer\'s account.'),
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

    $this->config('apigee_m10n_top_up.config')
      ->set('mail_on_error', $form_state->getValue('mail_on_error'))
      ->set('error_recipient', $form_state->getValue('error_recipient'))
      ->save();
  }

}
