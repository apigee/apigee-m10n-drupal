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

namespace Drupal\apigee_m10n_teams\Entity;

use Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlan;
use Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperInterface;
use Apigee\Edge\Entity\EntityInterface as EdgeEntityInterface;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Overrides the `subscription` entity class.
 *
 * This is a class for purchased plans that is aware of teams.
 */
class TeamRouteAwareSubscription extends Subscription implements TeamRouteAwareSubscriptionInterface {

  /**
   * EdgeEntityBase constructor.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   * @param null|string $entity_type
   *   Type of the entity.
   * @param \Apigee\Edge\Entity\EntityInterface|null $decorated
   *   The SDK entity that this Drupal entity decorates.
   *
   * @throws \ReflectionException
   */
  public function __construct(array $values, ?string $entity_type = NULL, ?EdgeEntityInterface $decorated = NULL) {
    // The entity type is not passed from `EdgeEntityBase::createFrom`.
    $entity_type = $entity_type ?? static::ENTITY_TYPE_ID;
    // Bypass the `Subscription` and `EdgeEntityBase` constructors.
    Entity::__construct([], $entity_type);
    // Set the decorated value.
    if ($decorated) {
      $this->decorated = $decorated;
    }
    else {
      // We override this constructor so we can determine the decorated class.
      $decorated_class = isset($values['company']) ? CompanyAcceptedRatePlan::class : static::decoratedClass();
      $rc = new \ReflectionClass($decorated_class);
      // Get rid of useless but also problematic null values.
      $values = array_filter($values, function ($value) {
        return !is_null($value);
      });
      $this->decorated = $rc->newInstance($values);
    }
    // Save entity references in this class as well as the decorated instance.
    if (!empty($values['ratePlan']) && $values['ratePlan'] instanceof RatePlanInterface) {
      $this->setRatePlan($values['ratePlan']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function getProperties(): array {
    $properties = parent::getProperties();

    // Add the team property.
    $properties['team'] = 'entity_reference';

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $definitions = parent::baseFieldDefinitions($entity_type);

    // Set the target for the team reference to the team entity.
    $definitions['team']->setSetting('target_type', 'team');
    $definitions['team']->setDisplayConfigurable('form', FALSE);

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getTermsAndConditions(): bool {
    if ($this->isTeamSubscription()) {
      $decorated = $this->decorated();
      return apigee_m10n_teams_service()->isLatestTermsAndConditionAccepted($decorated->getCompany()->id());
    }
    else {
      return parent::getTermsAndConditions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTeam() {
    // Returns an entity reference. If you need the monetization company
    // reference, you can use `$subscription->decorated()->getCompany()` but you
    // have to check `$subscription->isTeamSubscription()` first.
    return $this->isTeamSubscription() ? $this->getTeamReference() : NULL;
  }

  /**
   * Get's an entity reference compatible array for the team.
   *
   * @return array
   *   An entity reference array.
   */
  private function getTeamReference() {
    /** @var \Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlanInterface $decorated */
    $decorated = $this->decorated();
    return ['target_id' => $decorated->getCompany()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeveloper(): ?DeveloperInterface {
    return !$this->isTeamSubscription() ? parent::getDeveloper() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isTeamSubscription(): bool {
    return ($this->subscriptionType() === static::SUBSCRIPTION_TYPE_TEAM);
  }

  /**
   * {@inheritdoc}
   */
  public function subscriptionType() {
    return $this->decorated() instanceof CompanyAcceptedRatePlanInterface
      ? static::SUBSCRIPTION_TYPE_TEAM
      : static::SUBSCRIPTION_TYPE_DEVELOPER;
  }

  /**
   * Loads subscriptions by team ID.
   *
   * @param string $team_id
   *   The `team` ID.
   *
   * @return array
   *   An array of Subscription entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadByTeamId(string $team_id): array {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->loadByTeamId($team_id);
  }

}
