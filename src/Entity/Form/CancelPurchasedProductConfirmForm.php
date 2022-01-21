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

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\apigee_m10n\ApigeeSdkControllerFactory;
use Drupal\Core\Cache\Cache;

/**
 * Cancel entity form for `purchased_product` entities.
 */
class CancelPurchasedProductConfirmForm extends EntityConfirmFormBase {

  /**
   * Purchased plan entity.
   *
   * @var \Drupal\apigee_m10n\Entity\PurchasedProduct|null
   */
  protected $purchasedProduct;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * SDK controller factory.
   *
   * @var \Drupal\apigee_m10n\ApigeeSdkControllerFactory
   */
  protected $sdkControllerFactory;

  /**
   * CancelPurchaseConfirmForm constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\apigee_m10n\ApigeeSdkControllerFactory $sdkControllerFactory
   *   SDK Controller factory.
   */
  public function __construct(RouteMatchInterface $route_match, MessengerInterface $messenger, ApigeeSdkControllerFactory $sdkControllerFactory) {
    $this->routeMatch = $route_match;
    $this->purchasedProduct = $route_match->getParameter('purchased_product');
    $this->messenger = $messenger;
    $this->sdkControllerFactory = $sdkControllerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('messenger'),
      $container->get('apigee_m10n.sdk_controller_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to cancel this API Product?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Cancel %rate_plan', ['%rate_plan' => $this->purchasedProduct->getApiProduct()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    return $this->purchasedProduct;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->entity->user = $this->routeMatch->getParameter('user');

    try {
      if ($this->entity->save()) {
        $this->messenger->addStatus($this->t('You have successfully cancelled %label API Product', ['%label' => $this->entity->getApiProduct()]));
        Cache::invalidateTags([PurchasedProductForm::MY_PURCHASES_PRODUCT_CACHE_TAG]);
        $form_state->setRedirect('entity.purchased_product.developer_product_collection', ['user' => $this->entity->getOwnerId()]);
      }
    }
    // TODO: Check to see if `EntityStorageException` is the only type of error
    // to we need to catch here.
    catch (\Exception $e) {

      $this->messenger->addError('Error while cancelling plan: ' . $e->getMessage());
    }
  }

}
