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

namespace Drupal\Tests\apigee_m10n\Kernel\Entity;

use Drupal\apigee_m10n\Entity\Package;
use Drupal\apigee_m10n\Entity\PackageInterface;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Test the `package` entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class PackageEntityKernelTest extends MonetizationKernelTestBase {

  /**
   * A test package.
   *
   * @var \Drupal\apigee_m10n\Entity\PackageInterface
   */
  protected $package;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->package = $this->createPackage();
  }

  /**
   * Test that we can pass random data and create a rate plan.
   *
   * @throws \Exception
   */
  public function testEntityInstantiation() {
    static::assertInstanceOf(Package::class, $this->package);
  }

  /**
   * Test loading a rate plan from drupal entity storage.
   *
   * @throws \Exception
   */
  public function testLoadPackage() {
    $this->stack
      ->queueMockResponse(['get_monetization_package' => ['package' => $this->package]]);

    // Load the package.
    $package = Package::load($this->package->id());

    static::assertInstanceOf(PackageInterface::class, $package);
    static::assertSame(gettype($package), gettype($this->package));

    // Check properties.
    static::assertSame($this->package->id(), $package->id());
    static::assertSame($this->package->getDisplayName(), $package->getDisplayName());
    static::assertSame($this->package->getName(), $package->getName());
    static::assertSame($this->package->getDescription(), $package->getDescription());
    static::assertSame($this->package->getStatus(), $package->getStatus());
    // Get the package products.
    $products = $package->getApiProducts();
    static::assertGreaterThan(0, $this->count($products));
    static::assertCount(count($this->package->getApiProducts()), $products);

    foreach ($this->package->getApiProducts() as $product_id => $product) {
      static::assertArrayHasKey($product_id, $products);
    }
  }

}
