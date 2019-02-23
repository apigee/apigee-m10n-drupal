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

use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\apigee_m10n\Plugin\Field\FieldFormatter\SubscribeFormFormatter;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Override class for the `apigee_subscribe_form` field formatter.
 */
class TeamSubscribeFormFormatter extends SubscribeFormFormatter {

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
      $subscription = Subscription::create([
        'ratePlan' => $item->getEntity(),
        'team' => $value['team']->decorated(),
        'startDate' => new \DateTimeImmutable(),
      ]);
      return $this->entityFormBuilder->getForm($subscription, 'default', [
        'save_label' => $this->t('@save_label', ['@save_label' => $this->getSetting('label')]),
      ]);
    }
    else {
      return parent::viewValue($item);
    }
  }

}