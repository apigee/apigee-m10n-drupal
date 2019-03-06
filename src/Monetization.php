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

namespace Drupal\apigee_m10n;

use Apigee\Edge\Api\Management\Controller\OrganizationController;
use Apigee\Edge\Api\Monetization\Controller\ApiProductController;
use Apigee\Edge\Api\Monetization\Controller\PrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Entity\CompanyInterface;
use Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface;
use Apigee\Edge\Api\Monetization\Structure\LegalEntityTermsAndConditionsHistoryItem;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Formatter\CurrencyFormatter;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\apigee_m10n\Exception\SdkEntityLoadException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleInterface;
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
   * Static cache of `acceptLatestTermsAndConditions` results.
   *
   * @var array
   */
  private $developerAcceptedTermsStatus;

  /**
   * Static cache of the latest TnC.
   *
   * @var \Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface|false
   */
  protected $latestTermsAndConditions;

  /**
   * Static cache of the TnC list.
   *
   * @var \Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface[]
   */
  protected $termsAndConditionsList;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permission_handler;

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
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(
    SDKConnectorInterface $sdk_connector,
    ApigeeSdkControllerFactoryInterface $sdk_controller_factory,
    MessengerInterface $messenger,
    CacheBackendInterface $cache,
    LoggerInterface $logger,
    PermissionHandlerInterface $permission_handler
  ) {
    $this->sdk_connector          = $sdk_connector;
    $this->sdk_controller_factory = $sdk_controller_factory;
    $this->messenger              = $messenger;
    $this->cache                  = $cache;
    $this->logger                 = $logger;
    $this->permission_handler     = $permission_handler;

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
        return FALSE;
      }

      $monetization_status = $org->getPropertyValue('features.isMonetizationEnabled') === 'true' ? 'enabled' : 'disabled';
      $expire_time         = new \DateTime('now + 5 minutes');

      $this->cache->set("apigee_m10n:org_monetization_status:{$org_id}", $monetization_status, $expire_time->getTimestamp());
    }

    return ($monetization_status === 'enabled');
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
   * {@inheritdoc}
   */
  public function isLatestTermsAndConditionAccepted(string $developer_id): ?bool {
    if (!($latest_tnc = $this->getLatestTermsAndConditions())) {
      // If there isn't a latest TnC, and there was no error, there shouldn't be
      // anything to accept.
      // TODO: Add a test for an org with no TnC defined.
      return TRUE;
    }
    // Check the cache table.
    if (!isset($this->developerAcceptedTermsStatus[$developer_id])) {
      // Get the latest TnC ID.
      $latest_tnc_id = $latest_tnc->id();

      // Creates a controller for getting accepted TnC.
      $controller = $this->sdk_controller_factory->developerTermsAndConditionsController($developer_id);

      try {
        $history = $controller->getTermsAndConditionsHistory();
      }
      catch (\Exception $e) {
        $message = "Unable to load Terms and Conditions history for developer \n\n" . $e;
        $this->logger->error($message);
        throw new SdkEntityLoadException($message);
      }

      // All we care about is the latest entry for the latest TnC.
      $latest = array_reduce($history, function ($carry, $item) use ($latest_tnc_id) {
        /** @var \Apigee\Edge\Api\Monetization\Structure\LegalEntityTermsAndConditionsHistoryItem $item */
        // No need to look at items other than for the current TnC.
        if ($item->getTnc()->id() !== $latest_tnc_id) {
          return $carry;
        }
        // Gets the time of the carry over item.
        $carry_time = $carry instanceof LegalEntityTermsAndConditionsHistoryItem ? $carry->getAuditDate()->getTimestamp() : NULL;

        return $item->getAuditDate()->getTimestamp() > $carry_time ? $item : $carry;
      });

      $this->developerAcceptedTermsStatus[$developer_id] = ($latest instanceof LegalEntityTermsAndConditionsHistoryItem) && $latest->getAction() === 'ACCEPTED';
    }

    return $this->developerAcceptedTermsStatus[$developer_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestTermsAndConditions(): ?TermsAndConditionsInterface {
    // Check the static cache.
    if (isset($this->latestTermsAndConditions)) {
      return $this->latestTermsAndConditions;
    }
    // Get the full list.
    $list = $this->getTermsAndConditionsList();

    // Get the latest TnC that have already started.
    $latest = empty($list) ? NULL : array_reduce($list, function ($carry, $item) {
      /** @var \Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface $item */
      // Gets the time of the carry over item.
      $carry_time = $carry instanceof TermsAndConditionsInterface ? $carry->getStartDate()->getTimestamp() : NULL;
      // Gets the timestamp of the current item.
      $item_time = $item->getStartDate()->getTimestamp();
      $now = time();
      // Return the current item only if it the latest without starting in the
      // future.
      return ($item_time > $carry_time && $item_time < $now) ? $item : $carry;
    });

    // Cache the result for this request.
    $this->latestTermsAndConditions = $latest;

    return $this->latestTermsAndConditions;
  }

  /**
   * Gets the full list of terms and conditions.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface[]
   *   Returns the full list of terms and conditions or false on error.
   */
  protected function getTermsAndConditionsList(): array {
    // The cache ID.
    $cid = 'apigee_m10n:terms_and_conditions_list';

    // Check the static cache.
    if (isset($this->termsAndConditionsList)) {
      return $this->termsAndConditionsList;
    }
    // Check the cache.
    elseif (($cache = $this->cache->get($cid)) && ($list = $cache->data)) {
      // `$list` is set so there is nothing to do here.
    }
    else {
      try {
        $list = $this->sdk_controller_factory->termsAndConditionsController()->getEntities();
      }
      catch (\Exception $ex) {
        $this->logger->error("Unable to load Terms and Conditions: \n {$ex}");
        $this->cache->delete($cid);
        throw new SdkEntityLoadException("Error loading Terms and conditions. \n\n" . $ex);
      }

      // Cache the list for 5 minutes.
      $this->cache->set($cid, $list, time() + 299);

    }
    $this->termsAndConditionsList = $list;

    return $this->termsAndConditionsList;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptLatestTermsAndConditions(string $developer_id): ?LegalEntityTermsAndConditionsHistoryItem {
    try {
      // Reset the static cache for this developer.
      unset($this->developerAcceptedTermsStatus[$developer_id]);
      return $this->sdk_controller_factory->developerTermsAndConditionsController($developer_id)
        ->acceptTermsAndConditionsById($this->getLatestTermsAndConditions()->id());
    }
    catch (\Throwable $t) {
      $this->logger->error('Unable to accept latest TnC: ' . $t->getMessage());
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function formatCurrency(string $amount, string $currency_id): string {
    return $this->currencyFormatter->format($amount, $currency_id);
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
      $this->messenger->addWarning($e->getMessage());
      $this->logger->warning('Unable to retrieve prepaid balances: ' . $e->getMessage());
      return NULL;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCurrencies(): ?array {
    return $this->sdk_controller_factory->supportedCurrencyController()->getEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingDocumentsMonths(): ?array {
    return $this->sdk_controller_factory->billingDocumentsController()->getEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getPrepaidBalanceReports(string $developer_id, \DateTimeImmutable $month, string $currency): ?string {
    return $this->sdk_controller_factory->prepaidBalanceReportsController($developer_id)
      ->getReport($month, $currency);
  }

  /**
   * {@inheritdoc}
   */
  public function formUserAdminPermissionsAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Disable All incompatible permissions in the UI.
    foreach (array_keys($this->getMonetizationPermissions()) as $permission_name) {
      if (isset($form['permissions'][$permission_name][AccountInterface::ANONYMOUS_ROLE])) {
        // Disable the permission.
        $form['permissions'][$permission_name][AccountInterface::ANONYMOUS_ROLE]['#disabled'] = TRUE;
        $form['permissions'][$permission_name][AccountInterface::ANONYMOUS_ROLE]['#value'] = 0;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userRolePresave(RoleInterface $user_role) {
    // This prevents monetization permission grants on config import.
    if ($user_role->id() === AccountInterface::ANONYMOUS_ROLE) {
      // Get any permissions anon shouldn't have.
      $unauthorized_perms = array_intersect($user_role->getPermissions(), array_keys($this->getMonetizationPermissions()));
      // Remove all unauthorized perms.
      foreach ($unauthorized_perms as $permission_name) {
        $user_role->revokePermission($permission_name);
        $this->logger->info('Removing the `%permission` permission from the anonymous role.', ['%permission' => $permission_name]);
      }
    }

  }

  /**
   * Gets a list of Apigee monetization permissions.
   *
   * These permissions should only be applied to authenticated roles.
   *
   * @return array
   *   Permissions for the monetization module.
   */
  protected function getMonetizationPermissions() {
    return array_filter($this->permission_handler->getPermissions(), function ($permission) {
      return ($permission['provider'] === 'apigee_m10n');
    });
  }

}
