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

namespace Drupal\apigee_m10n\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityManager;
use Drupal\apigee_m10n\ApigeeSdkControllerFactory;

/**
 * Plugin implementation of the 'apigee_subscription_form' formatter.
 *
 * @FieldFormatter(
 *   id = "apigee_subscribe_form",
 *   label = @Translation("Rendered form"),
 *   field_types = {
 *     "apigee_subscribe"
 *   }
 * )
 */
class SubscribeFormFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * SDK Controller factory.
   *
   * @var \Drupal\apigee_m10n\ApigeeSdkControllerFactory
   */
  protected $sdkControllerFactory;

  /**
   * SubscribeLinkFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityFormBuilder $entityFormBuilder
   *   Entity form builder service.
   * @param \Drupal\Core\Entity\EntityManager $entityManager
   *   Entity manager service.
   * @param \Drupal\apigee_m10n\ApigeeSdkControllerFactory $sdkControllerFactory
   *   SDK Controller factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityFormBuilder $entityFormBuilder, EntityManager $entityManager, ApigeeSdkControllerFactory $sdkControllerFactory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityFormBuilder = $entityFormBuilder;
    $this->entityManager = $entityManager;
    $this->sdkControllerFactory = $sdkControllerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.form_builder'),
      $container->get('entity.manager'),
      $container->get('apigee_m10n.sdk_controller_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label' => 'Purchase Plan',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#default_value' => $this->getSetting('label'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }
    return $elements;
  }

  /**
   * Renderable entity form.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   Field item variable.
   *
   * @return array
   *   Renderable form elements.
   */
  protected function viewValue(FieldItemInterface $item) {
    if ($form_state_storage = $item->getValue()) {
      $form_state_storage['button_label'] = $this->t($this->getSetting('label'));
      $rate_plan = $this->entityManager->getStorage('rate_plan')->create();
      return $this->entityFormBuilder->getForm($rate_plan, 'subscribe', $form_state_storage);
    }
  }

}
