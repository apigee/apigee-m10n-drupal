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

namespace Drupal\apigee_m10n\Controller;

use Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller for balances.
 *
 * This is modeled after an entity list builder with some additions.
 * See: `\Drupal\Core\Entity\EntityListBuilderInterface`.
 */
interface PrepaidBalanceControllerInterface {

  /**
   * Cache prefix that is used for cache tags for this controller.
   */
  const CACHE_PREFIX = 'apigee.monetization.prepaid_balance';

  /**
   * View prepaid balance and account statements for teams.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array or a redirect response.
   *
   * @throws \Exception
   */
  public function render();

  /**
   * Gets the title of the page.
   */
  public function getTitle();

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::render()
   */
  public function buildHeader();

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface $balance
   *   The SDK balance entity for this row of the list.
   *
   * @return array
   *   A render array structure of fields for this entity.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::render()
   */
  public function buildRow(PrepaidBalanceInterface $balance);

  /**
   * Loads the balances for the listing.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface[]
   *   A list of apigee monetization prepaid balance entities.
   *
   * @throws \Exception
   */
  public function load();

  /**
   * Helper to get the billing cache tags.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The cache tags.
   */
  public static function getCacheTags(EntityInterface $entity);

  /**
   * Helper to get the cache id.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param null $suffix
   *   The suffix for the cache id.
   *
   * @return string
   *   The cache id.
   */
  public static function getCacheId(EntityInterface $entity, $suffix = NULL);

}
