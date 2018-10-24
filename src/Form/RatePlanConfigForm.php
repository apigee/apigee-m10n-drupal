<?php

namespace Drupal\apigee_m10n\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class for managing `apigee_m10n.rate_plan.config` settings.
 *
 * @package Drupal\apigee_m10n\Form
 */
class RatePlanConfigForm extends ConfigFormBase {

  /**
   * The config named used by this form.
   */
  const CONFIG_NAME = 'apigee_m10n.rate_plan.config';

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entity_display_repository;

  /**
   * Constructs a \Drupal\apigee_m10n\Form\RatePlanConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($config_factory);
    $this->entity_display_repository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rate_plan_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the working configuration.
    $config = $this->config(static::CONFIG_NAME);

    // Get view mode options from the repository service.
    $options = $this->entity_display_repository->getViewModeOptionsByBundle('rate_plan', 'rate_plan');

    $form['product_rate_plan_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Product rate plan view mode'),
      '#description' => $this->t('Select the view mode to use for displaying plans for packages.'),
      '#options' => $options,
      '#default_value' => $config->get('product_rate_plan_view_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config(static::CONFIG_NAME)
      ->set('product_rate_plan_view_mode', $form_state->getValue('product_rate_plan_view_mode'))
      ->save();
  }

}
