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

use Drupal\apigee_m10n\ApigeeSdkControllerFactory;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Unsubscribe entity form for subscriptions.
 */
class RatePlanSubscribeForm extends EntityForm {

  /** @var \Drupal\user\Entity\User|null */
  protected $developer;

  /** @var \Drupal\apigee_m10n\Entity\RatePlan|null */
  protected $rate_plan;

  /** @var \Drupal\Core\Messenger\MessengerInterface */
  protected $messenger;

  /** @var Drupal\apigee_m10n\ApigeeSdkControllerFactory */
  protected $sdkControllerFactory;

  /**
   * RatePlanSubscribeForm constructor.
   *
   * @param RouteMatchInterface $route_match
   * @param MessengerInterface $messenger
   * @param ApigeeSdkControllerFactory $sdkControllerFactory
   */
  public function __construct(RouteMatchInterface $route_match, MessengerInterface $messenger, ApigeeSdkControllerFactory $sdkControllerFactory) {
    $this->developer = $route_match->getParameter('user');
    $this->rate_plan = $route_match->getParameter('rate_plan');
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
   * Provides a generic title callback for a single entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string|null
   *   The title for the entity view page, if an entity was found.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function title(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    return $this->t("Subscribe to %label", ['%label' => $this->rate_plan->getDisplayName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['start_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plan End Date'),
      '#options' => [
        'immediate' => $this->t('Immediately'),
        'on_date'   => $this->t('Future Date')
      ],
      '#default_value' => 'immediate'
    ];

    $form['startDate'] = [
      '#type'  => 'date',
      '#title' => $this->t('Start Date'),
      '#states' => [
        'visible' => [
          ':input[name="start_type"]' => ['value' => 'on_date'],
        ]
      ],
    ];

    $form['actions']['submit']['#value'] = $this->t('Purchase This Plan');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $entity = Subscription::create([
      'developer' => $this->sdkControllerFactory->developerController()->load($this->developer->getEmail()),
      'startDate' => $values['start_type'] == 'on_date'
        ? new \DateTimeImmutable($values['startDate'])
        : new \DateTimeImmutable('now'),
      'ratePlan' => $this->rate_plan->decorated(),
    ]);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $display_name = $this->rate_plan->getDisplayName();
      if ($this->entity->save()) {
        $this->messenger->addStatus($this->t('You have successfully subscribed to <em>%label</em> plan', [
          '%label' => $display_name,
        ]));
      }
      else {
        $this->messenger->addWarning($this->t('Unable subscribe to <em>%label</em> plan', [
          '%label' => $display_name,
        ]));
      }
      Cache::invalidateTags(['apigee_my_subscriptions']);
      $form_state->setRedirect('apigee_monetization.my_subscriptions');
    }
    catch (\Exception $e) {
      $this->messenger->addError($e->getMessage());
    }
  }

}
