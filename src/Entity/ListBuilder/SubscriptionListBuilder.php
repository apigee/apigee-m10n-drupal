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

namespace Drupal\apigee_m10n\Entity\ListBuilder;

use Drupal\apigee_m10n\Entity\SubscriptionInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines implementation of a subscriptions listing page.
 *
 * @ingroup entity_api
 */
abstract class SubscriptionListBuilder extends EntityListBuilder implements ContainerInjectionInterface {

  /**
   * Subscription storage.
   *
   * @var \Drupal\apigee_m10n\Entity\Storage\SubscriptionStorageInterface
   */
  protected $storage;

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * SubscriptionListBuilderForDeveloper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Entity storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, MessengerInterface $messenger) {
    parent::__construct($entity_type, $storage);

    $this->storage = $storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type,
      $entity_type_manager->getStorage($entity_type->id()),
      $entity_type_manager,
      $container->get('logger.channel.apigee_m10n'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entityType = $container->get('entity_type.manager')->getDefinition('subscription');
    return static::createInstance($container, $entityType);
  }

  /**
   * {@inheritdoc}
   */
  public function getPageTitle(RouteMatchInterface $route_match): string {
    // TODO: make sure this string is configurable.
    return $this->t('Purchased plans');
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('@subscriptions', [
      '@subscriptions' => (string) $this->entityTypeManager->getDefinition('subscription')->getPluralLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'plan' => [
        'data' => $this->t('Plan Name'),
        'class' => ['rate-plan-name'],
        'field' => 'plan',
      ],
      'package' => [
        'data'  => $this->t('Package'),
        'field' => 'package',
        'class' => ['package-name'],
        'sort'  => 'desc',
      ],
      'products' => [
        'data' => $this->t('Products'),
        'class' => ['products'],
        'field' => 'products',
      ],
      'start_date' => [
        'data' => $this->t('Start Date'),
        'class' => ['subscription-start-date'],
        'field' => 'start_date',
      ],
      'end_date' => [
        'data' => $this->t('End Date'),
        'class' => ['subscription-end-date'],
        'field' => 'end_date',
      ],
      'plan_end_date' => [
        'data' => $this->t('Plan End Date'),
        'class' => ['rate-plan-end-date'],
        'field' => 'plan_end_date',
      ],
      'renewal_date' => [
        'data' => $this->t('Renewal Date'),
        'class' => ['subscription-renewal-date'],
        'field' => 'renewal_date',
      ],
      'status' => [
        'data' => $this->t('Status'),
        'field' => 'status',
        'class' => ['field-status'],
      ],
      'operations' => [
        'data' => $this->t('Actions'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\apigee_m10n\Entity\SubscriptionInterface $entity */
    $rate_plan = $entity->getRatePlan();

    // Concatenate all of the product names.
    $products = implode(', ', array_map(function ($product) {
      return $product->getDisplayName();
    }, $rate_plan->getPackage()->getApiProducts()));

    $date_display_settings = [
      'label' => 'hidden',
      'settings' => [
        'date_format' => 'custom',
        'custom_date_format' => 'm/d/Y',
      ],
    ];

    return [
      'data' => [
        'plan' => [
          'data' => Link::fromTextAndUrl($rate_plan->getDisplayName(), $this->ratePlanUrl($entity)),
          'class' => ['rate-plan-name'],
        ],
        'package' => [
          'data' => $rate_plan->getPackage()->getDisplayName(),
          'class' => ['package-name'],
        ],
        'products' => [
          'data' => $products,
          'class' => ['products'],
        ],
        'start_date' => [
          'data' => $entity->get('startDate')->view($date_display_settings),
          'class' => ['subscription-start-date'],
        ],
        'end_date' => [
          'data' => $entity->getEndDate() ? $entity->get('endDate')->view($date_display_settings) : NULL,
          'class' => ['subscription-end-date'],
        ],
        'plan_end_date' => [
          'data' => $rate_plan->getEndDate() ? $entity->get('endDate')->view($date_display_settings) : NULL,
          'class' => ['rate-plan-end-date'],
        ],
        'renewal_date' => [
          'data' => $entity->getRenewalDate() ? $entity->get('renewalDate')->view($date_display_settings) : NULL,
          'class' => ['subscription-renewal-date'],
        ],
        'status' => [
          'data' => $this->t('@status', ['@status' => $entity->getSubscriptionStatus()]),
          'class' => ['field-status'],
        ],
        'operations'    => ['data' => $this->buildOperations($entity)],
      ],
      'class' => ['subscription-row', Html::cleanCssIdentifier(strtolower($rate_plan->getDisplayName()))],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    array_push($build['table']['#cache']['contexts'], 'url.query_args');
    array_push($build['table']['#cache']['tags'], 'apigee_my_subscriptions');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $rate_plan = $entity->getRatePlan();
    $org_timezone = $rate_plan->getOrganization()->getTimezone();
    $today = new \DateTime('today', $org_timezone);

    if ($entity->access('update')
      && $entity->isSubscriptionActive()
      && ($today < $entity->getEndDate() || (!empty($rate_plan->getEndDate() && $today < $rate_plan->getEndDate())))
    ) {
      $operations['unsubscribe'] = [
        'title' => $this->t('Cancel'),
        'weight' => 10,
        'url' => $this->unsubscribeUrl($entity),
      ];
    }

    return $operations;
  }

  /**
   * Gets an unsubscribe url for the given entity.
   *
   * We could just use `$entity->toUrl('unsubscribe')` but then we would have to
   * look up the drupal user ID in `toUrl()`.
   *
   * @param \Drupal\apigee_m10n\Entity\SubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @return \Drupal\Core\Url
   *   The unsubscribe url.
   */
  abstract protected function unsubscribeUrl(SubscriptionInterface $subscription);

  /**
   * Gets the rate plan URL for the subscribed rate plan.
   *
   * @param \Drupal\apigee_m10n\Entity\SubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @return \Drupal\Core\Url
   *   The rate plan canonical url.
   */
  abstract protected function ratePlanUrl(SubscriptionInterface $subscription);

}
