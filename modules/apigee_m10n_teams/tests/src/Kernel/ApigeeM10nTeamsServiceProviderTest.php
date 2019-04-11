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

namespace Drupal\Tests\apigee_m10n_teams\Kernel;

use Drupal\apigee_m10n_teams\Entity\ParamConverter\TeamSubscriptionConverter;
use Drupal\apigee_m10n_teams\Entity\Storage\Controller\TeamAcceptedRatePlanSdkControllerProxy;
use Drupal\apigee_m10n_teams\TeamSdkControllerFactory;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the module affected overrides are overridden properly.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class ApigeeM10nTeamsServiceProviderTest extends KernelTestBase {

  public static $modules = [
    'key',
    'user',
    'apigee_edge',
    'apigee_edge_teams',
    'apigee_m10n',
    'apigee_m10n_teams',
  ];

  /**
   * Test modifications to the container.
   */
  public function testServiceAlter() {
    static::assertSame(TeamAcceptedRatePlanSdkControllerProxy::class, $this->container->getDefinition('apigee_m10n.sdk_controller_proxy.subscription')->getClass());
    static::assertSame(TeamSdkControllerFactory::class, $this->container->getDefinition('apigee_m10n.sdk_controller_factory')->getClass());
    static::assertSame(TeamSubscriptionConverter::class, $this->container->getDefinition('paramconverter.entity.subscription')->getClass());
  }

}
