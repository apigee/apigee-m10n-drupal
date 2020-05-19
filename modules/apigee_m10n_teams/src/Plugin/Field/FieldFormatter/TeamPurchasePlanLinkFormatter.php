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

use Drupal\apigee_m10n\Plugin\Field\FieldFormatter\PurchasePlanLinkFormatter;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Link;

/**
 * Class override for the `apigee_purchase_plan_link` field formatter.
 */
class TeamPurchasePlanLinkFormatter extends PurchasePlanLinkFormatter {

  /**
   * Renderable link element.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   Field item variable.
   *
   * @return array
   *   Renderable link element.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function viewValue(FieldItemInterface $item) {
    /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */
    $rate_plan = $item->getEntity();
    $canonical_url = $rate_plan->toUrl();
    if ($canonical_url->getRouteName() === 'entity.rate_plan.team') {
      return Link::createFromRoute($this->getSetting('label'), 'entity.rate_plan.team_purchase', $canonical_url->getRouteParameters())->toRenderable();
    }
    else {
      return parent::viewValue($item);
    }
  }

}
