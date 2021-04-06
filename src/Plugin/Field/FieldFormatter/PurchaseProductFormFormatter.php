<?php

/*
 * Copyright 2021 Google Inc.
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

use Apigee\Edge\Api\Monetization\Entity\Developer;
use Drupal\apigee_m10n\Entity\PurchasedProduct;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\apigee_m10n\Monetization;

/**
 * Plugin implementation of the 'apigee_purchase_product_form' formatter.
 *
 * @FieldFormatter(
 *   id = "apigee_purchase_product_form",
 *   label = @Translation("Rendered form"),
 *   field_types = {
 *     "apigee_purchase_product"
 *   }
 * )
 */
class PurchaseProductFormFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The Cache backend.
   *
   * @var \Drupal\apigee_m10n\Monetization
   */
  private $monetization;

  /**
   * PurchasePlanFormFormatter constructor.
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
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   Entity form builder service.
   * @param \Drupal\apigee_m10n\Monetization $monetization
   *   Monetization service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityFormBuilderInterface $entityFormBuilder, Monetization $monetization) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityFormBuilder = $entityFormBuilder;
    $this->monetization = $monetization;
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
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label' => 'Purchase product',
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
   *
   * @throws \Exception
   */
  protected function viewValue(FieldItemInterface $item) {
    /** @var \Drupal\apigee_m10n\Entity\XRatePlanInterface $rate_plan */

    $rate_plan = $item->getEntity();
    if (($value = $item->getValue()) && $item->getEntity()->access('purchase')) {
      $developer_id = $value['user']->getEmail();
      $purchased_product = PurchasedProduct::create([
        'xratePlan' => $rate_plan,
        // TODO: User a controller proxy that caches the developer entity.
        // @see: https://github.com/apigee/apigee-edge-drupal/pull/97.
        'developer' => new Developer(['email' => $developer_id]),
      ]);
      return $this->entityFormBuilder->getForm($purchased_product, 'default', [
        'save_label' => $this->t('@save_label', ['@save_label' => $this->getSetting('label')]),
      ]);
    }
  }

}
