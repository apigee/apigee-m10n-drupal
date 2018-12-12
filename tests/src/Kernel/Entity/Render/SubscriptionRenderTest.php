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

namespace Drupal\Tests\apigee_m10n\Kernel\Entity\Render;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Performs functional tests on drupal_render().
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class SubscriptionRenderTest extends MonetizationKernelTestBase {

  /**
   * Test package rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

  /**
   * Test subscription.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $subscription;

  /**
   * The developer drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    // Setup for creating a user.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
    $this->developer = $this->createAccount(['view subscription']);
    $this->setCurrentUser($this->developer);

    $this->rate_plan = $this->createPackageRatePlan($this->createPackage());
    $this->subscription = $this->createSubscription($this->developer, $this->rate_plan);
  }

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  public function testRenderSubscription() {

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($this->subscription->getEntityTypeId());

    $build = $view_builder->view($this->subscription, 'default');

    $this->setRawContent((string) \Drupal::service('renderer')->renderRoot($build));

    $this->assertText($this->developer->getDisplayName(), 'The developer display name appears.');
    $start_date = $this->subscription->getStartDate()->format('D, m/d/Y - H:i');
    static::assertNotEmpty($start_date);
    $this->assertText($start_date, 'The start date appears in the rendered subscription.');
  }

}
