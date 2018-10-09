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

namespace Drupal\Tests\apigee_m10n\FunctionalJavascript;

use Apigee\Edge\Api\Monetization\Entity\ApiPackage;
use Apigee\Edge\Api\Monetization\Entity\ApiProduct;
use Drupal\Core\Url;

/**
 * Functional tests for the package controller.
 *
 * @package Drupal\Tests\apigee_m10n\Functional
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
   * @var $account
   */
  protected $account;

  /**
   * (@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->createAccount([
      'access monetization packages',
      'access purchased monetization packages',
    ]);
    $this->queueOrg();
    $this->drupalLogin($this->account);
  }

  /**
   * Test the catalog page controller.
   *
   * @throws \Behat\Mink\Exception\ElementTextException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function testCatalogPage() {
    $packages = [];
    $package_names = [
      $this->randomMachineName(),
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];

    foreach ($package_names as $name) {
      $product_name = $name . 'Product';

      $packages[] = new ApiPackage([
        'id' => strtolower($name),
        'name' => $name,
        'description' => $name . ' description.',
        'displayName' => $name . ' display name',
        'apiProducts' => [new ApiProduct([
          'id' => strtolower($product_name),
          'name' => $product_name,
          'description' => $product_name . ' description.',
          'displayName' => $product_name . ' display name',
        ])]
      ]);
    }

    $this->stack
      ->queueFromResponseFile(['get_monetization_packages' => ['packages' => $packages]])
      ->queueFromResponseFile(['get_monetization_packages' => ['packages' => array_slice($packages, -1)]]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.packages', [
      'user' => $this->account->id(),
    ]));

    $this->assertCssElementContains('h1.page-title', 'Packages');
    $prefix = 'ul.apigee-package-list > li:nth-child(1) ';
    // Details should be hidden before clicking on the row.
    static::assertFalse($this->getSession()->getPage()->find('css', "{$prefix} div.apigee-package-description")->isVisible());

    // Click to view product details.
    $this->click("$prefix div.apigee-sdk-package-basic");
    $this->assertCssElementContains("{$prefix} div.apigee-package-id",           $packages[0]->id());
    $this->assertCssElementContains("{$prefix} div.apigee-package-name",         $packages[0]->getName());
    $this->assertCssElementContains("{$prefix} div.apigee-package-description",  $packages[0]->getDescription());
    $this->assertCssElementContains("{$prefix} div.apigee-package-status",       $packages[0]->getStatus());
    // Set the selector prefix for the 1st product of the 1st package.
    $prefix = 'ul.apigee-package-list > li:nth-child(1) ul.apigee-product-list li:nth-child(1)';
    // Get the product.
    /** @var \Apigee\Edge\Api\Monetization\Entity\ApiProduct $product */
    $product = $packages[0]->getApiProducts()[0];
    $this->assertCssElementContains("{$prefix} div.apigee-product-display-name", $product->getDisplayName());
    $this->assertCssElementContains("{$prefix} div.apigee-product-id",           $product->id());
    $this->assertCssElementContains("{$prefix} div.apigee-product-name",  $product->getName());
    $this->assertCssElementContains("{$prefix} div.apigee-product-description",  $product->getDescription());

    // Make necessary assertions for the second row.
    $prefix = 'ul.apigee-package-list > li:nth-child(2)';
    // Details should be hidden before clicking on the row.
    static::assertFalse($this->getSession()->getPage()->find('css', "{$prefix} div.apigee-package-description")->isVisible());

    // Click to view product details.
    $this->click("$prefix div.apigee-sdk-package-basic");
    $this->assertCssElementContains("{$prefix} div.apigee-package-id",           $packages[1]->id());
    $this->assertCssElementContains("{$prefix} div.apigee-package-name",         $packages[1]->getName());
    $this->assertCssElementContains("{$prefix} div.apigee-package-description",  $packages[1]->getDescription());
    $this->assertCssElementContains("{$prefix} div.apigee-package-status",       $packages[1]->getStatus());
    // Set the selector prefix for the 1st product of the 1st package.
    $prefix = 'ul.apigee-package-list > li:nth-child(2) ul.apigee-product-list li:nth-child(1)';
    // Get the product.
    /** @var \Apigee\Edge\Api\Monetization\Entity\ApiProduct $product */
    $product = $packages[1]->getApiProducts()[0];
    $this->assertCssElementContains("{$prefix} div.apigee-product-display-name", $product->getDisplayName());
    $this->assertCssElementContains("{$prefix} div.apigee-product-id",           $product->id());
    $this->assertCssElementContains("{$prefix} div.apigee-product-name",  $product->getName());
    $this->assertCssElementContains("{$prefix} div.apigee-product-description",  $product->getDescription());
  }
}
