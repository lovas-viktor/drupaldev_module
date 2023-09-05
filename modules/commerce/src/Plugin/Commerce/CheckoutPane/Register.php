<?php

namespace Drupal\drupaldev_commerce\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\CredentialsCheckFloodInterface;
use Drupal\commerce_checkout\Event\CheckoutCompletionRegisterEvent;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_order\OrderAssignmentInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the contact information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "register",
 *   label = @Translation("Register"),
 *   default_step = "review",
 *   wrapper_element = "fieldset",
 * )
 */
class Register extends CheckoutPaneBase {

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The client IP address.
   *
   * @var string
   */
  protected $clientIp;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The order assignment.
   *
   * @var \Drupal\commerce_order\OrderAssignmentInterface
   */
  protected $orderAssignment;

  /**
   * Constructs a new CompletionRegister object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\commerce\CredentialsCheckFloodInterface $credentials_check_flood
   *   The credentials check flood controller.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_order\OrderAssignmentInterface $order_assignment
   *   The order assignment.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, CredentialsCheckFloodInterface $credentials_check_flood, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, OrderAssignmentInterface $order_assignment, RequestStack $request_stack, UserAuthInterface $user_auth) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->credentialsCheckFlood = $credentials_check_flood;
    $this->currentUser = $current_user;
    $this->clientIp = $request_stack->getCurrentRequest()->getClientIp();
    $this->eventDispatcher = $event_dispatcher;
    $this->orderAssignment = $order_assignment;
    $this->userAuth = $user_auth;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('commerce.credentials_check_flood'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce_order.order_assignment'),
      $container->get('request_stack'),
      $container->get('user.auth')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    if ($this->currentUser->isAuthenticated()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $pane_form['pass_confirm'] = [
      '#type' => 'password',
      '#title' => $this->t('Password confirm'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->create([]);
    $form_display = EntityFormDisplay::collectRenderDisplay($account, 'register');
    $form_display->buildForm($account, $pane_form, $form_state);

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Validate the entity. This will ensure that the username and email are in
    // the right format and not already taken.
    $values = $form_state->getValue($pane_form['#parents']);
    if ($values['pass'] != $values['pass_confirm']) {
      $form_state->setError($pane_form['pass'], $this->t('The specified passwords do not match.'));
    }

    $existing_user = $this->userStorage->loadByProperties([
      'mail' => $this->order->getEmail(),
    ]);

    if ($existing_user) {
      $uid = $this->userAuth->authenticate($this->order->getEmail(), $values['pass_confirm']);
      if (!$uid) {
        $this->credentialsCheckFlood->register($this->clientIp, $this->order->getEmail());
        $form_state->setError($pane_form['pass'], $this->t('You already have a user with the given email But the password you provided is wrong. <a href=":url">Have you forgotten your password?</a>', [':url' => '/user/password']));
      }
    }

    $account = $this->userStorage->create([
      'mail' => $this->order->getEmail(),
      'name' => $this->order->getEmail(),
      'pass' => $values['pass'],
      'status' => TRUE,
    ]);

    /** @var \Drupal\user\UserInterface $account */
    $form_display = EntityFormDisplay::collectRenderDisplay($account, 'register');
    $form_display->extractFormValues($account, $pane_form, $form_state);
    $form_display->validateFormValues($account, $pane_form, $form_state);

    // Manually flag violations of fields not handled by the form display. This
    // is necessary as entity form displays only flag violations for fields
    // contained in the display.
    // @see \Drupal\user\AccountForm::flagViolations
    $violations = $account->validate();
    foreach ($violations->getByFields(['pass']) as $violation) {
      [$field_name] = explode('.', $violation->getPropertyPath(), 2);
      $form_state->setError($pane_form[$field_name], $violation->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $this->order->setEmail($this->order->getEmail());

    $existing_user = $this->userStorage->loadByProperties([
      'mail' => $this->order->getEmail(),
    ]);

    if ($existing_user) {
      $account = reset($existing_user);
    }
    else {
      $account = $this->userStorage->create([
        'pass' => $values['pass'],
        'mail' => $this->order->getEmail(),
        'name' => $this->order->getEmail(),
        'status' => TRUE,
      ]);
      /** @var \Drupal\user\UserInterface $account */
      $form_display = EntityFormDisplay::collectRenderDisplay($account, 'register');
      $form_display->extractFormValues($account, $pane_form, $form_state);
      $account->save();
    }

    user_login_finalize($account);
    $this->credentialsCheckFlood->clearAccount($this->clientIp, $account->getAccountName());
    $this->orderAssignment->assign($this->order, $account);

    // Notify other modules.
    $event = new CheckoutCompletionRegisterEvent($account, $this->order);
    $this->eventDispatcher->dispatch($event, CheckoutEvents::COMPLETION_REGISTER);
    // Event subscribers are allowed to set a redirect url, to send the
    // customer to their orders page, for example.
    if ($url = $event->getRedirectUrl()) {
      $form_state->setRedirectUrl($url);
    }
    $this->messenger()
      ->addStatus($this->t('Registration successful. You are now logged in.'));
  }

}
