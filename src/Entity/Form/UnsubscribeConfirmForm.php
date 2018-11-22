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
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

/**
 * Unsubscribe entity form for subscriptions.
 */
class UnsubscribeConfirmForm extends EntityConfirmFormBase {

  /** @var \Drupal\Core\Entity\EntityInterface|User|null */
  protected $developer;

  /** @var \Drupal\apigee_m10n\Entity\Subscription|null */
  protected $subscription;

  /** @var \Drupal\Core\Messenger\MessengerInterface */
  protected $messenger;

  /**
   * UnsubscribeConfirmForm constructor.
   *
   * @param RouteMatchInterface $route_match
   * @param MessengerInterface $messenger
   */
  public function __construct(RouteMatchInterface $route_match, MessengerInterface $messenger) {
    $this->developer = User::load($route_match->getParameter('user'));
    $this->subscription = $route_match->getParameter('subscription');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you would like to unsubscribe from this plan?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('End This Plan');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Unsubscribe from <em>%label</em> plan', [
      '%label' => $this->subscription->getRatePlan()->label()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return  Url::fromRoute('apigee_monetization.my_subscriptions');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['when'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plan End Date'),
      '#options' => [
        'immediate' => $this->t('Immediately'),
        'on_date'   => $this->t('Future Date')
      ],
      '#default_value' => 'immediate'
    ];
    $form['endDate'] = [
      '#type'  => 'date',
      '#title' => $this->t('Select an end date'),
      '#states' => [
        'visible' => [
          ':input[name="when"]' => ['value' => 'on_date'],
        ]
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $end_type = $values['end_type'];
    $entity = $this->subscription;
    $entity->setDeveloperEmail($this->developer->getEmail());
    if ($end_type == 'end_date') {
      $entity->setEndDate(new \DateTimeImmutable($values['endDate']));
    }
    else {
      $entity->setEndDate(new \DateTimeImmutable('-1 day'));
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $plan_name = $this->entity->getRatePlan()->label();
      if (!$this->entity->isSubscriptionActive()) {
        if ($this->entity->save()) {
          $this->messenger->addStatus($this->t('You have successfully unsubscribed from <em>%label</em> plan', [
            '%label' => $plan_name
          ]));
        }
      }
      else {
        $this->messenger->addWarning($this->t('You are already unsubscribed from <em>%label</em> plan', [
          '%label' => $plan_name
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
