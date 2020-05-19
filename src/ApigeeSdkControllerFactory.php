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

use Apigee\Edge\Api\Management\Entity\CompanyInterface;
use Apigee\Edge\Api\Monetization\Controller\ApiPackageController;
use Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\ApiProductController;
use Apigee\Edge\Api\Monetization\Controller\ApiProductControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceController;
use Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\DeveloperAcceptedRatePlanController;
use Apigee\Edge\Api\Monetization\Controller\DeveloperController;
use Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceController;
use Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\DeveloperReportDefinitionController;
use Apigee\Edge\Api\Monetization\Controller\DeveloperReportDefinitionControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\RatePlanController;
use Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\SupportedCurrencyController;
use Apigee\Edge\Api\Monetization\Controller\SupportedCurrencyControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\TermsAndConditionsController;
use Apigee\Edge\Api\Monetization\Controller\DeveloperTermsAndConditionsController;
use Apigee\Edge\Api\Monetization\Controller\TermsAndConditionsControllerInterface;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\user\UserInterface;

/**
 * The `apigee_m10n.sdk_controller_factory` service class.
 */
class ApigeeSdkControllerFactory implements ApigeeSdkControllerFactoryInterface {

  /**
   * The Apigee Edge SDK connector.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdk_connector;

  /**
   * The org fo this installation.
   *
   * @var string
   */
  protected $org;

  /**
   * The HTTP Client to use for each controller.
   *
   * @var \Apigee\Edge\ClientInterface
   */
  protected $client;

  /**
   * A cache of reusable controllers.
   *
   * @var array
   */
  protected $controllers = [];

  /**
   * Monetization constructor.
   *
   * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
   *   The Apigee Edge SDK connector.
   */
  public function __construct(SDKConnectorInterface $sdk_connector) {
    $this->sdk_connector = $sdk_connector;
  }

  /**
   * {@inheritdoc}
   */
  public function developerController(): DeveloperController {
    if (empty($this->controllers[__FUNCTION__])) {
      // Create a new developer controller.
      $this->controllers[__FUNCTION__] = new DeveloperController($this->getOrganization(), $this->getClient());
    }
    return $this->controllers[__FUNCTION__];
  }

  /**
   * {@inheritdoc}
   */
  public function developerTermsAndConditionsController(string $developer_id): DeveloperTermsAndConditionsController {
    return new DeveloperTermsAndConditionsController(
      $developer_id,
      $this->getOrganization(),
      $this->getClient()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function developerBalanceController(UserInterface $developer): DeveloperPrepaidBalanceControllerInterface {
    $developer_email = $developer->getEmail();
    if (empty($this->controllers[__FUNCTION__][$developer_email])) {
      // Don't assume the bucket has been initialized.
      $this->controllers[__FUNCTION__] = $this->controllers[__FUNCTION__] ?? [];
      // Create a new balance controller.
      $this->controllers[__FUNCTION__][$developer_email] = new DeveloperPrepaidBalanceController(
        $developer_email,
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__][$developer_email];
  }

  /**
   * {@inheritdoc}
   */
  public function companyBalanceController(CompanyInterface $company): CompanyPrepaidBalanceControllerInterface {
    $name = $company->getName();
    if (empty($this->controllers[__FUNCTION__][$name])) {
      // Don't assume the bucket has been initialized.
      $this->controllers[__FUNCTION__] = $this->controllers[__FUNCTION__] ?? [];
      // Create a new balance controller.
      $this->controllers[__FUNCTION__][$name] = new CompanyPrepaidBalanceController(
        $name,
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__][$name];
  }

  /**
   * {@inheritdoc}
   */
  public function apiProductController(): ApiProductControllerInterface {
    if (empty($this->controllers[__FUNCTION__])) {
      // Create a new org controller.
      $this->controllers[__FUNCTION__] = new ApiProductController(
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__];
  }

  /**
   * {@inheritdoc}
   */
  public function apiPackageController(): ApiPackageControllerInterface {
    if (empty($this->controllers[__FUNCTION__])) {
      // Create a new org controller.
      $this->controllers[__FUNCTION__] = new ApiPackageController(
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__];
  }

  /**
   * {@inheritdoc}
   */
  public function ratePlanController($product_bundle_id): RatePlanControllerInterface {
    if (empty($this->controllers[__FUNCTION__][$product_bundle_id])) {
      // Don't assume the bucket has been initialized.
      $this->controllers[__FUNCTION__] = $this->controllers[__FUNCTION__] ?? [];
      // Create a new rate plan controller.
      $this->controllers[__FUNCTION__][$product_bundle_id] = new RatePlanController(
        $product_bundle_id,
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__][$product_bundle_id];
  }

  /**
   * {@inheritdoc}
   */
  public function developerAcceptedRatePlanController(string $developer_id): DeveloperAcceptedRatePlanController {
    if (empty($this->controllers[__FUNCTION__][$developer_id])) {
      // Don't assume the bucket has been initialized.
      $this->controllers[__FUNCTION__] = $this->controllers[__FUNCTION__] ?? [];
      // Create a new balance controller.
      $this->controllers[__FUNCTION__][$developer_id] = new DeveloperAcceptedRatePlanController(
        $developer_id,
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__][$developer_id];
  }

  /**
   * {@inheritdoc}
   */
  public function supportedCurrencyController(): SupportedCurrencyControllerInterface {
    if (empty($this->controllers[__FUNCTION__])) {
      // Create a new org controller.
      $this->controllers[__FUNCTION__] = new SupportedCurrencyController(
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__];
  }

  /**
   * {@inheritdoc}
   */
  public function developerReportDefinitionController(string $developer_id): DeveloperReportDefinitionControllerInterface {
    if (empty($this->controllers[__FUNCTION__][$developer_id])) {
      $this->controllers[__FUNCTION__][$developer_id] = new DeveloperReportDefinitionController(
        $developer_id,
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__][$developer_id];
  }

  /**
   * {@inheritdoc}
   */
  public function termsAndConditionsController(): TermsAndConditionsControllerInterface {
    if (empty($this->controllers[__FUNCTION__])) {
      // Create a new TnC controller.
      $this->controllers[__FUNCTION__] = new TermsAndConditionsController(
        $this->getOrganization(),
        $this->getClient()
      );
    }
    return $this->controllers[__FUNCTION__];
  }

  /**
   * Gets the org from the SDK connector.
   *
   * @return string
   *   The organization id.
   */
  protected function getOrganization() {
    $this->org = $this->org ?? $this->sdk_connector->getOrganization();
    return $this->org;
  }

  /**
   * Get the SDK client from the SDK connector.
   *
   * @return \Apigee\Edge\ClientInterface
   *   The sdk client.
   */
  protected function getClient() {
    $this->client = $this->client ?? $this->sdk_connector->getClient();
    return $this->client;
  }

}
