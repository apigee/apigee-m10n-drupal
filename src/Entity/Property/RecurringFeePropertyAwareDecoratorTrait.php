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

namespace Drupal\apigee_m10n\Entity\Property;

/**
 * Trait RecurringFeePropertyAwareDecoratorTrait.
 */
trait RecurringFeePropertyAwareDecoratorTrait {

  /**
   * {@inheritdoc}
   */
  public function getRecurringFee(): ?float {
    return $this->decorated->getRecurringFee();
  }

  /**
   * Gets the recurring fee values as used by a field item.
   *
   * @return array
   *   The recurring fee price value.
   */
  public function getRecurringFeePriceValue() {
    return [
      'amount' => $this->getRecurringFee(),
      'currency_code' => $this->getCurrency()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setRecurringFee(float $recurringFee): void {
    $this->decorated->setRecurringFee($recurringFee);
  }

}
