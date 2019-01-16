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

namespace Drupal\apigee_m10n\Controller;

use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\apigee_m10n\Form\BillingConfigForm;
use Drupal\apigee_m10n\Form\PrepaidBalanceReportsDownloadForm;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for billing related routes.
 */
class BillingController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Cache prefix that is used for cache tags for this controller.
   */
  const CACHE_PREFIX = 'apigee.monetization.billing';

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
  private $monetization;

  /**
   * The Apigee SDK connector.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdk_connector;

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
   */
  public function __construct(SDKConnectorInterface $sdk_connector, MonetizationInterface $monetization, FormBuilderInterface $form_builder, AccountInterface $current_user) {
    $this->sdk_connector = $sdk_connector;
    $this->monetization = $monetization;
    $this->currentUser = $current_user;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_edge.sdk_connector'),
      $container->get('apigee_m10n.monetization'),
      $container->get('form_builder'),
      $container->get('current_user')
    );
  }

  /**
   * Redirect to the user's prepaid balances page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Gets a redirect to the users's balance page.
   */
  public function myPrepaidBalance(): RedirectResponse {
    return $this->redirect(
      'apigee_monetization.billing',
      ['user' => $this->currentUser->id()],
      ['absolute' => TRUE]
    );
  }

  /**
   * View prepaid balance and account statements, add money to prepaid balance.
   *
   * @param \Drupal\user\UserInterface $user
   *   The Drupal user.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array or a redirect response.
   *
   * @throws \Exception
   */
  public function prepaidBalancePage(UserInterface $user) {
    $build = [
      '#prefix' => '<div class="apigee-m10n-prepaid-balance">',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => [
          'apigee_m10n/billing',
        ],
      ],
    ];

    // Retrieve prepaid balances for this user for the current month and year.
    $build['prepaid_balances'] = [
      'balances' => [
        '#theme' => 'prepaid_balances',
        '#balances' => $this->getDataFromCache($user, 'prepaid_balances', function () use ($user) {
          return $this->monetization->getDeveloperPrepaidBalances($user, new \DateTimeImmutable('now'));
        }),
      ],
    ];

    // Add a refresh button.
    if ($this->currentUser->hasPermission('refresh prepaid balance reports')) {
      $build['prepaid_balances']['refresh_button'] = [
        '#type' => 'link',
        '#title' => $this->t('Refresh'),
        '#url' => Url::fromRoute('apigee_monetization.billing_refresh', [
          'user' => $user->id(),
        ]),
        '#attributes' => [
          'class' => [
            'button',
            'apigee-m10n-prepaid-balance__refresh-button',
          ],
        ],
      ];
    }

    // Cache the render array if enabled.
    if ($max_age = $this->getCacheMaxAge()) {
      $build['prepaid_balances']['#cache'] = [
        'contexts' => ['url.path'],
        'tags' => $this->getCacheTags($user),
        'max-age' => $max_age,
        'keys' => [$this->getCacheId($user, 'prepaid_balances')],
      ];
    }

    // Show the prepaid balance reports download form.
    if ($this->currentUser->hasPermission('download prepaid balance reports')) {
      $supported_currencies = $this->getDataFromCache($user, 'supported_currencies', function () {
        return $this->monetization->getSupportedCurrencies();
      });

      $billing_documents = $this->getDataFromCache($user, 'billing_documents', function () {
        return $this->monetization->getBillingDocumentsMonths();
      });

      // Build the form.
      $build['download_form'] = $this->formBuilder->getForm(PrepaidBalanceReportsDownloadForm::class, $user, $supported_currencies, $billing_documents);
      $build['download_form']['#cache']['keys'] = [$this->getCacheId($user, 'download_form')];
    }

    return $build;
  }

  /**
   * Callback for refreshing the prepaid balances.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function refreshPrepaidBalancePage(UserInterface $user) {
    // A user can only refresh own account.
    if ($this->currentUser->id() !== $user->id()) {
      throw new AccessDeniedHttpException();
    }

    // Invalidate the billing cache tag.
    // This handles both the current balance and the previous balance reports.
    Cache::invalidateTags($this->getCacheTags($user));

    // Redirect to billing page.
    return new RedirectResponse(Url::fromRoute('apigee_monetization.billing', [
      'user' => $user->id(),
    ])->toString());
  }

  /**
   * Helper to retrieve data from cache.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string $suffix
   *   The cache id suffix.
   * @param callable $callback
   *   The callback if not in cache.
   *
   * @return mixed
   *   The data.
   */
  protected function getDataFromCache(UserInterface $user, string $suffix, callable $callback) {
    $max_age = $this->getCacheMaxAge();

    // If caching is disable, run callback and return.
    if ($max_age == 0) {
      return $callback();
    }

    $cid = $this->getCacheId($user, $suffix);

    // Check cache.
    if ($cache = $this->cache()->get($cid)) {
      return $cache->data;
    }

    $data = $callback();
    $this->cache()
      ->set($cid, $data, time() + $max_age, $this->getCacheTags($user));

    return $data;
  }

  /**
   * Returns the cache max age.
   *
   * @return int
   *   The cache max age.
   */
  protected function getCacheMaxAge() {
    // Get the max-age from config.
    if ($config = $this->config(BillingConfigForm::CONFIG_NAME)) {
      return $config->get('prepaid_balance.cache_max_age');
    }

    return 0;
  }

  /**
   * Helper to get the billing cache tags.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return array
   *   The cache tags.
   */
  public static function getCacheTags(UserInterface $user) {
    return [
      static::CACHE_PREFIX,
      static::CACHE_PREFIX . ':user:' . $user->id(),
    ];
  }

  /**
   * Helper to get the cache id.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param null $suffix
   *   The suffix for the cache id.
   *
   * @return string
   *   The cache id.
   */
  public static function getCacheId(UserInterface $user, $suffix = NULL) {
    return static::CACHE_PREFIX . ':user:' . $user->id() . ($suffix ? ':' . $suffix : '');
  }

}
