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
class RenderTest extends MonetizationKernelTestBase {

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  public function testRenderRatePlan() {

    $package = $this->createPackage();
    $rate_plan = $this->createPackageRatePlan($package);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($rate_plan->getEntityTypeId());

    $build = $view_builder->view($rate_plan, 'default');

    $this->setRawContent((string) \Drupal::service('renderer')->renderRoot($build));

    $this->assertText($rate_plan->getDescription(), 'The plan description is displayed in the rendered rate plan');
    $this->assertLinkByHref("/user/0/monetization/packages/{$rate_plan->getPackage()->id()}/plan/{$rate_plan->id()}", 0, 'The display name links to the rate plan.');
  }

}
