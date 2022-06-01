<?php

/*
 * Copyright 2021 Google Inc.
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

namespace Drupal\apigee_m10n\Controller;

use Apigee\Edge\Api\ApigeeX\Entity\PrepaidBalanceInterface;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\apigee_m10n\Form\PrepaidBalanceConfigForm;
use Drupal\apigee_m10n\Form\PrepaidBalanceRefreshForm;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Prepaid balance controller base.
 *
 * This is modeled after an entity list builder with some additions.
 * See: `\Drupal\Core\Entity\EntityListBuilder`
 */
abstract class PrepaidBalanceXControllerBase extends ControllerBase implements PrepaidBalanceXControllerInterface {

  /**
   * The entity for this report.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Apigee Monetization utility service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * The Apigee SDK connector.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdk_connector;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal core messenger service (for adding flash messages).
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * BillingController constructor.
   *
   * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
   *   The `apigee_edge.sdk_connector` service.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The `apigee_m10n.monetization` service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal core messenger service (for adding flash messages).
   */
  public function __construct(SDKConnectorInterface $sdk_connector, MonetizationInterface $monetization, FormBuilderInterface $form_builder, AccountInterface $current_user, ModuleHandlerInterface $module_handler, MessengerInterface $messenger) {
    $this->sdk_connector = $sdk_connector;
    $this->monetization = $monetization;
    $this->currentUser = $current_user;
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_edge.sdk_connector'),
      $container->get('apigee_m10n.monetization'),
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Sanitize the entity type for a type specific wrapper class.
    $type_class = preg_replace('/[^a-z]+/', '-', $this->entity->getEntityTypeId());

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['apigee-m10n-prepaid-balance-wrapper', "apigee-m10n-{$type_class}-prepaid-balance-wrapper"],
      ],
      '#attached' => [
        'library' => [
          'apigee_m10n/prepaid_balance',
        ],
      ],
    ];

    // Add a refresh cache form.
    if ($this->canRefreshBalance()) {
      $build['refresh_form'] = $this->formBuilder()->getForm(PrepaidBalanceRefreshForm::class, $this->getCacheTags($this->entity));
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#caption' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no balances available for this @label.', ['@label' => strtolower($this->entity->getEntityType()->getLabel())]),
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => $this->getCacheTags($this->entity),
        'max-age' => $max_age = $this->getCacheMaxAge(),
        'keys' => [static::getCacheId($this->entity, 'prepaid_balances')],
      ],
    ];

    // Convert wait time to miliseconds as the last credited time is miliseconds.
    $wait_time_in_miliseconds = \Drupal::config('apigee_m10n_add_credit.general_settings.config')->get('wait_time') * 1000;
    $utc = new \DateTimeZone('UTC');
    $currentTimestamp = (new \DateTimeImmutable())->setTimezone($utc);
    $currentTimestamp = ($currentTimestamp->getTimestamp() . $currentTimestamp->format('v'));
    $time_to_wait = '';
    foreach ($this->getDataFromCache($this->entity, 'prepaid_balances', [$this, 'load']) as $balance) {

      /** @var \Apigee\Edge\Api\ApigeeX\Entity\PrepaidBalanceInterface $balance */
      if ($row = $this->buildRow($balance)) {
        $build['table']['#rows'][strtolower($balance->getBalance()->getCurrencyCode())] = $row;

        $time_to_wait = $balance->getlastCreditTime() + $wait_time_in_miliseconds;
        if ($time_to_wait > $currentTimestamp) {
          $build['table']['#rows'][strtolower($balance->getBalance()->getCurrencyCode())]['#attributes']['class'] = 'disable-add-credit';
          $seconds = floor(($time_to_wait - $currentTimestamp) / 1000);
          $minutes = ((int) ($seconds / 60) % 60);
          // If wait time set is less than 60 seconds, then the text is shown.
          // minutes+1 is added to avoid showing seconds.
          $minutes = ($wait_time_in_miliseconds <= 60000) ? "few seconds" : $minutes + 1 . ' mins';
          $this->messenger->addWarning($this->t('**Add credit is disabled for @mins for currency @currency ', ['@mins' => $minutes, '@currency' => $balance->getBalance()->getCurrencyCode()]));

        }
      }
    }

    // Allow other modules to alter this build.
    $this->moduleHandler->alter('apigee_m10n_prepaid_balance_list', $build, $this->entity);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Current prepaid balance');
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'currency' => $this->t('Account Currency'),
      'current_balance' => $this->t('Current Balance'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(PrepaidBalanceInterface $balance) {

    $currency_code = $balance->getBalance()->getCurrencyCode();
    return [
      'class' => ["apigee-balance-row-{$balance->getBalance()->getCurrencyCode()}"],
      'data' => [
        'currency' => $currency_code,
        'current_balance' => $this->formatCurrency(($balance->getBalance()->getUnits() + $balance->getBalance()->getNanos()), $currency_code,),
      ],
    ];
  }

  /**
   * Format an amount using the `monetization` service.
   *
   * See: \Drupal\apigee_m10n\MonetizationInterface::formatCurrency().
   *
   * @param string $amount
   *   The money amount.
   * @param string $currency_code
   *   Currency code.
   *
   * @return string
   *   The formatted amount as a string.
   */
  protected function formatCurrency($amount, $currency_code) {
    return $this->monetization->formatCurrency($amount, $currency_code);
  }

  /**
   * Helper to check if user has access to refresh prepaid balance.
   *
   * @return bool
   *   TRUE if user can refresh balance.
   */
  abstract protected function canRefreshBalance();

  /**
   * Loads the balances for the listing.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Entity\PrepaidBalanceInterface[]|array
   *   A list of apigee monetization prepaid balance entities.
   *
   * @throws \Exception
   */
  abstract public function load();

  /**
   * Returns the cache max age.
   *
   * @return int
   *   The cache max age.
   */
  protected function getCacheMaxAge() {
    // Get the max-age from config.
    if ($config = $this->config(PrepaidBalanceConfigForm::CONFIG_NAME)) {
      return $config->get('cache.max_age');
    }

    return 0;
  }

  /**
   * Helper to retrieve data from cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $suffix
   *   The cache id suffix.
   * @param callable $callback
   *   The callback if not in cache.
   *
   * @return mixed
   *   The data.
   */
  protected function getDataFromCache(EntityInterface $entity, string $suffix, callable $callback) {
    $max_age = $this->getCacheMaxAge();

    // If caching is disable, run callback and return.
    if ($max_age == 0) {
      return $callback();
    }

    $cid = $this->getCacheId($entity, $suffix);

    // Check cache.
    if ($cache = $this->cache()->get($cid)) {
      return $cache->data;
    }

    $data = $callback();
    $this->cache()
      ->set($cid, $data, time() + $max_age, $this->getCacheTags($entity));

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCacheTags(EntityInterface $entity) {
    return [
      static::CACHE_PREFIX,
      static::getCacheId($entity),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCacheId(EntityInterface $entity, $suffix = NULL) {
    return static::CACHE_PREFIX . ":{$entity->getEntityTypeId()}:{$entity->id()}" . ($suffix ? ":{$suffix}" : '');
  }

}
