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

use Drupal\apigee_m10n\Entity\PurchasedPlanInterface;
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
        'data' => $this->t('Rate plan'),
        'class' => ['subscription-rate-plan'],
        'field' => 'plan',
      ],
      'status' => [
        'data' => $this->t('Status'),
        'field' => 'status',
        'class' => ['subscription-status'],
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
      'operations' => [
        'data' => $this->t('Actions'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $entity */
    $rate_plan = $entity->getRatePlan();

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
          'class' => ['subscription-rate-plan'],
        ],
        'status' => [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '<span class="status status--{{ status|clean_class }}">{{ status }}</span>',
            '#context' => ['status' => $this->t('@status', ['@status' => $entity->getSubscriptionStatus()])],
          ],
          'class' => ['subscription-status'],
        ],
        'start_date' => [
          'data' => $entity->get('startDate')->view($date_display_settings),
          'class' => ['subscription-start-date'],
        ],
        'end_date' => [
          'data' => $entity->getEndDate() ? $entity->get('endDate')->view($date_display_settings) : NULL,
          'class' => ['subscription-end-date'],
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
    $build = [];
    $label = $this->entityType->getPluralLabel();

    // Group the subscriptions by status.
    $subscriptions = [
      PurchasedPlanInterface::STATUS_ACTIVE => [
        'title' => $this->t('Active and Future @label', ['@label' => $label]),
        'empty' => $this->t('No active or future @label.', ['@label' => strtolower($label)]),
        'entities' => [],
      ],
      PurchasedPlanInterface::STATUS_ENDED => [
        'title' => $this->t('Cancelled and Expired @label', ['@label' => $label]),
        'empty' => $this->t('No cancelled or expired @label.', ['@label' => strtolower($label)]),
        'entities' => [],
      ],
    ];

    /** @var \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $entity */
    foreach ($this->load() as $entity) {
      if (in_array($entity->getSubscriptionStatus(), [PurchasedPlanInterface::STATUS_ACTIVE, PurchasedPlanInterface::STATUS_FUTURE])) {
        $subscriptions[PurchasedPlanInterface::STATUS_ACTIVE]['entities'][] = $entity;
        continue;
      }

      $subscriptions[PurchasedPlanInterface::STATUS_ENDED]['entities'][] = $entity;
    }

    foreach ($subscriptions as $key => $subscription) {
      $build[$key]['heading'] = [
        '#type' => 'html_tag',
        '#value' => $subscription['title'],
        '#tag' => 'h3',
      ];

      $build[$key]['table'] = [
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#title' => $subscription['title'],
        '#rows' => [],
        '#empty' => $subscription['empty'],
        '#cache' => [
          'contexts' => $this->entityType->getListCacheContexts(),
          'tags' => $this->entityType->getListCacheTags(),
        ],
      ];

      if (count($subscription['entities'])) {
        foreach ($subscription['entities'] as $entity) {
          if ($row = $this->buildRow($entity)) {
            $build[$key]['table']['#rows'][$entity->id()] = $row;
          }
        }
      }

      array_push($build[$key]['table']['#cache']['contexts'], 'url.query_args');
      array_push($build[$key]['table']['#cache']['tags'], 'apigee_my_subscriptions');
    }

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
   * @param \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $subscription
   *   The subscription entity.
   *
   * @return \Drupal\Core\Url
   *   The unsubscribe url.
   */
  abstract protected function unsubscribeUrl(PurchasedPlanInterface $subscription);

  /**
   * Gets the rate plan URL for the subscribed rate plan.
   *
   * @param \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $subscription
   *   The subscription entity.
   *
   * @return \Drupal\Core\Url
   *   The rate plan canonical url.
   */
  abstract protected function ratePlanUrl(PurchasedPlanInterface $subscription);

}
