<?php

namespace Drupal\apigee_m10n\Entity\ListBuilder;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SubscriptionListBuilderForDeveloper extends EntityListBuilder implements ContainerInjectionInterface {

  /**
   * @var \Drupal\apigee_m10n\Entity\Storage\SubscriptionStorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $storage);

    $this->storage = $storage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')
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
    return $this->getTitle();
  }

  public function getTitle() {
    return $this->t('@subscriptions', [
      '@subscriptions' => (string) $this->entityTypeManager->getDefinition('subscription')->getPluralLabel()
    ]);
  }

  public function buildHeader() {
    return [
      'status' => ['data' => $this->t('status'), 'field' => 'status'],
      'package' => ['data' => $this->t('package'), 'field' => 'package', 'sort' => 'desc'],
      'products' => ['data' => $this->t('products'), 'field' => 'products'],
      'plan' => ['data' => $this->t('plan'), 'field' => 'plan'],
      'start_date' => ['data' => $this->t('start_date'), 'field' => 'start_date'],
      'end_date' => ['data' => $this->t('end_date'), 'field' => 'end_date'],
      'plan_end_date' => ['data' => $this->t('plan_end_date'), 'field' => 'plan_end_date'],
      'renewal_date' => ['data' => $this->t('renewal_date'), 'field' => 'renewal_date'],
      'operations' => $this->t('Operations')
    ];
  }

  public function buildRow(EntityInterface $entity) {
    $subscription = $entity->getRatePlan();

    $row['status'] = 'qqwer';
    $row['package'] = $subscription->getPackage()->getDisplayName();
    $row['products'] = 'qwer';
    $row['plan'] = count($subscription->getRatePlanDetails());
    $row['start_date'] = ($start = $subscription->getStartDate()) ? $start->format('Y-m-d') : '';
    $row['end_date'] = ($end = $subscription->getEndDate()) ? $end->format('Y-m-d') : '';
    $row['plan_end_date'] = '?';
    $row['renewal_date'] = '?';

    $row['operations']['data'] = $this->buildOperations($entity);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    return parent::getOperations($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    throw new EntityStorageException('Unable to load subscriptions directly. Use `SubscriptionStorage::loadPackageRatePlans`.');
  }

  public function render(UserInterface $user = NULL) {
    $developer_id = $user->apigee_edge_developer_id->value;

    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];

    foreach ($this->storage->loadByDeveloperId($developer_id) as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }
    return $build;
  }
}