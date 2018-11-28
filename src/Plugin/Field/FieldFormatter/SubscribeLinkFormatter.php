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

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'apigee_subscription_form' formatter.
 *
 * @FieldFormatter(
 *   id = "apigee_subscribe_link",
 *   label = @Translation("Subscribe link"),
 *   field_types = {
 *     "apigee_subscribe_link"
 *   }
 * )
 */
class SubscribeLinkFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /** @var Drupal\Core\Entity\EntityFormBuilder  */
  protected $entityFormBuilder;

  /** @var Drupal\Core\Entity\EntityManager */
  protected $entityManager;

  /**
   * SubscribeLinkFormatter constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param $label
   * @param $view_mode
   * @param array $third_party_settings
   * @param EntityFormBuilder $entityFormBuilder
   * @param EntityManager $entityManager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityFormBuilder $entityFormBuilder, EntityManager $entityManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityFormBuilder = $entityFormBuilder;
    $this->entityManager = $entityManager;
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
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'element_type'    => 'link',
      'subscribe_label' => 'Purchase This Plan',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['element_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Element type'),
      '#options' => [
        'form' => $this->t('Rendered form'),
        'link' => $this->t('Link')
      ],
      '#default_value' => $this->getSetting('element_type'),
    ];
    $form['subscribe_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subscribe label'),
      '#default_value' => $this->getSetting('subscribe_label'),
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
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   Renderable elements (link/form).
   */
  protected function viewValue(FieldItemInterface $item) {
    if ($this->getSetting('element_type') == 'link') {
      return Link::fromTextAndUrl($this->getSetting('subscribe_label'), $item->value)->toRenderable();
    }
    else {
      $rate_plan = $this->entityManager->getStorage('rate_plan')->create();
      return $this->entityFormBuilder->getForm($rate_plan, 'subscribe');
    }
  }

}
