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
 * Validates the default value range.
 *
 * @Constraint(
 *   id = "PriceRangeDefaultOutOfRange",
 *   label = @Translation("The default value is out of range.", context = "Validation"),
 * )
 */
class PriceRangeDefaultOutOfRangeConstraint extends Constraint {

  /**
   * The validation message when value is not in right format.
   *
   * @var string
   */
  public $formatMessage = '@field must be a number.';

  /**
   * The validation message when out of range.
   *
   * @var string
   */
  public $rangeMessage = 'The default value must be between the minimum and the maximum price.';

  /**
   * The validation message when value is less than minimum price.
   *
   * @var string
   */
  public $minMessage = 'This default value cannot be less than the minimum price.';

  /**
   * The validation message when value is greater than maximum price.
   *
   * @var string
   */
  public $maxMessage = 'This default value cannot be greater than the maximum price.';

  /**
   * The validation message when currency is invalid.
   *
   * @var string
   */
  public $currencyMessage = 'The selected currency is invalid.';

}
