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

use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\PriceRangeItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the PriceRangeMinimumGreaterMaximum constraint.
 */
class PriceRangeMinimumGreaterMaximumConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!($value instanceof PriceRangeItem)) {
      throw new UnexpectedTypeException($value, PriceRangeItem::class);
    }

    $range = $value->getValue();

    // No validation if a maximum is not set.
    if (!isset($range['maximum'])) {
      return;
    }

    if (($range['minimum'] > $range['maximum'])) {
      $this->context->addViolation($constraint->message);
    }
  }

}
