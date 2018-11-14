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

namespace Drupal\Tests\apigee_m10n\Kernel;

use Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface;
use Apigee\Edge\Api\Monetization\Entity\Company;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\user\UserInterface;

/**
 * Tests the `apigee_m10n.sdk_controller_factory` service.
 *
 * @package Drupal\Tests\apigee_m10n\Kernel
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class ApigeeSdkControllerFactoryKernelTest extends MonetizationKernelTestBase {


  /**
   * The controller factory.
   *
   * @var \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface
   */
  protected $controller_factory;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function setUp() {
    parent::setUp();

    $this->controller_factory = $this->container->get('apigee_m10n.sdk_controller_factory');
    static::assertInstanceOf(ApigeeSdkControllerFactoryInterface::class, $this->controller_factory);
  }

  /**
   * Tests the developer balance controller.
   */
  public function testDeveloperBalanceController() {
    $email = $this->randomMachineName() . '@example.com';

    $account = $this
      ->getMockBuilder(UserInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $account->expects($this->any())
      ->method('getEmail')
      ->will($this->returnValue($email));

    /** @var \Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceControllerInterface $controller */
    $controller = $this->controller_factory->developerBalanceController($account);

    static::assertInstanceOf(DeveloperPrepaidBalanceControllerInterface::class, $controller);

    static::assertSame($this->sdk_connector->getOrganization(), $controller->getOrganisationName());
  }

  /**
   * Tests the company balance controller.
   */
  public function testCompanyBalanceController() {
    $company_name = $this->randomMachineName();

    $company = $this
      ->getMockBuilder(Company::class)
      ->disableOriginalConstructor()
      ->getMock();
    $company->expects($this->any())
      ->method('getLegalName')
      ->will($this->returnValue($company_name));

    /** @var \Apigee\Edge\Api\Monetization\Controller\CompanyPrepaidBalanceControllerInterface $controller */
    $controller = $this->controller_factory->companyBalanceController($company);

    static::assertInstanceOf(CompanyPrepaidBalanceControllerInterface::class, $controller);

    static::assertSame($this->sdk_connector->getOrganization(), $controller->getOrganisationName());
  }

  /**
   * Tests the developer balance controller.
   */
  public function testApiPackageController() {
    /** @var \Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface $controller */
    $controller = $this->controller_factory->apiPackageController();

    static::assertInstanceOf(ApiPackageControllerInterface::class, $controller);

    static::assertSame($this->sdk_connector->getOrganization(), $controller->getOrganisationName());
  }

  /**
   * Test the package rate plan controller.
   */
  public function testPackageRatePlanController() {
    /** @var \Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface $controller */
    $controller = $this->controller_factory->packageRatePlanController($this->randomString());

    static::assertInstanceOf(RatePlanControllerInterface::class, $controller);

    static::assertSame($this->sdk_connector->getOrganization(), $controller->getOrganisationName());
  }

}
