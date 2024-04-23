<?php

namespace Drupal\invoice_agent\Form;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CreateInvoiceForm extends FormBase {

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new OrderReassignForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Drupal\commerce_order\OrderAssignmentInterface $order_assignment
   *   The order assignment service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface|null $password_generator
   *   The password generator.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->order = $current_route_match->getParameter('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'invoice_agent_create_invoice_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->order instanceof Order) {
      $form['date'] = [
        '#type' => 'date',
        '#title' => t('Date'),
        '#default_value' => date('Y-m-d'),
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Generate invoice'),
        '#submit' => ['::submitForm'],
      ];

      $invoice = $this->order->get('field_invoice')->getValue();

      $form['invoice'] = [
        '#markup' =>  ''
      ];
    }

    return $form;
  }

  /**
   * Validate the title and the checkbox of the form
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $date = $form_state->getValue('date');
    if (empty($date)) {
      $date = NULL;
    }

    $this->order->setTotalPaid($this->order->getTotalPrice());
    $this->order->save();

  }

}

