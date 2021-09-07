<?php

namespace Drupal\apigee_m10n_add_credit\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Apigee\Edge\Api\ApigeeX\Controller\DeveloperBillingTypeController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\Entity\User;
use Drupal\apigee_edge\Entity\Developer;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\apigee_m10n\MonetizationInterface;

/**
 * Defines a confirmation form to confirm updating of developer billing type.
 */
class ConfirmUpdateForm extends ConfirmFormBase {

  /**
   * The user from route.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Monetization base.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * Constructs a Confirmation object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   Monetization base.
   */
  public function __construct(MessengerInterface $messenger, RouteMatchInterface $routeMatch, MonetizationInterface $monetization) {
    $this->messenger = $messenger;
    $this->routeMatch = $routeMatch;
    $this->monetization = $monetization;
    $this->userId = $routeMatch->getParameter('user');
    $this->user = User::load($this->userId);
    $this->billingtype_selected = $routeMatch->getParameter('billingtype');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('current_route_match'),
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * Checks current users access to Billing Profile page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Grants access to the route if passed permissions are present.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $user = $this->user;
    try {
      $developer_billingtype = $this->monetization->getBillingtype($user);
    }
    catch (\Exception $e) {
      return AccessResult::forbidden('Developer does not exist.');
    }

    /*if ($developer_billingtype) {
      return AccessResult::forbidden('Billing type for this developer is already set');
    }*/

    return AccessResult::allowedIf(
      $account->hasPermission('update any billing type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $developer_billing_type = $this->monetization->updateBillingtype($this->user->getEmail(), strtoupper($this->billingtype_selected));
      $this->messenger->addStatus($this->t('Billing type of the user is saved.'));
      $form_state->setRedirect('entity.user.edit_form', ['user' => $this->user->id()]);
      drupal_flush_all_caches();
    }
    catch (\Exception $e) {
      $this->messenger->addError($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_change_billing_type_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('apigee_m10n_add_credit.userbillingtype', ['user' => $this->userId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    return $this->t('Are you sure you want to change the billing type for user %email?', ['%email' => $this->user->getEmail()]);
  }

}
