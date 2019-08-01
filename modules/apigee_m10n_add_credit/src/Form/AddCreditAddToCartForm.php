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

namespace Drupal\apigee_m10n_add_credit\Form;

use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\Form\AddToCartForm;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AddCreditAddToCartForm.
 */
class AddCreditAddToCartForm extends AddToCartForm {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The add credit plugin manager.
   *
   * @var \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface
   */
  protected $addCreditPluginManager;

  /**
   * Constructs an AddToCartForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain base price resolver.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface $add_credit_plugin_manager
   *   The add credit plugin manager.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    CartManagerInterface $cart_manager,
    CartProviderInterface $cart_provider,
    OrderTypeResolverInterface $order_type_resolver,
    CurrentStoreInterface $current_store,
    ChainPriceResolverInterface $chain_price_resolver,
    AccountInterface $current_user,
    RequestStack $request,
    RouteMatchInterface $route_match,
    AddCreditEntityTypeManagerInterface $add_credit_plugin_manager
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time, $cart_manager, $cart_provider, $order_type_resolver, $current_store, $chain_price_resolver, $current_user);
    $this->request = $request;
    $this->routeMatch = $route_match;
    $this->addCreditPluginManager = $add_credit_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.current_store'),
      $container->get('commerce_price.chain_price_resolver'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.apigee_add_credit_entity_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the default value from query otherwise use the current user.
    $default_value = $this->request->getCurrentRequest()->get(AddCreditConfig::TARGET_FIELD_NAME) ?? [
      'target_type' => 'developer',
      'target_id' => $this->currentUser->getEmail(),
    ];

    // Set the entity target default value.
    // This ensures a target is set even if the target field is not visible
    // in the form display.
    $this->entity->set(AddCreditConfig::TARGET_FIELD_NAME, $default_value);

    // Build the form.
    $form = parent::buildForm($form, $form_state);

    // If the target field is visible, set a default value.
    if (isset($form[AddCreditConfig::TARGET_FIELD_NAME])) {
      $form[AddCreditConfig::TARGET_FIELD_NAME]['widget']['#default_value'] = [
        "{$default_value['target_type']}:{$default_value['target_id']}",
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $action = parent::actions($form, $form_state);

    // Update the submit button if skip cart.
    if ($this->shouldSkipCart()) {
      $action['submit']['#value'] = $this->t('Checkout');
    }

    return $action;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Go to the review step is skip cart.
    if ($this->shouldSkipCart()) {
      // Ignore the destination.
      $this->request->getCurrentRequest()->query->remove('destination');

      $form_state->setRedirect('commerce_checkout.form', [
        'commerce_order' => $this->entity->getOrderId(),
        'step' => 'review',
      ]);
    }
  }

  /**
   * Helper to determine if should skip cart.
   *
   * @return bool
   *   TRUE is should skip cart.
   */
  protected function shouldSkipCart() {
    return isset($this->entity->purchased_entity->entity->product_id->entity->type->entity)
      ? $this->entity->purchased_entity->entity->product_id->entity->type->entity->getThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_skip_cart', FALSE)
      : FALSE;
  }

}
