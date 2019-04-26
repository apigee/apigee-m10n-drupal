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

use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n_teams\Cache\TeamCacheContext;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the testing the `url.team` cache context.
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_unit
 */
class TeamCacheContextTest extends UnitTestCase {

  /**
   * Tests the team cache context service.
   */
  public function testTeamCacheContext() {
    // Test team context before param converters run.
    $request = $this->prophesize(Request::class)
      ->get('team')
      ->willReturn('team-foo')
      ->getObjectProphecy();

    $request_stack = $this->prophesize(RequestStack::class)
      ->getCurrentRequest()
      ->willReturn($request)
      ->getObjectProphecy();

    $team_context = new TeamCacheContext($request_stack->reveal());

    static::assertSame('Team', (string) TeamCacheContext::getLabel());
    static::assertSame('team-foo', $team_context->getContext());

    // Test team context before param converters run.
    $user = $this->prophesize(TeamInterface::class)
      ->id()
      ->willReturn('team-foo')
      ->getObjectProphecy();

    $request = $this->prophesize(Request::class)
      ->get('team')
      ->willReturn($user->reveal())
      ->getObjectProphecy();

    $request_stack = $this->prophesize(RequestStack::class)
      ->getCurrentRequest()
      ->willReturn($request)
      ->getObjectProphecy();

    $team_context = new TeamCacheContext($request_stack->reveal());

    static::assertSame('Team', (string) TeamCacheContext::getLabel());
    static::assertSame('team-foo', $team_context->getContext());
  }

}

// @codingStandardsIgnoreStart
/**
 * This is a hack since we don't want to load `bootstrap.inc` in a unit test.
 */
namespace Drupal\apigee_m10n_teams\Cache;

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
