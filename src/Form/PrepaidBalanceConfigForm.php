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

use Drupal\apigee_m10n\Controller\PrepaidBalanceControllerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config form for prepaid balance.
 */
class PrepaidBalanceConfigForm extends ConfigFormBase {

  /**
   * The config named used by this form.
   */
  const CONFIG_NAME = 'apigee_m10n.prepaid_balance.config';

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
    return 'prepaid_balance_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    // Build options.
    $period = [0, 60, 300, 600, 900, 1800, 3600, 21600, 32400, 43200, 86400];
    $period = array_map([$this->dateFormatter, 'formatInterval'], array_combine($period, $period));
    $period[0] = '<' . $this->t('no caching') . '>';

    $form['cache'] = [
      '#title' => $this->t('Caching'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['cache']['max_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Max age'),
      '#default_value' => $config->get('cache.max_age'),
      '#options' => $period,
      '#description' => $this->t('Set the cache age for the prepaid balance for a developer.'),
    ];

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General'),
    ];

    $form['general']['enable_insufficient_funds_workflow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable insufficient funds workflow.'),
      '#description' => $this->t('If checked, the "Purchase" button on rate plans will be disabled if developer billing type is PREPAID and does not have enough credit.'),
      '#default_value' => $config->get('enable_insufficient_funds_workflow'),
    ];

    $form['general']['max_statement_history_months'] = [
      '#type' => 'number',
      '#title' => $this->t('Billing history limit.'),
      '#description' => $this->t('The maximum number of months to allow generating as prepaid statement.'),
      '#default_value' => $config->get('max_statement_history_months'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 1200,
      '#field_suffix' => t('months'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIG_NAME)
      ->set('cache.max_age', $form_state->getValue('max_age'))
      ->set('enable_insufficient_funds_workflow', $form_state->getValue('enable_insufficient_funds_workflow'))
      ->set('max_statement_history_months', $form_state->getValue('max_statement_history_months'))
      ->save();

    // Clear caches.
    Cache::invalidateTags([PrepaidBalanceControllerInterface::CACHE_PREFIX]);

    parent::submitForm($form, $form_state);
  }

}
