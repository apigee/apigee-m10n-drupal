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

namespace Drupal\apigee_m10n_add_credit;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Session\AccountInterface;

/**
 * Helper service to handle basic module tasks.
 *
 * @package Drupal\apigee_m10n_add_credit
 */
class AddCreditService implements AddCreditServiceInterface {

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $current_user;

  /**
   * The system theme config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructor for the `apigee_m10n.add_credit` service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $user) {
    $this->config = $config_factory;
    $this->current_user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function mail($key, &$message, $params) {
    $params['@site'] = $this->config->get('system.site')->get('name');
    switch ($key) {
      case 'balance_adjustment_report':
        $options = ['langcode' => $message['langcode']];
        $message['subject'] = t('Add Credit successfully applied to account (@email@team_name) from @site', $params, $options);
        $message['body'][0] = t($params['report_text'], $params, $options);
        break;

      case 'balance_adjustment_error_report':
        $options = ['langcode' => $message['langcode']];
        $params['@site'] = $this->config->get('system.site')->get('name');
        $message['subject'] = t('Developer account add credit error from @site', $params, $options);
        $body = "There was an error applying a credit to an account. \n\r\n\r" . $params['report_text'] . "\n\r\n\r@error";
        $message['body'][0] = t($body, $params, $options);
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function commerceOrderItemCreate(EntityInterface $entity) {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $entity */
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variant */
    // Check to see if an "Add credit" product is what's being added.
    if ($entity instanceof OrderItemInterface
      && ($variant = $entity->getPurchasedEntity())
      && ($product = $variant->getProduct())
      && !empty($product->apigee_add_credit_enabled->value)
    ) {
      // Save the current user as the top up recipient. We might need to change
      // how this works when topping up a company or a non-current user.
      $entity->setData('add_credit_account', $this->current_user->getEmail());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    if ($entity_type->id() === 'commerce_product') {
      // The base field needs to be added to all product types for the storage
      // to be allocated but the option to enable will be hidden and unused
      // unless enabled for that bundle.
      $fields['apigee_add_credit_enabled'] = BaseFieldDefinition::create('boolean')
        ->setLabel(t('This is an Apigee add credit product'))
        ->setRevisionable(TRUE)
        ->setTranslatable(TRUE)
        ->setDefaultValue(FALSE);

      return $fields;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    // Make sure we are dealing with a product bundle that has Apigee add credit
    // enabled.
    if ($entity_type->id() === 'commerce_product'
      && ($product_type = ProductType::load($bundle))
      && $product_type->getThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit')
    ) {
      // Apigee add credit enabled products will automatically update a
      // developer's balance upon payment completion. This adds a base field to
      // the bundle to allow add credit to be enabled for products of the bundle
      // individually.
      $add_credit_base_def = clone $base_field_definitions['apigee_add_credit_enabled'];
      $add_credit_base_def
        ->setDefaultValue(TRUE)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', ['weight' => 25])
        ->setDisplayConfigurable('view', TRUE);
      return ['apigee_add_credit_enabled' => $add_credit_base_def];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formCommerceProductTypeEditFormAlter(&$form, FormStateInterface $form_state, $form_id) {

    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $default_value = (($product_type = $form_state->getFormObject()->getEntity())
      && $product_type->getThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit')
    ) ? TRUE : FALSE;

    // Add an option to allow enabling Apigee add credit for a product type.
    $form['apigee_m10n_enable_add_credit'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Apigee Monetization Add Credit</em> for this product type.'),
      '#default_value' => $default_value,
    ];
    // Add our own callback so we can save the add_credit enabled setting.
    array_splice($form["actions"]["submit"]["#submit"], -1, 0, [[static::class, 'formCommerceProductTypeSubmit']]);
  }

  /**
   * Submit callback for `::formCommerceProductTypeEditFormAlter()`.
   *
   * Add a third party setting to the product type to flag whether or not this
   * product type is should be used as an apigee to up product.
   *
   * @param array|null $form
   *   The add or edit form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function formCommerceProductTypeSubmit(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    if (($product_type = $form_state->getFormObject()->getEntity())) {
      // Save the enabled setting to third party settings.
      $product_type->setThirdPartySetting(
        'apigee_m10n_add_credit',
        'apigee_m10n_enable_add_credit',
        $form_state->getValue('apigee_m10n_enable_add_credit')
      );
    }
  }

}
