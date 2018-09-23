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
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Apigee Monetization base service.
 *
 * @package Drupal\apigee_m10n
 */
class Monetization implements MonetizationInterface {

  const MONETIZATION_DISABLED_ERROR_MESSAGE = 'Monetization is not enabled for your Apigee Edge organization.';

  /**
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  private $sdk_connector;

  /**
   * Drupal core messenger service (for adding flash messages).
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * Monetization constructor.
   *
   * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
   */
  public function __construct(SDKConnectorInterface $sdk_connector, MessengerInterface $messenger, CacheBackendInterface $cache) {
    $this->sdk_connector = $sdk_connector;
    $this->messenger     = $messenger;
    $this->cache         = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function isMonetizationEnabled(): bool {
    // Get organization ID string.
    $org_id = $this->sdk_connector->getOrganization();

    // Use cached result if available.
    $monetization_status_cache_entry = $this->cache->get("apigee_m10n:org_monetization_status:{$org_id}");
    $monetization_status = $monetization_status_cache_entry ? $monetization_status_cache_entry->data : null;

    if (!$monetization_status) {

      // Load organization and populate cache.
      $org_controller = new OrganizationController($this->sdk_connector->getClient());

      try {
        $org = $org_controller->load($org_id);
      } catch (\Exception $e) {
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
  public function getDeveloperPrepaidBalances(string $org_id, AccountInterface $developer): ?array {

    /** @todo use SDK when it's ready */

    try {
      $url = '/mint/organizations/' . $org_id . '/developers/' . rawurlencode($developer->getEmail()) . '/prepaid-developer-balance';

      $month = date('F');
      $year = date('Y');

      $query_array = [
          'billingMonth' => strtoupper($month),
          'billingYear' => $year,
          'supportedCurrencyId' => null,
      ];

      $query = http_build_query($query_array);
      $response = $this->sdk_connector->getClient()->get($url . "?" . $query);

      $result = json_decode($response->getBody()->getContents());
    }
    catch (\Exception $e) {
      return null;
    }

    return $result->developerBalance ?? null;
  }
}
