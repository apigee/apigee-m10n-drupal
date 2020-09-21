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

namespace Drupal\apigee_m10n\Entity\ParamConverter;

use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Param converter for up-casting `purchased_plan` entity IDs to full objects.
 *
 * {@inheritdoc}
 */
class PurchasedPlanConverter extends EntityConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   */
  public function convert($value, $definition, $name, array $defaults) {
    // Get the user from defaults.
    $user = $defaults['user'] ?? FALSE;
    // Load the user if it is still a string.
    $user = (!$user || $user instanceof UserInterface) ? $user : User::load($user);
    // Get the developer ID.
    $developer_id = $user instanceof UserInterface ? $user->getEmail() : FALSE;
    // `$developer_id` will be empty for the anonymous. Returning NULL = 404.
    return empty($developer_id) ? NULL : $this->loadPurchasedPlanById($developer_id, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    // This only applies to purchased_plan entities.
    return (parent::applies($definition, $name, $route) && $definition['type'] === 'entity:purchased_plan');
  }

  /**
   * Loads the purchased_plan by developer/purchased plan ID.
   *
   * @param string $developer_id
   *   The developer ID for the purchased_plan.
   * @param string $purchased_plan_id
   *   The purchased plan id.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The `purchased_plan` entity.
   */
  protected function loadPurchasedPlanById($developer_id, $purchased_plan_id) {
    try {
      $purchased_plan = $this->entityTypeManager
        ->getStorage('purchased_plan')
        ->loadById($developer_id, $purchased_plan_id);
    }
    catch (\Exception $ex) {
      throw new NotFoundHttpException('Purchased plan not found for developer', $ex);
    }

    return $purchased_plan;
  }

}
