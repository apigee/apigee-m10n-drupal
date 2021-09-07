<?php

/*
 * Copyright 2021 Google LLC
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

namespace Drupal\apigee_m10n_add_credit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Config form for general setting.
 */
class GeneralSettingsConfigForm extends ConfigFormBase {

  /**
   * The config named used by this form.
   */
  const CONFIG_NAME = 'apigee_m10n_add_credit.general_settings.config';

  /**
   * Apigee Monetization base service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, MonetizationInterface $monetization) {
    parent::__construct($config_factory);
    $this->monetization = $monetization;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * Checks if ApigeeX org.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Grants access to the route if the org id ApigeeX.
   */
  public function access() {

    if (!$this->monetization->isOrganizationApigeeXorHybrid()) {
      return AccessResult::forbidden('Only accessible for ApigeeX organization');
    }
    else {
      return AccessResult::allowed();
    }
  }

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
    return 'general_settings_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    $billingType = ['postpaid' => 'Postpaid' , 'prepaid' => 'Prepaid'];

    $form['billing'] = [
      '#title' => $this->t('General Settings'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['billing']['billingtype'] = [
      '#type' => 'radios',
      '#title' => $this->t('Billing Type'),
      '#default_value' => $config->get('billing.billingtype'),
      '#options' => $billingType,
      '#description' => $this->t('Select default billing type for users signing up for the portal.'),
    ];

    $form['time'] = [
      '#title' => $this->t('Maximum wait Settings'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['time']['wait_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Topup balance wait.'),
      '#description' => $this->t('Portal blocks the frequent top-ups by disabling the add credit option for the above mentioned period.'),
      '#default_value' => $config->get('wait_time'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 3600,
      '#field_suffix' => t('seconds'),
    ];

    // Add a note about changing the wait time.
    $form['time']['note'] = [
      '#markup' => $this->t('<div class="apigee-add-credit-notification-note"><div class="label">@note</div><div>@description</div></div>', [
        '@note' => 'Note:',
        '@description' => "Apigee backend API for credit balance doesn't allow frequent top-ups,
                          and hence in line with the same,
                          the portal also disables the add credit option for a fixed period.
                          If you want to change the above value, please make sure the changes
                          fits well with the backend API guarantees.",
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIG_NAME)
      ->set('billing.billingtype', $form_state->getValue('billingtype'))
      ->set('wait_time', $form_state->getValue('wait_time'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
