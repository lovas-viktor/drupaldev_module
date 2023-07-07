<?php

namespace Drupal\drupaldev_commerce;

use Drupal\commerce_shipping\ProfileFieldCopy as BaseProfileFieldCopy;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ProfileFieldCopy extends BaseProfileFieldCopy {

  /**
   * {@inheritdoc}
   */
  public function supportsForm(array &$inline_form, FormStateInterface $form_state) {
    if (!isset($inline_form['#profile_scope']) || $inline_form['#profile_scope'] != 'shipping') {
      return FALSE;
    }
    $order = self::getOrder($form_state);
    if (!$order) {
      // The inline form is being used outside of an order context
      // (e.g. the payment method add/edit screen).
      return FALSE;
    }
    if (!$order->hasField('shipments')) {
      // The order is not shippable.
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$inline_form, FormStateInterface $form_state) {
    $billing_profile = self::getBillingProfileFromFormState($form_state);
    if (!$billing_profile) {
      // No source information is available.
      return;
    }
    $shipping_profile = self::getShippingProfile($form_state);
    $billing_form_display = self::getFormDisplay($billing_profile, 'billing');
    $billing_fields = array_keys($billing_form_display->getComponents());
    $user_input = (array) NestedArray::getValue($form_state->getUserInput(), $inline_form['#parents']);
    // Copying is enabled by default for new billing profiles.
    $enabled = $shipping_profile->getData('copy_fields', $shipping_profile->isNew());
    if ($user_input) {
      if (isset($user_input['copy_fields'])) {
        $enabled = (bool) $user_input['copy_fields']['enable'];
      }
      elseif (isset($user_input['select_address'])) {
        $enabled = FALSE;
      }
    }

    $inline_form['copy_fields'] = [
      '#parents' => array_merge($inline_form['#parents'], ['copy_fields']),
      '#type' => 'container',
      '#weight' => -1000,
      '#billing_fields' => $billing_fields,
      '#has_form' => FALSE,
    ];
    $inline_form['copy_fields']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->getCopyLabel($inline_form),
      '#default_value' => $enabled,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $inline_form['#id'],
      ],
    ];

    if ($enabled) {
      // Copy over the current shipping field values, allowing widgets such as
      // TaxNumberDefaultWidget to rely on them. These values might change
      // during submit, so the profile is populated again in submitForm().
      $shipping_profile->populateFromProfile($billing_profile, $billing_fields);
      // Disable address book copying and remove all existing fields.
      $inline_form['copy_to_address_book'] = [
        '#type' => 'value',
        '#value' => FALSE,
      ];
      foreach (Element::getVisibleChildren($inline_form) as $key) {
        if (!in_array($key, ['copy_fields', 'copy_to_address_book'])) {
          $inline_form[$key]['#access'] = FALSE;
        }
      }
      // Add field widgets for any non-copied billing fields.
      $form_display = self::getFormDisplay($shipping_profile, 'shipping', $billing_fields);
      $shipping_fields = array_keys($form_display->getComponents());
      if ($shipping_fields) {
        $form_display->buildForm($shipping_profile, $inline_form['copy_fields'], $form_state);
        $inline_form['copy_fields']['#has_form'] = TRUE;
      }
      // Replace the existing validate/submit handlers with custom ones.
      foreach ($inline_form['#element_validate'] as &$validate_handler) {
        if ($validate_handler[1] == 'runValidate') {
          $validate_handler = [get_class($this), 'validateForm'];
          break;
        }
      }
      foreach ($inline_form['#commerce_element_submit'] as &$submit_handler) {
        if ($submit_handler[1] == 'runSubmit') {
          $submit_handler = [get_class($this), 'submitForm'];
          break;
        }
      }
    }
    else {
      $shipping_profile->unsetData('copy_fields');
    }
  }

  /**
   * Gets the copy label for the given inline form.
   *
   * @param array $inline_form
   *   The inline form.
   *
   * @return string
   *   The copy label.
   */
  protected function getCopyLabel(array $inline_form) {
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $plugin */
    $plugin = $inline_form['#inline_form'];
    $configuration = $plugin->getConfiguration();
    $is_owner = FALSE;
    if (empty($configuration['admin'])) {
      $is_owner = $this->currentUser->id() == $configuration['address_book_uid'];
    }

    if ($is_owner) {
      $copy_label = $this->t('My shipping information is the same as my billing information.');
    }
    else {
      $copy_label = $this->t('Shipping information is the same as the billing information.');
    }

    return $copy_label;
  }

  /**
   * Validates the inline form.
   *
   * @param array $inline_form
   *   The inline form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateForm(array &$inline_form, FormStateInterface $form_state) {
    $billing_fields = $inline_form['copy_fields']['#billing_fields'];
    if ($inline_form['copy_fields']['#has_form']) {
      $shipping_profile = self::getShippingProfile($form_state);
      $form_display = self::getFormDisplay($shipping_profile, 'shipping', $billing_fields);
      $form_display->extractFormValues($shipping_profile, $inline_form['copy_fields'], $form_state);
      $form_display->validateFormValues($shipping_profile, $inline_form['copy_fields'], $form_state);
    }
  }

  /**
   * Submits the inline form.
   *
   * @param array $inline_form
   *   The inline form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitForm(array &$inline_form, FormStateInterface $form_state) {
    $billing_fields = $inline_form['copy_fields']['#billing_fields'];
    $billing_profile = self::getBillingProfileFromFormState($form_state);
    $shipping_profile = self::getShippingProfile($form_state);

    $shipping_profile->populateFromProfile($billing_profile, $billing_fields);
    if ($inline_form['copy_fields']['#has_form']) {
      $form_display = self::getFormDisplay($shipping_profile, 'shipping', $billing_fields);
      $form_display->extractFormValues($shipping_profile, $inline_form['copy_fields'], $form_state);
    }
    $shipping_profile->setData('copy_fields', TRUE);
    $shipping_profile->unsetData('copy_to_address_book');
    // Transfer the source address book ID to ensure that the right option
    // is preselected when the copy_fields checkbox is unchecked.
    $address_book_profile_id = $shipping_profile->getData('address_book_profile_id');
    if ($address_book_profile_id && $billing_profile->bundle() == $shipping_profile->bundle()) {
      $shipping_profile->setData('address_book_profile_id', $address_book_profile_id);
    }
    $shipping_profile->save();
  }

  /**
   * Gets the billing profile from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The billing profile.
   */
  protected static function getBillingProfileFromFormState(FormStateInterface $form_state) {
    if ($form_state->has('billing_profile')) {
      // Shipping information on the same step as the billing information.
      $billing_profile = $form_state->get('billing_profile');
    }
    else {
      $order = self::getOrder($form_state);
      $profiles = $order->collectProfiles();
      $billing_profile = $profiles['billing'] ?? NULL;
    }

    return $billing_profile;
  }


}
