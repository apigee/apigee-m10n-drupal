<?php

/**
 * Copyright 2020 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_m10n\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the reports download form for developer.
 */
class ReportsDownloadForm extends ReportsDownloadFormBase {

  /**
   * {@inheritdoc}
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    $user = $route_match->getParameter('user');
    return AccessResult::allowedIf(
      $account->hasPermission('download any reports') ||
      ($account->hasPermission('download own reports') && $account->id() === $user->id())
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityId(): string {
    if ($user = $this->routeMatch->getParameter('user')) {
      return $this->entityTypeManager->getStorage('user')->load($user)->getEmail();
    }

    return $this->currentUser->getEmail();
  }

}
