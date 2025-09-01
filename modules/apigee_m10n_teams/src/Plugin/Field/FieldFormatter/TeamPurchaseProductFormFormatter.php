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

namespace Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter;

use Apigee\Edge\Api\ApigeeX\Entity\AppGroup;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\PurchasedProduct;
use Drupal\apigee_m10n\Plugin\Field\FieldFormatter\PurchaseProductFormFormatter;

/**
 * Override class for the `apigee_purchase_plan_form` field formatter.
 */
class TeamPurchaseProductFormFormatter extends PurchaseProductFormFormatter {

  /**
   * Renderable entity form that handles teams.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   Field item variable.
   *
   * @return array
   *   Renderable form elements.
   *
   * @throws \Exception
   */
  protected function viewValue(FieldItemInterface $item) {
    if (($value = $item->getValue()) && (isset($value['team'])) && ($value['team'] instanceof TeamInterface)) {
      if ($item->getEntity()->access('purchase')) {
        $create_values = [
          'xratePlan' => $item->getEntity(),
          'appgroup' => new AppGroup(['id' => $value['team']->id()]),
          'startDate' => new \DateTimeImmutable(),
        ];
        $purchased_product = PurchasedProduct::create($create_values);
        return $this->entityFormBuilder->getForm($purchased_product, 'default', [
          'save_label' => $this->t('@save_label', ['@save_label' => $this->getSetting('label')]),
        ]);
      }
    }
    else {
      return parent::viewValue($item);
    }
  }

}
