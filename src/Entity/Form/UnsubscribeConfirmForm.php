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

use Drupal\apigee_m10n\Form\SubscriptionConfigForm;
use Drupal\apigee_m10n\Entity\SubscriptionInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\apigee_m10n\ApigeeSdkControllerFactory;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

/**
 * Unsubscribe entity form for subscriptions.
 */
class UnsubscribeConfirmForm extends EntityConfirmFormBase {

  /**
   * Subscription entity.
   *
   * @var \Drupal\apigee_m10n\Entity\Subscription|null
   */
  protected $subscription;

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
   * UnsubscribeConfirmForm constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\apigee_m10n\ApigeeSdkControllerFactory $sdkControllerFactory
   *   SDK Controller factory.
   */
  public function __construct(RouteMatchInterface $route_match, MessengerInterface $messenger, ApigeeSdkControllerFactory $sdkControllerFactory) {
    $this->subscription = $route_match->getParameter('subscription');
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
    // Get the unsubscribe text from config. Use `config_translate` to
    // translate if necessary.
    $description = $this->config(SubscriptionConfigForm::CONFIG_NAME)->get('unsubscribe_description');
    return $description ?? $this->t('Are you sure you would to like cancel this plan?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $button_label = $this->config(SubscriptionConfigForm::CONFIG_NAME)->get('unsubscribe_button_label');
    return $button_label ?? $this->t('End This Plan');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $question = $this->config(SubscriptionConfigForm::CONFIG_NAME)->get('unsubscribe_question');
    return $this->t($question ?? 'Cancel %rate_plan', [
      '%rate_plan' => $this->subscription->getRatePlan()->getDisplayName(),
      '@rate_plan' => $this->subscription->getRatePlan()->getDisplayName(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('apigee_monetization.my_subscriptions');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['end_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plan End Date'),
      '#options' => [
        'now' => $this->t('Now'),
        'on_date' => $this->t('Future Date'),
      ],
      '#default_value' => 'now',
    ];
    $form['endDate'] = [
      '#type' => 'date',
      '#title' => $this->t('Select End Date'),
      '#states' => [
        'visible' => [
          ':input[name="end_type"]' => ['value' => 'on_date'],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $end_type = $values['end_type'] ?? 'now';
    $end_date = $end_type == 'on_date' ? new \DateTimeImmutable($values['endDate']) : $this->subscription->getStartDate();
    $end_date->setTimezone($this->subscription->getRatePlan()->getOrganization()->getTimezone());
    $this->subscription->setEndDate($end_date);
    return $this->subscription;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      if ($this->entity->save()) {
        $this->messenger->addStatus($this->t('You have successfully cancelled %label plan', [
          '%label' => $this->entity->getRatePlan()->getDisplayName(),
        ]));
        Cache::invalidateTags(['apigee_my_subscriptions']);
        $form_state->setRedirect('apigee_monetization.my_subscriptions');
      }
    }
    // TODO: Check to see if `EntityStorageException` is the only type of error
    // to we need to catch here.
    catch (\Exception $e) {
      $this->messenger->addError('Error while cancelling plan: ' . $e->getMessage());
    }
  }

}
