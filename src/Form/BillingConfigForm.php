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

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BillingConfigForm
 *
 * @package Drupal\apigee_m10n
 */
class BillingConfigForm extends ConfigFormBase {

  /**
   * The config named used by this form.
   */
  const CONFIG_NAME = 'apigee_m10n.billing.config';

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter) {
    parent::__construct($config_factory);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter')
    );
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
    return 'billing_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    // Build options.
    $period = [0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400];
    $period = array_map([$this->dateFormatter, 'formatInterval'], array_combine($period, $period));
    $period[0] = '<' . $this->t('no caching') . '>';

    $form['prepaid_balance'] = [
      '#title' => $this->t('Prepaid balance'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['prepaid_balance']['cache_max_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Cache max age'),
      '#default_value' => $config->get('prepaid_balance.cache_max_age'),
      '#options' => $period,
      '#description' => $this->t('How long to cache the prepaid balance for a developer.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIG_NAME)
      ->set('prepaid_balance.cache_max_age', $form_state->getValue('cache_max_age'))
      ->save();

    parent::submitForm($form, $form_state);
  }


}
