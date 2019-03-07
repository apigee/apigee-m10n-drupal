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

namespace Drupal\Tests\apigee_m10n\FunctionalJavascript;

use Drupal\Core\Url;

/**
 * Functional tests for the package controller.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Controller\PackagesController
 */
class PackageControllerFunctionalTest extends MonetizationFunctionalJavascriptTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $develoepr;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function setUp() {
    parent::setUp();

    $this->develoepr = $this->createAccount([
      'view package',
      'view rate_plan',
      'view subscription',
    ]);
    $this->drupalLogin($this->develoepr);
  }

  /**
   * Test the catalog page controller.
   *
   * @throws \Exception
   */
  public function testCatalogPage() {
    $packages = [
      $this->createPackage(),
      $this->createPackage(),
    ];
    $sdk_packges = [];
    $rate_plans = [];
    // Create rate plans for each package.
    foreach ($packages as $package) {
      $rate_plans[$package->id()] = $this->createPackageRatePlan($package);
      $sdk_packges[] = $package->decorated();
    }

    $this->queueOrg();
    $this->stack
      ->queueMockResponse(['get_monetization_packages' => ['packages' => $sdk_packges]]);

    foreach ($packages as $package) {
      foreach ($package->decorated()->getApiProducts() as $api_product) {
        $this->stack->queueMockResponse(['api_product' => ['product' => $api_product]]);
      }
      $this->stack->queueMockResponse(['get_monetization_package_plans' => ['plans' => [$rate_plans[$package->id()]]]]);
    }

    $this->drupalGet(Url::fromRoute('apigee_monetization.packages', [
      'user' => $this->develoepr->id(),
    ]));

    $this->assertCssElementContains('h1.page-title', 'Packages');
    for ($i = 0; $i < 1; $i++) {
      $css_index = $i + 1;
      $prefix = "ul.apigee-package-list > li:nth-child({$css_index}) ";
      // Details should be hidden before clicking on the row.
      static::assertFalse($this->getSession()->getPage()->find('css', "{$prefix} .field--name-description > .field__item")->isVisible());

      // Click to view product details.
      $this->click("$prefix div.apigee-sdk-package-basic");
      // Details should be hidden before clicking on the row.
      static::assertTrue($this->getSession()->getPage()->find('css', "{$prefix} .field--name-description > .field__item")->isVisible());
      $this->assertCssElementContains("{$prefix} .field--name-id > .field__item", $packages[$i]->id());
      $this->assertCssElementContains("{$prefix} .field--name-displayname > .field__item", $packages[$i]->getDisplayName());
      $this->assertCssElementContains("{$prefix} .field--name-description > .field__item", $packages[$i]->getDescription());
      $this->assertCssElementContains("{$prefix} .field--name-status > .field__item", $packages[$i]->getStatus());

      for ($n = 0; $n < count($packages[$i]->decorated()->getApiProducts()); $n++) {
        $product_css_index = $n + 1;
        // Set the selector prefix for the 1st product of the 1st package.
        $prefix = "ul.apigee-package-list > li:nth-child({$css_index}) .field--name-apiproducts > .field__items > .field__item:nth-child({$product_css_index})";

        // Get the product.
        /** @var \Apigee\Edge\Api\Monetization\Entity\ApiProduct $product */
        $product = $packages[$i]->decorated()->getApiProducts()[$n];
        $this->assertCssElementContains("{$prefix} div.apigee-product-display-name", $product->getDisplayName());
        $this->assertCssElementContains("{$prefix} div.apigee-product-id", $product->id());
        $this->assertCssElementContains("{$prefix} div.apigee-product-name", $product->getName());
        $this->assertCssElementContains("{$prefix} div.apigee-product-description", $product->getDescription());
      }

      // Set the selector prefix for the 1st rate plan of the 1st package.
      $prefix = "ul.apigee-package-list > li:nth-child({$css_index}) .field--name-rateplans > .field__items > .field__item:nth-child(1)";
      // Get the rate plan.
      /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */
      $rate_plan = $rate_plans[$packages[$i]->id()];

      static::assertNotSame($rate_plan->getDisplayName(), $rate_plan->getDescription());
      $this->assertCssElementContains("{$prefix} .field--name-displayname", $rate_plan->getDisplayName());
      $this->assertCssElementContains("{$prefix} .field--name-description", $rate_plan->getDescription());

      $this->assertCssElementContains("{$prefix} .field--name-rateplandetails .rate-plan-detail", $rate_plan->getRatePlanDetails()[0]->getDuration() . ' ' . strtolower($rate_plan->getRatePlanDetails()[0]->getDurationType()));
    }
  }

}
