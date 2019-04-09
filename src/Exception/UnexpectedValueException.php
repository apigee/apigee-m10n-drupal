<?php

/*
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_m10n\Exception;

use Drupal\apigee_edge\Exception\ApigeeEdgeExceptionInterface;

/**
 * Unexpected value encountered.
 */
class UnexpectedValueException extends \UnexpectedValueException implements ApigeeEdgeExceptionInterface {

  /**
   * UnexpectedValueException constructor.
   *
   * @param object $object
   *   The object that has the unexpected value.
   * @param string $property
   *   The objects property name.
   * @param string $expected
   *   The expected value.
   * @param string $actual
   *   The actual value.
   */
  public function __construct($object, string $property, string $expected, string $actual) {
    parent::__construct(
      sprintf(
        'Invalid value returned for %s property on instance of %s class. Expected value was "%s", got "%s".',
        $property,
        get_class($object),
        $expected,
        $actual
      )
    );
  }

}
