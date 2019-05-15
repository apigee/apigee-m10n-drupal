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

namespace Drupal\apigee_m10n\Entity;

use Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperInterface;

/**
 * Defines the interface for subscription entity objects.
 */
interface SubscriptionInterface extends AcceptedRatePlanInterface {

  /**
   * Status text for ended subscriptions.
   */
  const STATUS_ENDED = 'Ended';

  /**
   * Status text for future subscriptions.
   */
  const STATUS_FUTURE = 'Future';

  /**
   * Status text for active subscriptions.
   */
  const STATUS_ACTIVE = 'Active';

  /**
   * Loads subscriptions by developer email.
   *
   * @param string $developer_id
   *   The email of a developer registered with apigee edge.
   *
   * @return \Drupal\apigee_m10n\Entity\Subscription[]
   *   An array of subscription entities for a given developer (identified by
   *   email).
   */
  public static function loadByDeveloperId(string $developer_id): array;

  /**
   * Getter for the `isSubscriptionActive` call.
   *
   * @return bool
   *   Whether or not this is an active subscription.
   */
  public function isSubscriptionActive(): bool;

  /**
   * Getter for the `getSubscriptionStatus` call.
   *
   * @return string
   *   Subscription status as a string.
   */
  public function getSubscriptionStatus(): string;

  /**
   * Gets data for the `apigee_tnc` field formatter.
   *
   * @return bool
   *   The rate plan.
   */
  public function getTermsAndConditions(): bool;

  /**
   * Get's the developer of a developer accepted rate plan.
   *
   * Assuming this subscription decorates a developer accepted rate plan, the
   * developer will be returned, The developer can be null for a new developer
   * accepted rate plan.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\DeveloperInterface|null
   *   The developer accepted rate plan developer.
   */
  public function getDeveloper(): ?DeveloperInterface;

  /**
   * Sets the suppressWarning value.
   *
   * @param bool $value
   *   Set to TRUE to suppress all warnings.
   *
   * @return \Drupal\apigee_m10n\Entity\SubscriptionInterface
   *   The subscription entity.
   */
  public function setSuppressWarning(bool $value): SubscriptionInterface;

  /**
   * Flag that specifies whether to suppress the error if the developer
   * attempts to accept a rate plan that overlaps another accepted
   * rate plan.
   *
   * @return bool
   *   TRUE is warning should be suppressed.
   */
  public function getSuppressWarning(): bool;

}
