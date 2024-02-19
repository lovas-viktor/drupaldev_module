<?php

namespace Drupal\furgefutar\Form;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\file\Entity\File;
use Drupal\Core\Site\Settings;
use Drupal\furgefutar\FurgefutarService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShippingSelectForm extends FormBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\commerce_order\Entity\Order $order
   */
  protected Order $order;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\furgefutar\FurgefutarService $furgefutarService
   */
  protected FurgefutarService $furgefutarService;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $currentRouteMatch, FurgefutarService $furgefutarService) {
    $this->currentRouteMatch = $currentRouteMatch;
    $this->order = $this->currentRouteMatch->getParameter('commerce_order');
    $this->furgefutarService = $furgefutarService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('furgefutar.furgefutar_service'),
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
    return 'shipping_select_form';
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
    $env = settings::get('furgefutar_env', 'dev');

    if ($this->order instanceof Order) {
      $existing_quote = $this->furgefutarService->getQuotesForOrder($this->order);

      if (empty($existing_quote)) {
        $packagesNames = $this->furgefutarService->getPackageSizesNames();
        $packagesNames['custom'] = t('Custom size');
        $form ['#attributes']['id'][] = 'furgefutar_shipping_select_form';
        $form['package_size'] = [
          '#type' => 'select',
          '#title' => t('Package size'),
          '#options' => $packagesNames,
          '#default_value' => $form_state->getValue('package_size'),
        ];

        $form['width'] = [
          '#type' => 'number',
          '#title' => t('Width'),
          '#field_suffix' => ' cm',
          '#states' => [
            'visible' => [
              ':input[name="package_size"]' => ['value' => 'custom'],
            ],
          ],
          '#default_value' => $form_state->getValue('width'),
        ];

        $form['length'] = [
          '#type' => 'number',
          '#title' => t('Length'),
          '#field_suffix' => ' cm',
          '#states' => [
            'visible' => [
              ':input[name="package_size"]' => ['value' => 'custom'],
            ],
          ],
          '#default_value' => $form_state->getValue('length'),
        ];

        $form['height'] = [
          '#type' => 'number',
          '#title' => t('Height'),
          '#field_suffix' => ' cm',
          '#states' => [
            'visible' => [
              ':input[name="package_size"]' => ['value' => 'custom'],
            ],
          ],
          '#default_value' => $form_state->getValue('height'),
        ];

        $form['weight'] = [
          '#type' => 'number',
          '#title' => t('Weight'),
          '#field_suffix' => ' g',
          '#states' => [
            'visible' => [
              ':input[name="package_size"]' => ['value' => 'custom'],
            ],
          ],
          '#default_value' => $form_state->getValue('weight'),
        ];

        // Group submit handlers in an actions element with a key of "actions" so
        // that it gets styled correctly, and so that other modules may add actions
        // to the form. This is not required, but is convention.

        // Add a submit button that handles the submission of the form.
        $form['get_quote'] = [
          '#type' => 'button',
          '#value' => t('Get shipping quotes'),
          '#ajax' => [
            'callback' => '::getQuotes',
            'disable-refocus' => FALSE,
            'event' => 'click',
            'wrapper' => 'furgefutar_shipping_select_form',
            'progress' => [
              'type' => 'throbber',
              'message' => t('Verifying entry...'),
            ],
          ],
        ];
      }
      else {
        $form['get_quote_information'] = [
          '#markup' => t('You only can select one shipping per order!'),
        ];
      }

      $triggered_element_id = $form_state->getTriggeringElement()['#parents'][0] ?? NULL;

      $values = $form_state->getValues();
      if ($triggered_element_id) {
        if ($values['package_size'] == 'custom') {
          $package = [
            'cmWidth' => $values['width'],
            'cmLength' => $values['length'],
            'cmHeight' => $values['height'],
            'gWt' => $values['weight'],
          ];
        }
        else {
          $packages = $this->furgefutarService->getPackageSizes();
          $package = $packages[$values['package_size']];
        }

        $package['tyPackage'] = 'PARCEL';
        $package['ctPackage'] = '1';
        $package['amContent'] = $this->order->getTotalPrice()->getNumber();
        $package['txContent'] = 'Művirág';
        $package['idOrder'] = $this->order->id();

        $shipping_profile = $this->order->collectProfiles()['shipping'];

        $name = sprintf('%s %s', $shipping_profile->address->first()
          ->get('family_name')
          ->getValue(), $shipping_profile->address->first()
          ->get('given_name')
          ->getValue());
        $zip_code = $shipping_profile->address->first()
          ->get('postal_code')
          ->getValue();
        $city = $shipping_profile->address->first()
          ->get('locality')
          ->getValue();
        $address = $shipping_profile->address->first()
          ->get('address_line1')
          ->getValue();
        $phone = $shipping_profile->address->first()
          ->get('address_line2')
          ->getValue();

        $country_code = $shipping_profile->address->first()->get('country_code')->getString();

        $client = \Drupal::httpClient();
        $array = [
          'REQUEST' => [
            'txEmail' => 'info@bokretakeramia.hu',
            'txPassword' => 'Bokreta2023!',
            'flDebug' => 'true',
            'cdLang' => $country_code,
            'flSendWaybill' => 'true',
            'QUOTE' => [
              'tyCOD' => 'NONE',
              'flNothingProhibited' => 'true',
              'flAgreedToTermsAndConditions' => 'true',
              'flInsured' => 'false',
              'ADDRESSES' => [
                'DESTINATION' => [
                  'nmCompanyOrPerson' => $name,
                  'cdCountry' => $country_code,
                  'txAddress' => $address,
                  'txAddressNumber' => $address,
                  'txPost' => trim($zip_code),
                  'txCity' => $city,
                  'nmContact' => $name,
                  'txPhoneContact' => $phone,
                  'txEmailContact' => $this->order->getEmail(),
                  'txInstruction' => '',
                ],
              ],
              'PACKAGES' => [
                'PACKAGE' => [$package],
              ],
            ],
          ],
        ];

        if ($this->order->get('payment_gateway')->entity->id() == 'cash_on_delivery') {
          $array['REQUEST']['QUOTE']['tyCOD'] = 'CONTENT';
        }

        if ($env == 'prod') {
          $array['REQUEST']['flDebug'] = 'false';
        }

        if (isset($values['quotes']) && $triggered_element_id === 'order_quote') {
          $quote_ids = explode('_', $values['quotes']);
          $array['REQUEST']['BOOK'] = [
            'dtPickup' => date("Y.m.d", strtotime("+1 day")),
            'idCarrier' => $quote_ids[0],
            'idService' => $quote_ids[1],
          ];
        }

        $request = $client->post('https://api.pactic.com/webservices/webshop.ashx', [
          'json' => $array,
        ]);

        $response = json_decode($request->getBody());

        if (!empty($response->Messages)) {
          foreach ($response->Messages as $message) {
            if ($message->Type == 0) {
              \Drupal::messenger()->addError($message->Text);
            } else {
              \Drupal::messenger()->addInfo($message->Text);
            }
          }
        }

        if (is_array($response->Quotes[0]->Labels) && count($response->Quotes[0]->Labels)) {
          $directory = 'public://furgefutar_labels/';
          \Drupal::service('file_system')
            ->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
          $file = \Drupal::service('file.repository')
            ->writeData(base64_decode($response->Quotes[0]->Labels[0]), $directory . $response->Quotes[0]->WayBills[0] . '.pdf', FileSystemInterface::EXISTS_REPLACE);
          $response->Quotes[0]->Labels = [
            '0' => $file->id(),
          ];
        }

        if (!empty($values['quotes']) && $triggered_element_id == 'order_quote') {
          $quote_ok = FALSE;
          foreach ($response->Messages as $message) {
            if ($message->Type == 1) {
              $quote_ok = TRUE;
            }
          }

          if ($quote_ok) {
            $this->furgefutarService->setQuoteToOrder($response->Quotes[0], $this->order, $package);
          }
          else {
            foreach ($response->Messages as $message) {
              //$form_state->setError('any', $message->Text);
            }
          }
        }
        else {
          foreach ($response->Messages as $message) {
            //$form_state->setError('any', $message->Text);
          }
        }

        $quotes = [];
        foreach ($response->Quotes as $quote) {
          $quote_id = $quote->Service->idCarrier . '_' . $quote->Service->idService;
          $quotes[$quote_id] = $quote->Service->nmCarrier . ' ' . $quote->Service->nmService . ' (' . ($quote->Service->amNet + $quote->Service->amVAT) . ' Ft)';
        }

        $form['quotes'] = [
          '#type' => 'select',
          '#options' => $quotes,
          '#title' => t('Select a quote'),
        ];

        // Add a submit button that handles the submission of the form.
        $form['order_quote'] = [
          '#type' => 'button',
          '#value' => t('Order the shipping quotes'),
          '#ajax' => [
            #'callback' => '::getQuotes', // don't forget :: when calling a class method.
            'callback' => [$this, 'getQuotes'],
            //alternative notation
            'disable-refocus' => FALSE,
            // Or TRUE to prevent re-focusing on the triggering element.
            'event' => 'click',
            'wrapper' => 'shipping-select-form',
            // This element is updated with this AJAX callback.
            'progress' => [
              'type' => 'throbber',
              'message' => t('Ordering under process...'),
            ],
          ],
          '#weight' => 100,
        ];
      }
      $this->getQuoteDataTable($form, $form_state);
    }

    return $form;
  }

  /**
   * Displays the quote data table.
   *
   * @param array $form
   * @param array $form_state
   */
  public function getQuoteDataTable(&$form, $form_state) {
    $quote_data = $this->furgefutarService->getQuotesForOrder($this->order);

    if (!empty($quote_data)) {
      $results = $quote_data;

      $form['order_quote_datas'] = [
        '#type' => 'table',
        '#caption' => t('Ordered quote'),
        '#header' => [
          t('ID'),
          t('Furgefutar ID'),
          t('Name'),
          t('Package'),
          t('Package Price'),
          t('Shipping Price'),
          t('Label'),
          t('Status'),
        ],
        '#weight' => -50,
      ];

      $i = 0;
      foreach ($results as $result) {
        $i++;

        $data = unserialize($result->data);

        $package = $data->package;
        $form['order_quote_datas'][$i]['id'] = [
          '#markup' => $result->id,
        ];

        $form['order_quote_datas'][$i]['furgefutarid'] = [
          '#markup' => $data->idQuote,
        ];
        $form['order_quote_datas'][$i]['name'] = [
          '#markup' => $data->Service->nmCarrier . ' ' . $data->Service->nmService,
        ];
        $form['order_quote_datas'][$i]['package'] = [
          '#markup' => $package['cmWidth'] . 'X' . $package['cmLength'] . 'X' . $package['cmHeight'] . ' cm papírdoboz',
        ];

        $form['order_quote_datas'][$i]['package_price'] = [
          '#markup' => \Drupal::service('commerce_price.currency_formatter')
            ->format($this->order->getTotalPrice()
              ->getNumber(), $this->order->getTotalPrice()->getCurrencyCode()),
        ];
        $form['order_quote_datas'][$i]['shipping_price'] = [
          '#markup' => ($data->Service->amNet + $data->Service->amVAT) . ' Ft',
        ];

        if (is_array($data->Labels) && isset($data->Labels[0])) {
          $file = File::load($data->Labels[0]);
          $uri = $file->getFileUri();
          $fileUrl = \Drupal::service('file_url_generator')
            ->generateString($uri);

          $form['order_quote_datas'][$i]['label'] = [
            '#markup' => '<a href="' . $fileUrl . '" target="_blank">' . $file->getFilename() . '</a>',
          ];
        }
        else {
          if (isset($data->WayBills[0])) {
            $form['order_quote_datas'][$i]['label'] = [
              '#markup' => $data->WayBills[0],
            ];
          }
          else {
            $form['order_quote_datas'][$i]['label'] = [
              '#markup' => t('No label'),
            ];
          }
        }

        $form['order_quote_datas'][$i]['status'] = [
          '#children' => $this->furgefutarService->getOrderTrackingStatus($this->order, 'name') . '</br><small>' . $this->furgefutarService->getOrderTrackingStatus($this->order, 'desc') . '</small>',
        ];
      }
    }
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
  }

  // Get the value from example select field and fill
  // the textbox with the selected text.
  public function getQuotes(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}

