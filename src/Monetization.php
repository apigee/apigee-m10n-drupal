<?php

namespace Drupal\apigee_m10n;

use Apigee\Edge\Api\Management\Controller\OrganizationController;
use Drupal\apigee_edge\SDKConnectorInterface;

class Monetization implements MonetizationInterface {

  private $sdk_connector;

  public function __construct(SDKConnectorInterface $sdk_connector) {
    $this->sdk_connector = $sdk_connector;
  }

  /**
   * {@inheritdoc}
   */
  public function isMonetizationEnabled() {
    $org_controller = new OrganizationController($this->sdk_connector->getClient());
    $org_id = $this->sdk_connector->getOrganization();
    $org = $org_controller->load($org_id);

    $is_monetization_enabled = $org->getPropertyValue('features.isMonetizationEnabled');

    return $is_monetization_enabled;
  }
}
