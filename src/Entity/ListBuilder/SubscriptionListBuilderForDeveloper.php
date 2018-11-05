<?php

namespace Drupal\apigee_m10n\Entity\ListBuilder;

use Drupal\apigee_m10n\Entity\SubscriptionInterface;
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
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
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
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
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

  public function buildRow(EntityInterface $subscription) {
    $rate_plan = $subscription->getRatePlan();

    $products = array_reduce($rate_plan->getPackage()->getApiProducts(), function($result, $product) {
      return $result ? "{$result}, {$product->getDisplayName()}" : $product->getDisplayName();
    }, "");

    $row['status'] = $this->getSubscriptionStatus($subscription);
    $row['package'] = $rate_plan->getPackage()->getDisplayName();
    $row['products'] = $products;
    $row['plan'] = Link::fromTextAndUrl($rate_plan->getDisplayName(), $rate_plan->toUrl());
    $row['start_date'] = $subscription->getStartDate()->format('m/d/Y');
    $row['end_date'] = $subscription->getEndDate() ? $subscription->getEndDate()->format('m/d/Y') : null;
    $row['plan_end_date'] = $rate_plan->getEndDate() ? $rate_plan->getEndDate()->format('m/d/Y') : null;
    $row['renewal_date'] = $subscription->getRenewalDate() ? $subscription->getRenewalDate()->format('m/d/Y') : null;

    $row['operations']['data'] = $this->buildOperations($subscription);

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

    $header = $this->buildHeader();

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts() + ['url.query_args'],
        'tags' => $this->entityType->getListCacheTags(),
        'max-age' => 600
      ],
    ];

    if (!$developer_id) {
      $this->logger->error($this->t('Developer with email @email not found.', ['@email' => $user->getEmail()]));
      $this->messenger->addError($this->t('Developer with email @email not found.', ['@email' => $user->getEmail()]));
      return $build;
    }

    $rows = [];

    foreach ($this->storage->loadByDeveloperId($developer_id) as $entity) {
      if ($row = $this->buildRow($entity)) {
        $rows[$entity->id()] = $row;
      }
    }

    // @todo implement storage->getQuery so we can use query->tableSort. @see DeveloperAppListBuilder::getEntityIds.
    $column = tablesort_get_order($header)['sql'] ?? '';
    $sort = tablesort_get_sort($header);

    uasort($rows, function($a, $b) use ($column, $sort) {
      $a = $a[$column];
      $b = $b[$column];
      $return = null;

      if ($a === $b) {
        $return = 0;
      }
      else if (strpos($column, 'date') !== false) {
        $a = is_null($a) ? '+100 years' : $a;
        $b = is_null($b) ? '+100 years' : $b;
        $return = (new \DateTime($a)) < (new \DateTime($b)) ? -1 : 1;
      }
      else {
        $a = is_a($a, Link::class) ? $a->getText() : $a;
        $b = is_a($b, Link::class) ? $b->getText() : $b;
        $return = strcasecmp((string) $a, (string) $b);
      }

      return ($sort === 'asc') ? $return : -$return;
    });

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
   * @todo determine if this should be in the AcceptedRatePlan SDK entity.
   *
   * @param $subscription
   *
   * @return string
   */
  private function getSubscriptionStatus($subscription) {
    $org_timezone = $subscription->getRatePlan()->getOrganization()->getTimezone();
    $today = new \DateTime('today', $org_timezone);

    // If rate plan ended before today, the status is ended.
    $plan_end_date = $subscription->getRatePlan()->getEndDate();
    if (!empty($plan_end_date) && $plan_end_date < $today) {
      return SubscriptionInterface::STATUS_ENDED;
    }
    // If the developer ended the plan before today, the plan has ended.
    $developer_plan_end_date = $subscription->getEndDate();
    if (!empty($developer_plan_end_date) && $developer_plan_end_date < $today) {
      return SubscriptionInterface::STATUS_ENDED;
    }

    // If the start date is later than today, it is a future plan.
    $developer_plan_start_date = $subscription->getStartDate();
    if (!empty($developer_plan_start_date) && $developer_plan_start_date > $today) {
      return SubscriptionInterface::STATUS_FUTURE;
    }

    return SubscriptionInterface::STATUS_ACTIVE;
  }

}