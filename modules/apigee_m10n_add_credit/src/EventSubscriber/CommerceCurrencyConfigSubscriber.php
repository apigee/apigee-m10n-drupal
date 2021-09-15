<?php

/**
 * @file
 * Contains \Drupal\apigee_m10n_add_credit\CommerceCurrencyConfigSubscriber.
 */

namespace Drupal\apigee_m10n_add_credit\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce_price\Price;
use Drupal\apigee_m10n\MonetizationInterface;

/**
 * Class CommerceCurrencyConfigSubscriber.
 *
 * @package Drupal\apigee_m10n_add_credit\EventSubscriber
 */
class CommerceCurrencyConfigSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The monetization service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * CommerceCurrencyConfigSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, MonetizationInterface $monetization) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->monetization = $monetization;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event to listen, and the methods to be executed.
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE] = ['onCurrencySave', -100];
    $events[ConfigEvents::DELETE] = ['onCurrencyDelete', -100];

    return $events;
  }

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config save event.
   */
  public function onCurrencySave(ConfigCrudEvent $event) {
    $config = $event->getConfig();

    // Excecuted when new currency is saved.
    // For new currencies orginal data is empty array.
    if (!$config->getOriginal() && strpos($config->getName(), 'commerce_price.commerce_currency') !== FALSE) {
      if ($this->monetization->isOrganizationApigeeXorHybrid()) {
        $raw_data = $config->getRawData();
        $currencyCode = $raw_data['currencyCode'];
        // Get the commerce store storeage.
        $store_storage = $this->entityTypeManager->getStorage('commerce_store');
        // Fetch the default store enabled to store the product.
        $default_store = $store_storage->loadDefault();

        // Get the product_variation type.
        $variation_type_storage = $this->entityTypeManager
          ->getStorage('commerce_product_variation_type');
        $variation_type = $variation_type_storage->load('add_credit');

        if ($variation_type) {
          $variation = $this->entityTypeManager
            ->getStorage('commerce_product_variation')
            ->create([
              'type' => 'add_credit',
              'sku' => "ADD-CREDIT-{$currencyCode}",
              'title' => $currencyCode,
              'status' => 1,
              'price' => new Price(1, $currencyCode),
            ]);
          $variation->set('apigee_price_range', [
            'minimum' => 1,
            'maximum' => 999,
            'default' => 1,
            'currency_code' => $currencyCode,
          ]);
          $variation->save();
        }
        // Get the product type.
        $product_type_storage = $this->entityTypeManager
          ->getStorage('commerce_product_type');
        $product_type = $product_type_storage->load('add_credit');

        if ($product_type) {
          // Create an add credit product for this currency.
          $product = $this->entityTypeManager->getStorage('commerce_product')
            ->create([
              'title' => $currencyCode,
              'type' => 'add_credit',
              'stores' => [$default_store->id()],
              'variations' => [$variation],
              AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME => 1,
            ]);
          $product->save();

          // Save config.
          $this->configFactory
            ->getEditable(AddCreditConfig::CONFIG_NAME)
            ->set('products.' . strtolower($currencyCode), [
              'product_id' => $product->id(),
            ])
            ->save();
        }
      }
    }
  }

  /**
   * React to a config object being delete.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config delete event.
   */
  public function onCurrencyDelete(ConfigCrudEvent $event) {
    $config = $event->getConfig();

    // Excecuted when currency is deleted.
    if (strpos($config->getName(), 'commerce_price.commerce_currency') !== FALSE) {
      if ($this->monetization->isOrganizationApigeeXorHybrid()) {
        $original_data = $config->getOriginal();
        $currencyCode = $original_data['currencyCode'];
        $addCreditProducts = $this->entityTypeManager->getStorage('commerce_product')->loadByProperties([
          'apigee_add_credit_enabled' => '1'
          ]
        );
        foreach ($addCreditProducts as $product) {
          if ($product_variation = $product->getDefaultVariation()) {
            if ($product_variation->getPrice()->getCurrencyCode() === $currencyCode) {
              $product->delete();
              // Save config.
              $this->configFactory
                ->getEditable(AddCreditConfig::CONFIG_NAME)
                ->clear('products.' . strtolower($currencyCode))
                ->save();
            }
          }
        }
      }
    }
  }

}
