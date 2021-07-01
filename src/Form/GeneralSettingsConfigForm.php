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

namespace Drupal\apigee_m10n\Form;

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
  const CONFIG_NAME = 'apigee_m10n.general_settings.config';

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

    $billingType = ['Not Specified', 'POSTPAID' => 'Postpaid' , 'PREPAID' => 'Prepaid'];

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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIG_NAME)
      ->set('billing.billingtype', $form_state->getValue('billingtype'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
