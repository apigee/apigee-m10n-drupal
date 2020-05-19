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

namespace Drupal\apigee_m10n\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Render\Markup;

/**
 * Admin list builder for `product_bundle` entities.
 */
class ProductBundleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'title' => $this->t('Title'),
      'id' => $this->t('Id'),
      'description' => $this->t('Description'),
      'products' => $this->t('Products'),
      'status' => $this->t('Status'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\apigee_m10n\Entity\ProductBundleInterface $entity */
    // Build a list of product links.
    $product_links = [];
    // ApiProducts is an entity reference field so we treat it as such.
    foreach ($entity->get('apiProducts') as $delta => $value) {
      /** @var \Drupal\apigee_edge\Entity\ApiProduct $product */
      $product = $value->entity;
      $product_links[$delta] = $product->hasLinkTemplate('canonical') ? $product->toLink($product->label())->toString() : $product->label();
    }

    return [
      'title' => $entity->toLink($entity->label()),
      'id' => $entity->id(),
      'description' => $entity->getDescription(),
      'products' => Markup::create(implode(', ', $product_links)),
      'status' => $entity->getStatus(),
    ];
  }

}
