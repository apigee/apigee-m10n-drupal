<?php

namespace Drupal\apigee_m10n;

use Apigee\Edge\Api\Management\Controller\OrganizationController;
use Drupal\apigee_edge\SDKConnectorInterface;

class Monetization {

  private $sdk_connector;

  public function __construct(SDKConnectorInterface $sdk_connector) {
    $this->sdk_connector = $sdk_connector;
  }

  /**
   * Test whether the current organization has monetization enabled (a requirement for using this module).
   *
   * @return bool
   */
  public function testConnection() {
    $org_controller = new OrganizationController($this->sdk_connector->getClient());
    $org_id = $this->sdk_connector->getOrganization();
    $org = $org_controller->load($org_id);

    $is_monetization_enabled = $org->getPropertyValue('features.isMonetizationEnabled');

    return $is_monetization_enabled;
  }

}