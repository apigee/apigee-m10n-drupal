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

namespace Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates the default unit price.
 *
 * @Constraint(
 *   id = "PriceRangeUnitPrice",
 *   label = @Translation("The unit price is out of range.", context = "Validation"),
 * )
 */
class PriceRangeUnitPriceConstraint extends Constraint {

  public $rangeMessage = 'The unit price value must be between @minimum and @maximum.';

  public $minMessage = 'This unit price cannot be less than @minimum.';

  public $maxMessage = 'This unit price cannot be greater than @maximum.';

  public $currencyMessage = 'The selected currency is invalid.';

}
