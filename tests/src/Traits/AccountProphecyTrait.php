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

namespace Drupal\Tests\apigee_m10n\Traits;

use Drupal\Core\Session\AccountProxyInterface;
use Prophecy\Argument;

/**
 * Trait to prophesize an account.
 */
trait AccountProphecyTrait {

  /**
   * Prophesize a user account.
   *
   * @param array $permissions
   *   Any permissions in the account should have.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The user account.
   */
  protected function prophesizeAccount($permissions = []) {
    static $uid = 2;
    return $this->prophesize(AccountProxyInterface::class)
      ->id()
      ->willReturn($uid++)
      ->getObjectProphecy()
      ->getUsername()
      ->willReturn($this->getRandomGenerator()->word(8))
      ->getObjectProphecy()
      ->getAccountName()
      ->willReturn($this->getRandomGenerator()->word(8))
      ->getObjectProphecy()
      ->getDisplayName()
      ->willReturn("{$this->getRandomGenerator()->word(8)} {$this->getRandomGenerator()->word(12)}")
      ->getObjectProphecy()
      ->getEmail()
      ->willReturn("{$this->randomMachineName()}@example.com")
      ->getObjectProphecy()
      ->isAnonymous()
      ->willReturn(FALSE)
      ->getObjectProphecy()
      ->isAuthenticated()
      ->willReturn(TRUE)
      ->getObjectProphecy()
      ->hasPermission(Argument::any())
      ->will(function ($args) use ($permissions) {
        return in_array($args[0], $permissions);
      })
      ->getObjectProphecy()
      ->reveal();
  }

  /**
   * Set the current user to a mock.
   *
   * @param array $permissions
   *   An array of permissions the current user should have.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  protected function prophesizeCurrentUser($permissions = []) {
    // Set the current user to a prophecy.
    $this->container->set('current_user', $this->prophesizeAccount($permissions));

    return \Drupal::currentUser();
  }

}
