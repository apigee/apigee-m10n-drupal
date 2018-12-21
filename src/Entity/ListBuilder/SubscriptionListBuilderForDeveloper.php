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

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines implementation of a subscriptions listing page.
 *
 * @ingroup entity_api
 */
class SubscriptionListBuilderForDeveloper extends EntityListBuilder implements ContainerInjectionInterface {

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
   * Messanger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The user for the listing page.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

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
   *   Messanger service.
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
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
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
    return $this->t('Purchased Plans');
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
      'status' => [
        'data' => $this->t('Status'),
        'field' => 'status',
        'class' => ['field-status'],
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
      'plan' => [
        'data' => $this->t('Plan Name'),
        'class' => ['rate-plan-name'],
        'field' => 'plan',
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

    $rate_plan_url = $this->ensureDestination(Url::fromRoute('entity.rate_plan.canonical', [
      'user' => $this->user->id(),
      'package' => $rate_plan->getPackage()->id(),
      'rate_plan' => $rate_plan->id(),
    ]));

    return [
      'data' => [
        'status' => [
          'data' => $this->t('@status', ['@status' => $entity->getSubscriptionStatus()]),
          'class' => ['field-status'],
        ],
        'package' => [
          'data' => $rate_plan->getPackage()->getDisplayName(),
          'class' => ['package-name'],
        ],
        'products' => [
          'data' => $products,
          'class' => ['products'],
        ],
        'plan' => [
          'data' => Link::fromTextAndUrl($rate_plan->getDisplayName(), $rate_plan_url),
          'class' => ['rate-plan-name'],
        ],
        'start_date' => [
          'data' => $entity->getStartDate()->format('m/d/Y'),
          'class' => ['subscription-start-date'],
        ],
        'end_date' => [
          'data' => $entity->getEndDate() ? $entity->getEndDate()->format('m/d/Y') : NULL,
          'class' => ['subscription-end-date'],
        ],
        'plan_end_date' => [
          'data' => $rate_plan->getEndDate() ? $rate_plan->getEndDate()->format('m/d/Y') : NULL,
          'class' => ['rate-plan-end-date'],
        ],
        'renewal_date' => [
          'data' => $entity->getRenewalDate() ? $entity->getRenewalDate()->format('m/d/Y') : NULL,
          'class' => ['subscription-renewal-date'],
        ],
        'operations'    => ['data' => $this->buildOperations($entity)],
      ],
      'class' => ['subscription-row', Html::cleanCssIdentifier(strtolower($rate_plan->getDisplayName()))],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    throw new EntityStorageException('Unable to load subscriptions directly. Use `SubscriptionStorage::loadPackageRatePlans`.');
  }

  /**
   * {@inheritdoc}
   */
  public function render(UserInterface $user = NULL) {
    $this->user = $user;
    $developer_id = $user->getEmail();

    $header = $this->buildHeader();

    $build['table'] = [
      '#type'   => 'table',
      '#header' => $header,
      '#title'  => $this->getTitle(),
      '#rows'   => [],
      '#empty'  => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache'  => [
        'contexts' => $this->entityType->getListCacheContexts() + ['url.query_args'],
        'tags'     => $this->entityType->getListCacheTags() + ['apigee_my_subscriptions'],
      ],
      '#attributes' => ['class' => ['developer-subscription-list']],
    ];

    if (!$developer_id) {
      $this->logger->error($this->t('Developer with email @email not found.', ['@email' => $user->getEmail()]));
      $this->messenger->addError($this->t('Developer with email @email not found.', ['@email' => $user->getEmail()]));
      return $build;
    }

    $rows = [];

    foreach ($this->entityTypeManager->getStorage('subscription')->loadByDeveloperId($developer_id) as $entity) {
      if ($row = $this->buildRow($entity)) {
        $rows[$entity->id()] = $row;
      }
    }

    $build['table']['#rows'] = $rows;

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // TODO: Is a custom unsubscribe access check is really necessary?
    if ($entity->access('update') && $entity->hasLinkTemplate('unsubscribe-form')) {
      if ($entity->isSubscriptionActive()) {
        $operations['unsubscribe'] = [
          'title' => $this->t('Cancel Plan'),
          'weight' => 10,
          'url' => $this->ensureDestination(Url::fromRoute('entity.subscription.unsubscribe_form', ['user' => $this->user->id(), 'subscription' => $entity->id()])),
        ];
      }
    }

    return $operations;
  }

}
