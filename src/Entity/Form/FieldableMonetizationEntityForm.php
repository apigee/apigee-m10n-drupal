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

use Drupal\apigee_edge\Entity\Form\FieldableEdgeEntityForm;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Entity form variant for Apigee Monetization entity types.
 *
 * @deprecated in 1.7 and is removed from 2.x. Use
 * \Drupal\apigee_edge\Entity\Form\FieldableEdgeEntityForm instead.
 *
 * @see https://github.com/apigee/apigee-m10n-drupal/issues/236
 */
class FieldableMonetizationEntityForm extends FieldableEdgeEntityForm implements FieldableMonetizationEntityFormInterface {

  /**
   * {@inheritdoc}
   *
   * Button-level validation handlers are highly discouraged for entity forms,
   * as they will prevent entity validation from running. If the entity is going
   * to be saved during the form submission, this method should be manually
   * invoked from the button-level validation handler, otherwise an exception
   * will be thrown.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->buildEntity($form, $form_state);

    $violations = $entity->validate();

    // Remove violations of inaccessible fields.
    $violations->filterByFieldAccess($this->currentUser());

    // In case a field-level submit button is clicked, for example the 'Add
    // another item' button for multi-value fields or the 'Upload' button for a
    // File or an Image field, make sure that we only keep violations for that
    // specific field.
    $edited_fields = [];
    if ($limit_validation_errors = $form_state->getLimitValidationErrors()) {
      foreach ($limit_validation_errors as $section) {
        $field_name = reset($section);
        if ($entity->hasField($field_name)) {
          $edited_fields[] = $field_name;
        }
      }
      $edited_fields = array_unique($edited_fields);
    }
    else {
      $edited_fields = $this->getEditedFieldNames($form_state);
    }

    // Remove violations for fields that are not edited.
    $violations->filterByFields(array_diff(array_keys($entity->getFieldDefinitions()), $edited_fields));

    $this->flagViolations($violations, $form, $form_state);

    // The entity was validated.
    $entity->setValidationRequired(FALSE);
    $form_state->setTemporaryValue('entity_validated', TRUE);

    return $entity;
  }

  /**
   * Gets the names of all fields edited in the form.
   *
   * If the entity form customly adds some fields to the form (i.e. without
   * using the form display), it needs to add its fields here and override
   * flagViolations() for displaying the violations.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string[]
   *   An array of field names.
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_keys($this->getFormDisplay($form_state)->getComponents());
  }

  /**
   * Flags violations for the current form.
   *
   * If the entity form customly adds some fields to the form (i.e. without
   * using the form display), it needs to add its fields to array returned by
   * getEditedFieldNames() and overwrite this method in order to show any
   * violations for those fields; e.g.:
   * @code
   * foreach ($violations->getByField('name') as $violation) {
   *   $form_state->setErrorByName('name', $violation->getMessage());
   * }
   * parent::flagViolations($violations, $form, $form_state);
   * @endcode
   *
   * @param \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations
   *   The violations to flag.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Flag entity level violations.
    foreach ($violations->getEntityViolations() as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $form_state->setErrorByName(str_replace('.', '][', $violation->getPropertyPath()), $violation->getMessage());
    }
    // Let the form display flag violations of its fields.
    $this->getFormDisplay($form_state)->flagWidgetsErrorsFromViolations($violations, $form, $form_state);
  }

}
