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

namespace Drupal\apigee_m10n;

use Apigee\Edge\Api\Management\Controller\OrganizationController;
use Apigee\Edge\Api\Monetization\Controller\ApiProductController;
use Apigee\Edge\Api\Monetization\Controller\PrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Entity\CompanyInterface;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Formatter\CurrencyFormatter;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Apigee Monetization base service.
 *
 * @package Drupal\apigee_m10n
 */
class Monetization implements MonetizationInterface {

  const MONETIZATION_DISABLED_ERROR_MESSAGE = 'Monetization is not enabled for your Apigee Edge organization.';

  /**
   * The Apigee Edge SDK connector.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  private $sdk_connector;

  /**
   * The SDK controller factory.
   *
   * @var \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface
   */
  private $sdk_controller_factory;

  /**
   * Drupal core messenger service (for adding flash messages).
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * The Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatter
   */
  private $currencyFormatter;

  /**
   * Monetization constructor.
   *
   * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
   *   The Apigee Edge SDK connector.
   * @param \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface $sdk_controller_factory
   *   The SDK controller factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal core messenger service (for adding flash messages).
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The Cache backend.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    SDKConnectorInterface $sdk_connector,
    ApigeeSdkControllerFactoryInterface $sdk_controller_factory,
    MessengerInterface $messenger,
    CacheBackendInterface $cache,
    LoggerInterface $logger
  ) {
    $this->sdk_connector          = $sdk_connector;
    $this->sdk_controller_factory = $sdk_controller_factory;
    $this->messenger              = $messenger;
    $this->cache                  = $cache;
    $this->logger                 = $logger;

    $numberFormatRepository = new NumberFormatRepository();
    $currencyRepository = new CurrencyRepository();

    $this->currencyFormatter = new CurrencyFormatter($numberFormatRepository, $currencyRepository);
  }

  /**
   * {@inheritdoc}
   */
  public function isMonetizationEnabled(): bool {
    // Get organization ID string.
    $org_id = $this->sdk_connector->getOrganization();

    // Use cached result if available.
    $monetization_status_cache_entry = $this->cache->get("apigee_m10n:org_monetization_status:{$org_id}");
    $monetization_status             = $monetization_status_cache_entry ? $monetization_status_cache_entry->data : NULL;

    if (!$monetization_status) {

      // Load organization and populate cache.
      $org_controller = new OrganizationController($this->sdk_connector->getClient());

      try {
        $org = $org_controller->load($org_id);
      }
      catch (\Exception $e) {
        $this->messenger->addError($e->getMessage());
        return $is_monetization_enabled = FALSE;
      }

      $monetization_status = $org->getPropertyValue('features.isMonetizationEnabled') === 'true' ? 'enabled' : 'disabled';
      $expire_time         = new \DateTime('now + 5 minutes');

      $this->cache->set("apigee_m10n:org_monetization_status:{$org_id}", $monetization_status, $expire_time->getTimestamp());
    }

    return (bool) $is_monetization_enabled = $monetization_status === 'enabled' ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function apiProductAssignmentAccess(EntityInterface $entity, AccountInterface $account): AccessResultInterface {
    // Cache results for this request.
    static $eligible_product_cache = [];
    // The developer ID to check access for.
    $developer_id = $account->getEmail();

    if (!isset($eligible_product_cache[$developer_id])) {
      // Instantiate an instance of the m10n ApiProduct controller.
      $product_controller = new ApiProductController($this->sdk_connector->getOrganization(), $this->sdk_connector->getClient());
      // Get a list of available products for the m10n developer.
      $eligible_product_cache[$developer_id] = $product_controller->getEligibleProductsByDeveloper($developer_id);
    }

    // Get just the IDs from the available products.
    $product_ids = array_map(function ($product) {
      return $product->id();
    }, $eligible_product_cache[$developer_id]);

    // Allow only if the id is in the eligible list.
    return in_array(strtolower($entity->id()), $product_ids)
      ? AccessResult::allowed()
      : AccessResult::forbidden('Product is not eligible for this developer');
  }

  /**
   * {@inheritdoc}
   */
  public function getDeveloperPrepaidBalances(UserInterface $developer, \DateTimeImmutable $billingDate): ?array {
    $balance_controller = $this->sdk_controller_factory->developerBalanceController($developer);
    return $this->getPrepaidBalances($balance_controller, $billingDate);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompanyPrepaidBalances(CompanyInterface $company, \DateTimeImmutable $billingDate): ?array {
    $balance_controller = $this->sdk_controller_factory->companyBalanceController($company);
    return $this->getPrepaidBalances($balance_controller, $billingDate);
  }

  /**
   * Gets the prepaid balances.
   *
   * Uses a prepaid balance controller to return prepaid balances for a
   * specified month and year.
   *
   * @param \Apigee\Edge\Api\Monetization\Controller\PrepaidBalanceControllerInterface $balance_controller
   *   The balance controller.
   * @param \DateTimeImmutable $billingDate
   *   The time to get the report for.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface[]|null
   *   The balance list or null if no balances are available.
   */
  protected function getPrepaidBalances(PrepaidBalanceControllerInterface $balance_controller, \DateTimeImmutable $billingDate): ?array {

    try {
      $result = $balance_controller->getPrepaidBalance($billingDate);
    }
    catch (\Exception $e) {
      $this->messenger->addWarning('Unable to retrieve prepaid balances.');
      $this->logger->warning('Unable to retrieve prepaid balances: ' . $e->getMessage());
      return NULL;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function formatCurrency(string $amount, string $currency_id): string {
    return $this->currencyFormatter->format($amount, $currency_id);
  }

}
