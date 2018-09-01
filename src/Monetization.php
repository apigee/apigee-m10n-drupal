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
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Apigee Monetization base service.
 *
 * @package Drupal\apigee_m10n
 */
class Monetization implements MonetizationInterface {

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

  /**Ò
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
    $org_id = $this->sdk_connector->getOrganization();

    $monetization_status = $this->cache->get("apigee_m10n:org_monetization_status:{$org_id}");

    if (!$monetization_status) {

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
}
