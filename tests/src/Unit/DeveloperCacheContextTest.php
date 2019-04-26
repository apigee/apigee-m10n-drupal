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

use Drupal\apigee_m10n\Cache\DeveloperCacheContext;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the testing the `url.developer` cache context.
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 */
class DeveloperCacheContextTest extends UnitTestCase {

  /**
   * Tests the developer cache context service.
   */
  public function testDeveloperCacheContext() {
    // Test developer context before param converters run.
    $request = $this->prophesize(Request::class)
      ->get('user')
      ->willReturn(2)
      ->getObjectProphecy();

    $request_stack = $this->prophesize(RequestStack::class)
      ->getCurrentRequest()
      ->willReturn($request)
      ->getObjectProphecy();

    $developer_context = new DeveloperCacheContext($request_stack->reveal());

    static::assertSame('Developer', (string) DeveloperCacheContext::getLabel());
    static::assertSame('2', $developer_context->getContext());

    // Test developer context after param converters run.
    $user = $this->prophesize(UserInterface::class)
      ->id()
      ->willReturn(2)
      ->getObjectProphecy();

    $request = $this->prophesize(Request::class)
      ->get('user')
      ->willReturn($user->reveal())
      ->getObjectProphecy();

    $request_stack = $this->prophesize(RequestStack::class)
      ->getCurrentRequest()
      ->willReturn($request)
      ->getObjectProphecy();

    $developer_context = new DeveloperCacheContext($request_stack->reveal());

    static::assertSame('Developer', (string) DeveloperCacheContext::getLabel());
    static::assertSame('2', $developer_context->getContext());
  }

}

// @codingStandardsIgnoreStart
/**
 * This is a hack since we don't want to load `bootstrap.inc` in a unit test.
 */
namespace Drupal\apigee_m10n\Cache;

use Drupal\Component\Render\MarkupInterface;
use Prophecy\Prophet;

/**
 * {@inheritdoc}
 */
function t($string, array $args = [], array $options = []) {
  $prophet = new Prophet();
  return $prophet->prophesize(MarkupInterface::class)
    ->__toString()
    ->willReturn($string)
    ->getObjectProphecy()
    ->reveal();
}
// @codingStandardsIgnoreEnd
