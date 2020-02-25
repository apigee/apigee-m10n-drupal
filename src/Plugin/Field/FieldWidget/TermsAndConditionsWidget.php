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

namespace Drupal\apigee_m10n\Plugin\Field\FieldWidget;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'apigee_tnc_widget' widget.
 *
 * @FieldWidget(
 *   id = "apigee_tnc_widget",
 *   label = @Translation("Apigee Terms and Conditions"),
 *   field_types = {
 *     "apigee_tnc"
 *   }
 * )
 */
class TermsAndConditionsWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The purchased_plan.
   *
   * @var \Drupal\apigee_m10n\Entity\PurchasedPlanInterface
   */
  protected $entity;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Monetization factory.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * TermsAndConditionsWidget constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   Monetization factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, LoggerInterface $logger, MonetizationInterface $monetization = NULL) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->monetization = $monetization;
    $this->logger = $logger;
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
      $configuration['third_party_settings'],
      $container->get('logger.factory')->get('apigee_m10n'),
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_description' => 'Accept Terms and Conditions',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['default_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Use this label when Terms and Conditions returns an empty description.'),
      '#default_value' => $this->getSetting('default_description'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $this->entity = $items->getEntity();
    // We won't ask a user to accept terms and conditions again if it has been already accepted.
    if (!$items[$delta]->value && ($tnc = $this->monetization->getLatestTermsAndConditions())) {
      $element += [
        '#type' => 'checkbox',
        '#default_value' => !empty($items[0]->value),
        '#element_validate' => [[$this, 'validate']],
      ];

      // Validate url.
      try {
        $url = Url::fromUri($tnc->getUrl())->toString();
      }
      catch (\InvalidArgumentException $exception) {
        $this->logger->error($exception->getMessage());

        // Fallback to user supplied url.
        $url = $tnc->getUrl();
      }

      // Accept TnC description.
      $element['#description'] = $this->t('%description <a href=":url">Terms and Conditions</a>', [
        '%description' => ($description = $tnc->getDescription()) ? $this->t($description) : $this->getSetting('default_description'),
        ':url' => $url,
      ]);

      $element['#attached']['library'][] = 'apigee_m10n/tnc-widget';
      return ['value' => $element];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    // We only apply checking when terms and conditions checkbox is present in the form.
    if (empty($value)) {
      $form_state->setError($element, $this->t('Terms and conditions acceptance is required.'));
    }
    else {
      // Accept terms and conditions.
      $this->monetization->acceptLatestTermsAndConditions($this->entity->getDeveloper()->getEmail());
    }
  }

}
