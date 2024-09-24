<?php

/*
 * Copyright 2021 Google Inc.
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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Entity\Render;

use Apigee\Edge\Api\ApigeeX\Entity\RatePlanInterface;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;
use Drupal\apigee_m10n\Exception\InvalidRatePlanIdException;

/**
 * Kernel test for the rate plan storage.
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Entity\Storage\XRatePlanStorage
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class RatePlanStorageTest extends MonetizationKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup for creating a user.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');

    // Get pre-configured token storage service for testing.
    $this->storeToken();
  }

  /**
   * Test rate plans.
   *
   * @covers ::loadRatePlansByProduct
   */
  public function testloadRatePlansByProduct() {
    $this->stack->reset();
    $xproduct = $this->createApigeexProduct();
    $this->stack->reset();

    // Create one valid and one invalid rate plans.
    $rate_plans[] = $this->createRatePlan($xproduct, RatePlanInterface::TYPE_STANDARD, 'foobar');
    $this->stack->reset();
    $rate_plans[] = $this->createRatePlan($xproduct, RatePlanInterface::TYPE_STANDARD, 'foo/bar');

    $this->stack->reset();
    $this->stack->queueMockResponse(['get_monetization_apigeex_plans' => ['plans' => [$rate_plans]]]);
    $entities = \Drupal::entityTypeManager()
      ->getStorage('xrate_plan')
      ->loadRatePlansByProduct($xproduct->id());

    // Expect only one valid rate plan to be returned from storage.
    $this->assertCount(1, $entities);
  }

  /**
   * Tests valid rate plan ids.
   *
   * @param string $id
   *   The rate plan Idd.
   * @param bool $isValid
   *   TRUE if Id is valid. Otherwise FALSE.
   *
   * @dataProvider ratePlanIdsProvider
   *
   * @covers ::isValidId
   *
   * @throws \Exception
   */
  public function testIsValidId(string $id, bool $isValid) {
    if (!$isValid) {
      $this->expectException(InvalidRatePlanIdException::class);
    }

    /** @var \Drupal\apigee_m10n\Entity\Storage\RatePlanStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('xrate_plan');
    $this->assertEquals($isValid, $storage->isValidId($id));
  }

  /**
   * Provides a list of rate plan ids.
   *
   * @return array
   *   An array of rate plan ids.
   */
  public function ratePlanIdsProvider() {
    return [
      [
        'ks_test_plan',
        TRUE,
      ],
      [
        '15_month_plan-per',
        TRUE,
      ],
      [
        '15/month_rate_plan',
        FALSE,
      ],
    ];
  }

}
