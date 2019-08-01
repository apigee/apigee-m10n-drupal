<?php

/*
 * Copyright 2018 Google Inc.
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

namespace Drupal\apigee_m10n_teams\Entity\Permissions;

use Drupal\apigee_edge_teams\DynamicTeamPermissionProviderInterface;
use Drupal\apigee_edge_teams\Structure\TeamPermission;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the default team permissions.
 */
class MonetizationTeamPermissionsProvider implements DynamicTeamPermissionProviderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function permissions(): array {
    $group = $this->t('Purchased plans');
    $plan_group = $this->t('Rate plan');
    $product_bundle_group = $this->t('Product bundle');
    return [
      'purchase rate_plan' => new TeamPermission(
        'purchase rate_plan',
        $this->t('Purchase a rate plan'),
        $group,
        $this->t('This allows a team member to purchase a plan.')
      ),
      'update purchased_plan' => new TeamPermission(
        'update purchased_plan',
        $this->t('Update a purchased plan'),
        $group,
        $this->t('This allows a team member to cancel a purchased plan.')
      ),
      'view purchased_plan' => new TeamPermission(
        'view purchased_plan',
        $this->t('View purchased plans'),
        $group,
        $this->t('This allows a team member to view purchased plans')
      ),
      // Rate plans.
      'view rate_plan' => new TeamPermission(
        'view rate_plan',
        $this->t('View rate plans'),
        $plan_group
      ),
      // Product bundles.
      'view product_bundle' => new TeamPermission(
        'view product_bundle',
        $this->t('View product bundle'),
        $product_bundle_group
      ),
      'edit billing details' => new TeamPermission(
        'edit billing details',
        $this->t('Edit billing details'),
        $this->t('Billing details'),
        $this->t('This allows a team member to edit billing details')
      ),
    ];
  }

}
