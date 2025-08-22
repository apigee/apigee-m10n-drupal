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

namespace Drupal\apigee_m10n_teams\Entity;

use Apigee\Edge\Api\ApigeeX\Entity\AppGroupAcceptedRatePlan;
use Apigee\Edge\Api\ApigeeX\Entity\AppGroupAcceptedRatePlanInterface;
use Apigee\Edge\Api\ApigeeX\Entity\DeveloperInterface;
use Apigee\Edge\Entity\EntityInterface as EdgeEntityInterface;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\PurchasedProduct;
use Drupal\apigee_m10n\Entity\XRatePlanInterface;

/**
 * Overrides the `purchased_product` entity class.
 *
 * This is a class for purchased products that is aware of teams.
 */
#[\AllowDynamicProperties]
class TeamsPurchasedProduct extends PurchasedProduct implements TeamsPurchasedProductInterface
{

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
  public function __construct(array $values, ?string $entity_type = NULL, ?EdgeEntityInterface $decorated = NULL)
  {
    // The entity type is not passed from `EdgeEntityBase::createFrom`.
    $entity_type = $entity_type ?? static::ENTITY_TYPE_ID;
    // Bypass the `PurchasedProduct` and `EdgeEntityBase` constructors.
    EntityBase::__construct([], $entity_type);
    // Set the decorated value.
    if ($decorated) {
      $this->decorated = $decorated;
    } else {
      // We override this constructor so we can determine the decorated class.
      $decorated_class = isset($values['appgroup']) ? AppGroupAcceptedRatePlan::class : static::decoratedClass();
      $rc = new \ReflectionClass($decorated_class);
      // Get rid of useless but also problematic null values.
      $values = array_filter($values, function ($value) {
        return !is_null($value);
      });
      $this->decorated = $rc->newInstance($values);
    }
    // Save entity references in this class as well as the decorated instance.
    if (!empty($values['xratePlan']) && $values['xratePlan'] instanceof XRatePlanInterface) {
      $this->setRatePlan($values['xratePlan']);
    }

    // Do not suppress warnings by default.
    $this->suppressWarning = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getProperties(): array
  {
    $properties = parent::getProperties();

    // Add the team property.
    $properties['team'] = 'entity_reference';

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
  {
    $definitions = parent::baseFieldDefinitions($entity_type);

    // Set the target for the team reference to the team entity.
    $definitions['team']->setSetting('target_type', 'team');
    $definitions['team']->setDisplayConfigurable('form', FALSE);

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getTeam()
  {
    // Returns an entity reference. If you need the monetization company
    // reference, you can use `$purchased_product->decorated()->getCompany()` but
    // you have to check `$purchased_product->isTeamPurchasedProduct()` first.
    return $this->isTeamPurchasedProduct() ? $this->getTeamReference() : NULL;
  }

  /**
   * Get the team from the team reference if it exists.
   *
   * @return string
   *   Returns the team ID.
   */
  // private function getTeamId(): ?string {
  //   /** @var \Apigee\Edge\Api\ApigeeX\Entity\AppGroupAcceptedRatePlanInterface $decorated */
  //   $decorated = $this->decorated();
  //   return $decorated ? $decorated->getAppGroup()->id() : NULL;
  // }
  private function getTeamId(): ?string
  {
    /** @var \Apigee\Edge\Api\ApigeeX\Entity\AppGroupAcceptedRatePlanInterface $decorated */
    $decorated = $this->decorated();

    // return $decorated ? $decorated->getAppGroup()->id() : NULL;
    if ($decorated && $appgroup = $decorated->getAppGroup()) {
      return $appgroup->id();
    }

    // Fallback to get the team from the route match.                                                                                                     
    // This is not ideal, but it's a pragmatic solution to this complex problem.                                                                          
    $route_match = \Drupal::service('current_route_match');
    if ($team = $route_match->getParameter('team')) {
      return is_string($team) ? $team : $team->id();
    }
    return NULL;
  }

  /**
   * Get the team entity if it exists.
   *
   * @return \Drupal\apigee_edge_teams\Entity\TeamInterface
   *   Returns the team.
   */
  public function getTeamEntity(): ?TeamInterface
  {
    if ($this->isTeamPurchasedProduct()) {
      return \Drupal::entityTypeManager()->getStorage('team')->load($this->getTeamId());
    }

    return NULL;
  }

  /**
   * Gets an entity reference compatible array for the team.
   *
   * @return array
   *   An entity reference array.
   */
  private function getTeamReference()
  {
    /** @var \Apigee\Edge\Api\ApigeeX\Entity\AppGroupAcceptedRatePlanInterface $decorated */
    $decorated = $this->decorated();
    return ['target_id' => $decorated->getAppGroup()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeveloper(): ?DeveloperInterface
  {
    return !$this->isTeamPurchasedProduct() ? parent::getDeveloper() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isTeamPurchasedProduct(): bool
  {
    return ($this->purchasedProductType() === static::PURCHASED_PRODUCT_TYPE_TEAM);
  }

  /**
   * {@inheritdoc}
   */
  public function purchasedProductType()
  {
    return $this->decorated() instanceof AppGroupAcceptedRatePlanInterface
      ? static::PURCHASED_PRODUCT_TYPE_TEAM
      : static::PURCHASED_PRODUCT_TYPE_DEVELOPER;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner()
  {
    // Team purchased products do not belong to a particular user, but to a team,
    // however the EntityOwnerInterface expects a user, so return NULL instead.
    if ($this->isTeamPurchasedProduct()) {
      return NULL;
    } else {
      return parent::getOwner();
    }
  }

  /**
   * Loads purchased products by team ID.
   *
   * @param string $team_id
   *   The `team` ID.
   *
   * @return array
   *   An array of purchased_product entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadByTeamId(string $team_id): array
  {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->loadByTeamId($team_id);
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = [])
  {
    // Get team collection for team URLs.
    if (($team_id = $this->getTeamId()) && $rel === 'collection') {
      // Build the URL.
      $url = parent::toUrl('team_collection', $options);
      // Strip the `purchased_product` parameter from the collection.
      $url->setRouteParameters(array_diff_key($url->getRouteParameters(), ['purchased_product' => NULL]));
      // Set the team ID.
      $url->setRouteParameter('team', $team_id);

      return $url;
    } else {
      return parent::toUrl($rel, $options);
    }
  }
}
