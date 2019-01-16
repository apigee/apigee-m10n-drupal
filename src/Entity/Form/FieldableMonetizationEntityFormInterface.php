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

namespace Drupal\apigee_m10n\Entity\Form;

use Drupal\apigee_edge\Entity\Form\FieldableEdgeEntityFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Entity form variant for Apigee Monetization entity types.
 */
interface FieldableMonetizationEntityFormInterface extends FieldableEdgeEntityFormInterface {

  /**
   * {@inheritdoc}
   *
   * Note that extending classes should not override this method to add entity
   * validation logic, but define further validation constraints using the
   * entity validation API and/or provide a new validation constraint if
   * necessary. This is the only way to ensure that the validation logic
   * is correctly applied independently of form submissions; e.g., for REST
   * requests.
   * For more information about entity validation, see
   * https://www.drupal.org/node/2015613.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface
   *   The built entity.
   */
  public function validateForm(array &$form, FormStateInterface $form_state);

}
