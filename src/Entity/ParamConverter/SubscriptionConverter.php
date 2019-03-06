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

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting subscriptions entity IDs to full objects.
 *
 * {@inheritdoc}
 */
class SubscriptionConverter extends EntityConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    /** @var \Drupal\apigee_m10n\Entity\Storage\SubscriptionStorage $storage */
    $storage = $this->entityManager->getStorage($entity_type_id);

    // Get the user from defaults.
    $user = $defaults['user'] ?? FALSE;
    // Load the user if it is still a string.
    $user = (!$user || $user instanceof UserInterface) ? $user : User::load($user);
    // Get the developer ID.
    $develoepr_id = $user instanceof UserInterface ? $user->getEmail() : FALSE;

    $entity = !empty($develoepr_id) ? $storage->loadById($develoepr_id, $value) : NULL;

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    // This only applies to subscription entities.
    return (parent::applies($definition, $name, $route) && $definition['type'] === 'entity:subscription');
  }

}
