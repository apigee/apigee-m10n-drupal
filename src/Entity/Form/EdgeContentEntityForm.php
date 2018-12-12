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

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity form variant for Apigee Edge entity types.
 *
 * The content `ContentEntityForm` makes the assumption that entities are
 * translatable and revisionable. This entity base form provides most of the
 * same helpers for Apigee Edge entities.
 *
 * @see \Drupal\Core\Entity\ContentEntityForm
 */
class EdgeContentEntityForm extends EntityForm implements EdgeContentEntityFormInterface {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\apigee_edge\Entity\EdgeEntityInterface
   */
  protected $entity;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a EdgeContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    if ($entity_repository instanceof EntityManagerInterface) {
      @trigger_error('Passing the entity.manager service to ContentEntityForm::__construct() is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Pass the entity.repository service instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $this->entityManager = $entity_repository;
    }
    $this->entityRepository = $entity_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info ?: \Drupal::service('entity_type.bundle.info');
    $this->time = $time ?: \Drupal::service('datetime.time');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * Returns the bundle entity of the entity, or NULL if there is none.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The bundle entity.
   */
  protected function getBundleEntity() {
    if ($bundle_entity_type = $this->entity->getEntityType()->getBundleEntityType()) {
      return $this->entityTypeManager->getStorage($bundle_entity_type)->load($this->entity->bundle());
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    // Content entity forms do not use the parent's #after_build callback
    // because they only need to rebuild the entity in the validation and the
    // submit handler because Field API uses its own #after_build callback for
    // its widgets.
    unset($form['#after_build']);

    $this->getFormDisplay($form_state)->buildForm($this->entity, $form, $form_state);

    $form['footer'] = [
      '#type' => 'container',
      '#weight' => 99,
      '#attributes' => [
        'class' => ['entity-content-form-footer'],
      ],
      '#optional' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Update the changed timestamp of the entity.
    $this->updateChangedTime($this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    // Mark the entity as requiring validation.
    $entity->setValidationRequired(!$form_state->getTemporaryValue('entity_validated'));

    return $entity;
  }

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

  /**
   * Initializes the form state and the entity before the first form build.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function init(FormStateInterface $form_state) {
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, $this->getOperation());
    $this->setFormDisplay($form_display, $form_state);

    parent::init($form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // First, extract values from widgets.
    $extracted = $this->getFormDisplay($form_state)->extractFormValues($entity, $form, $form_state);

    // Then extract the values of fields that are not rendered through widgets,
    // by simply copying from top-level form values. This leaves the fields
    // that are not being edited within this form untouched.
    foreach ($form_state->getValues() as $name => $values) {
      if ($entity->hasField($name) && !isset($extracted[$name])) {
        $entity->set($name, $values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay(FormStateInterface $form_state) {
    return $form_state->get('form_display');
  }

  /**
   * {@inheritdoc}
   */
  public function setFormDisplay(EntityFormDisplayInterface $form_display, FormStateInterface $form_state) {
    $form_state->set('form_display', $form_display);
    return $this;
  }

  /**
   * Updates the changed time of the entity.
   *
   * Applies only if the entity implements the EntityChangedInterface.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity updated with the submitted values.
   */
  public function updateChangedTime(EntityInterface $entity) {
    if ($entity instanceof EntityChangedInterface) {
      $entity->setChangedTime($this->time->getRequestTime());
    }
  }

}
