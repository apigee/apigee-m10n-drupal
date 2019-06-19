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

namespace Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter;

use Apigee\Edge\Api\Monetization\Entity\Company;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\PurchasedPlan;
use Drupal\apigee_m10n\Plugin\Field\FieldFormatter\PurchasePlanFormFormatter;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Override class for the `apigee_purchase_plan_form` field formatter.
 */
class TeamPurchasePlanFormFormatter extends PurchasePlanFormFormatter {

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
        $purchased_plan = PurchasedPlan::create([
          'ratePlan' => $item->getEntity(),
          'company' => new Company(['id' => $value['team']->id()]),
          'startDate' => new \DateTimeImmutable(),
        ]);
        return $this->entityFormBuilder->getForm($purchased_plan, 'default', [
          'save_label' => $this->t('@save_label', ['@save_label' => $this->getSetting('label')]),
        ]);
      }
    }
    else {
      return parent::viewValue($item);
    }
  }

}
