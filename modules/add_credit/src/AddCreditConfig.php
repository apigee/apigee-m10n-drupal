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

namespace Drupal\apigee_m10n_add_credit;

use Drupal\Core\Session\AccountInterface;

/**
 * Class AddCreditConfig.
 */
class AddCreditConfig implements AddCreditConfigInterface {

  /**
   * The default name for the `apigee_m10n_add_credit` module.
   *
   * @var string
   */
  public const CONFIG_NAME = 'apigee_m10n_add_credit.config';

  /**
   * The "Always" value for `apigee_m10n_add_credit.config.notify_on`.
   *
   * @var string
   */
  public const NOTIFY_ALWAYS = 'always';

  /**
   * The "Only on error" value for `apigee_m10n_add_credit.config.notify_on`.
   *
   * @var string
   */
  public const NOTIFY_ON_ERROR = 'error_only';

  /**
   * The name of the field that holds the add credit target value.
   */
  public const TARGET_FIELD_NAME = 'add_credit_target';

  /**
   * {@inheritdoc}
   */
  public static function getEntityTypes(): array {
    $types = [
      'user' => [
        'base_route_name' => 'apigee_monetization.billing',
        'edge_entity_type' => 'developer',
        'id_field_name' => 'mail',
        'entities_callback' => function (AccountInterface $account) {
          return \Drupal::entityTypeManager()
            ->getStorage('developer')
            ->loadMultiple([$account->getEmail()]);
        },
      ],
    ];

    // Enable add credit for teams if the apigee_m10n_teams is enabled.
    // TODO: This could be handle in a submodule or config in the future.
    if (\Drupal::moduleHandler()->moduleExists('apigee_m10n_teams')) {
      $types['team'] = [
        'base_route_name' => 'apigee_monetization_teams.billing',
        'edge_entity_type' => 'team',
        'id_field_name' => 'name',
        'entities_callback' => function (AccountInterface $account) {
          if ($ids = \Drupal::service('apigee_edge_teams.team_membership_manager')->getTeams($account->getEmail())) {
            return \Drupal::entityTypeManager()->getStorage('team')->loadMultiple($ids);
          }

          return [];
        },
      ];
    }

    return $types;
  }

}
