<?php

/*
 * Copyright 2025 Google Inc.
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

namespace Drupal\apigee_m10n_teams\Entity\Access;

use Apigee\Edge\Api\Monetization\Entity\StandardRatePlanInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\apigee_m10n\Entity\Access\XRatePlanAccessControlHandler;
use Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface;
use Drupal\apigee_m10n_teams\MonetizationTeamsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the rate_plan entity.
 */
class TeamXRatePlanAccessControlHandler extends XRatePlanAccessControlHandler {


  /**
   * The teams monetization service.
   *
   * @var \Drupal\apigee_m10n_teams\MonetizationTeamsInterface
   */
  protected $teamMonitization;

  /**
   * The team access service.
   *
   * @var \Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface
   */
  protected $teamAccess;

  /**
   * Constructs an access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\apigee_m10n_teams\MonetizationTeamsInterface $team_monetization
   *   Teams monetization factory.
   * @param \Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface $team_access
   *   The team access service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entityTypeManager, MonetizationTeamsInterface $team_monetization, TeamPermissionAccessInterface $team_access) {
    parent::__construct($entity_type, $entityTypeManager);
    $this->teamMonitization = $team_monetization;
    $this->teamAccess = $team_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('apigee_m10n.teams'),
      $container->get('apigee_m10n_teams.access_check.team_permission')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Get the SDK rate plan.
    /** @var \Apigee\Edge\Api\ApigeeX\Entity\RatePlanInterface $sdk_rate_plan */
    $sdk_rate_plan = $entity->decorated();

    if ($sdk_rate_plan instanceof StandardRatePlanInterface && $this->teamMonitization->currentTeam()) {
      return $this->teamMonitization->entityAccess($entity, $operation, $account);
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
