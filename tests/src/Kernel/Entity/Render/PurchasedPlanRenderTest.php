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

use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Performs functional tests on drupal_render().
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class PurchasedPlanRenderTest extends MonetizationKernelTestBase {

  /**
   * Test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

  /**
   * Test purchased plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $purchased_plan;

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
    $this->developer = $this->createAccount(['view own purchased_plan']);
    $this->setCurrentUser($this->developer);

    // Set the default timezone for formatting the start  date.
    $purchased_plan_default_display = \Drupal::config('core.entity_view_display.purchased_plan.purchased_plan.default')->get('content');
    $purchased_plan_default_display['startDate']['settings']['timezone'] = 'America/Los_Angeles';
    \Drupal::configFactory()->getEditable('core.entity_view_display.purchased_plan.purchased_plan.default')->set('content', $purchased_plan_default_display)->save();

    $this->rate_plan = $this->createRatePlan($this->createPackage());
    $this->purchased_plan = $this->createPurchasedPlan($this->developer, $this->rate_plan);
  }

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  public function testRenderPurchasedPlan() {

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($this->purchased_plan->getEntityTypeId());

    $build = $view_builder->view($this->purchased_plan, 'default');

    $this->setRawContent((string) \Drupal::service('renderer')->renderRoot($build));

    $this->assertText($this->developer->getDisplayName(), 'The developer display name appears.');
    // The formatter will render with the default time zone.
    $start_date = $this->purchased_plan
      ->getStartDate()
      ->setTimezone(new \DateTimeZone('America/Los_Angeles'))
      ->format('D, m/d/Y - H:i');
    static::assertNotEmpty($start_date);
    $this->assertText($start_date, 'The start date appears in the rendered purchased plan.');
  }

}
