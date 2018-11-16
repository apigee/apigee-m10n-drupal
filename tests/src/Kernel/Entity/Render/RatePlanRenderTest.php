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
    $plan = $this->createPackageRatePlan($package);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($plan->getEntityTypeId());

    $build = $view_builder->view($plan, 'default');

    $this->setRawContent((string) \Drupal::service('renderer')->renderRoot($build));

    $this->assertText($plan->getDisplayName(), 'The plan name is displayed in the rendered rate plan');
    $this->assertText($plan->getDescription(), 'The plan description is displayed in the rendered rate plan');
  }

}
