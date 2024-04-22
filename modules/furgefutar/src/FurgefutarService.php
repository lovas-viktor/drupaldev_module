<?php

namespace Drupal\furgefutar;

use Drupal\commerce_order\Entity\Order;

class FurgefutarService {

  /**
   * Returns predefined package sizes.
   *
   * @return array
   */
  public function getPackageSizes() {
    $packages = [];

    $packages[0] = [
      'cmWidth' => '20',
      'cmLength' => '20',
      'cmHeight' => '15',
      'gWt' => '1000',
    ];

    $packages[1] = [
      'cmWidth' => '30',
      'cmLength' => '30',
      'cmHeight' => '20',
      'gWt' => '1000',
    ];

    $packages[2] = [
      'cmWidth' => '20',
      'cmLength' => '40',
      'cmHeight' => '40',
      'gWt' => '1000',
    ];

    $packages[3] = [
      'cmWidth' => '50',
      'cmLength' => '20',
      'cmHeight' => '20',
      'gWt' => '1000',
    ];

    return $packages;
  }

  /**
   * Get package names based on package sizes.
   *
   * @return array
   */
  public function getPackageSizesNames() {
    $packages = $this->getPackageSizes();

    $packagesNames = [];

    foreach ($packages as $id => $package) {
      $packagesNames[$id] = $package['cmWidth'] . 'X' . $package['cmLength'] . 'X' . $package['cmHeight'] . ' cm papírdoboz, ' . ($package['gWt'] / 1000) . 'KG';
    }

    return $packagesNames;
  }

  /**
   * Saves the quote to the database.
   *
   * @param array $data
   * @param \Drupal\commerce_order\Entity\Order $order
   * @param $package
   */
  public function setQuoteToOrder($data, Order $order, $package) {
    $data->package = $package;
    $field_arr = [
      'entity_type' => 'commerce_order',
      'entity_bundle' => 'commerce_order',
      'entity_id' => $order->id(),
      'data' => serialize($data),
    ];

    $query = \Drupal::database();
    $query->insert('furgefutar')
      ->fields($field_arr)
      ->execute();
  }

  /**
   * Delete order quotes.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *
   * @return void
   */
  public function deleteOrderQuotes(Order $order) {
    $connection = \Drupal::database();
    $result = $connection->delete('furgefutar')
      ->condition('entity_id', $order->id())
      ->execute();
    if ($result == 1) {
      \Drupal::messenger()->addStatus(t('Label deleted successfully'));
    }
  }

  /**
   * Updates data based on id and status.
   *
   * @param int $id
   * @param array $data
   * @param int $status
   */
  public function updateData($id, $data, $status) {
    if ($status) {
      \Drupal::database()->update('furgefutar')
        ->condition('id', $id)
        ->fields([
          'data' => serialize($data),
          'status' => $status,
        ])
        ->execute();
    }
  }

  /**
   * Return existing quotes for order.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *
   * @return array
   */
  public function getQuotesForOrder(Order $order) {
    $database = \Drupal::database();
    $query = $database->select('furgefutar', 'f');

    $query->condition('f.entity_id', $order->id());
    $query->fields('f', ['id', 'data']);
    $query->range(0, 50);

    return $query->execute()->fetchAll();
  }

  /**
   * Return status description based on status code.
   *
   * @param string $statusCode
   *
   * @return array
   */
  public function getTrackingStatusDescription($statusCode) {
    $statusDesc = [
      '1' => [
        'title' => t('Data Received'),
        'desc' => t('Data transferred and received by carrier'),
        'color' => 'gray',
        'order_status' => 'waiting_delivery',
      ],
      '2' => [
        'title' => t('In transit'),
        'desc' => t('Parcel is in transit, this might mean being in a HUB (central or local) or being transferred between HUBs.'),
        'color' => 'gray',
        'order_status' => 'under_delivery',
      ],
      '3' => [
        'title' => t('Out for Delivery'),
        'desc' => t('Parcel is at courier, delivery expected soon.'),
        'color' => 'orange',
        'order_status' => '',
      ],
      '4' => [
        'title' => t('Delivered'),
        'desc' => t('Parcel was delivered successfully.'),
        'color' => 'green',
        'order_status' => 'completed',
      ],
      '5' => [
        'title' => t('Disruptions'),
        'desc' => t('Disruptions happened (lost, refused, damaged, etc.). Contact Allpacka/Furgefutar for more information.'),
        'color' => 'red',
        'order_status' => 'problem',
      ],
      '6' => [
        'title' => t('Returned'),
        'desc' => t('Parcel sent back to original sender.'),
        'color' => 'red',
        'order_status' => 'problem',
      ],
      '7' => [
        'title' => t('Cancelled'),
        'desc' => t('Shipment has been cancelled'),
        'color' => 'red',
        'order_status' => 'problem',
      ],
      '8' => [
        'title' => t('No data available'),
        'desc' => t('Data transferred to carrier, but not acknowledged yet. Please note that this status exists only on the website for now. This web service returns nothing when no data is available.'),
        'color' => 'red',
        'order_status' => 'problem',
      ],
      '9' => [
        'title' => t('Damaged'),
        'desc' => t('Parcel got damaged.'),
        'color' => 'red',
        'order_status' => 'problem',
      ],
      '10' => [
        'title' => t('Drop-off Point'),
        'desc' => t('Parcel is in drop-off point or parcel shop'),
        'color' => 'gray',
        'order_status' => '',
      ],
      '11' => [
        'title' => t('Lost'),
        'desc' => t('Parcel has been lost.'),
        'color' => 'red',
        'order_status' => 'problem',
      ],
      '12' => [
        'title' => t('Refused'),
        'desc' => t('Consignee refused the parcel.'),
        'color' => 'red',
        'order_status' => 'problem',
      ],
      '13' => [
        'title' => t('Consignee absent'),
        'desc' => t('Consignee could not be found.'),
        'color' => 'red',
        'order_status' => 'problem',
      ],
      '14' => [
        'title' => t('Wrong address'),
        'desc' => t('Wrong address.'),
        'color' => 'red',
        'order_status' => 'problem',
      ],
      '101' => [
        'title' => t('Arrived to HUB'),
        'desc' => t('Parcel has arrived to HUB.'),
        'color' => 'gray',
        'order_status' => '',
      ],
      '102' => [
        'title' => t('Linehaul Transit'),
        'desc' => t('Parcel has entered the Linehaul network.'),
        'color' => 'gray',
        'order_status' => '',
      ],
      '103' => [
        'title' => t('Dropped off'),
        'desc' => t('Parcel has been dropped off at last mile courier.'),
        'color' => 'green',
        'order_status' => '',
      ],
      '104' => [
        'title' => t('Final Return'),
        'desc' => t('Return parcel has been received and processed.'),
        'color' => 'gray',
        'order_status' => '',
      ],
      '105' => [
        'title' => t('Consolidated by Sender'),
        'desc' => t('Sender has consolidated the parcels.'),
        'color' => 'gray',
        'order_status' => '',
      ],
    ];

    return $statusDesc[$statusCode];
  }

  /**
   * Get tracking information.
   *
   * @param Order $order
   * @param $wayBill
   * @param $refresh
   *
   * @return false|string
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getTrackingInformations($order, $wayBill = 0, $refresh = 0) {
    if ($refresh) {
      $client = \Drupal::httpClient();
      $response = $client->request('GET', 'http://allpacka.com/webservices/shipment/tracking/track.ashx?waybill=' . $wayBill);
      return json_decode($response->getBody()->getContents())[0]->history;
    }
    else {
      $database = \Drupal::database();
      $query = $database->select('furgefutar', 'f');

      $query->condition('f.entity_id', $order->id());
      $query->fields('f', ['id', 'entity_id', 'data', 'status']);
      $query->orderBy('id', 'DESC');
      $query->range(0, 1);
      $results = $query->execute()->fetchAll();

      // Check if we have results.
      if (empty($results[0])) {
        return '';
      }

      $data = unserialize($results[0]->data);

      return !empty($data->tracking) ? $data->tracking : FALSE;
    }
  }

  /**
   * Get order tracking status.
   *
   * @param Order $order
   * @param string $mode
   *
   * @return string
   */
  public function getOrderTrackingStatus(Order $order, $mode = '') {
    $tracking_information = $this->getTrackingInformations($order);
    if (empty($tracking_information)) {
      $status_desc = [
        'title' => '',
        'desc' => '',
        'color' => '',
      ];
    }
    else {
      $last_tracking_information = end($tracking_information);
      $status = $last_tracking_information->status;
      $status_desc = $this->getTrackingStatusDescription($status);
    }

    if ($mode == 'name') {
      return '<span style="color: ' . $status_desc['color'] . '"><strong>' . $status_desc['title'] . '</strong></span>';
    }

    return $status_desc['desc'];
  }

  /**
   * Update tracking informations.
   */
  public function updateTrackingInformations() {
    $database = \Drupal::database();
    $query = $database->select('furgefutar', 'f');

    $query->condition('f.status', '4', '!=');
    $query->fields('f', ['id', 'entity_id', 'data', 'status']);
    $query->orderBy('id', 'DESC');
    $query->range(0, 50);
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      $data = unserialize($result->data);
      if (isset($data->WayBills[0])) {
        $tracking_information = $this->getTrackingInformations(0, $data->WayBills[0], 1);
        $data->tracking = $tracking_information;
        $last_tracking_information = end($tracking_information);
        $status = $last_tracking_information->status;

        $status_desc = $this->getTrackingStatusDescription($status);
        if ($status_desc['order_status']) {
          $order = Order::load($result->entity_id);

          if ($order instanceof Order) {
            $order->setStatusId($status_desc['order_status'])->save();
          }
        }

        $this->updateData($result->id, $data, $status);
      }
    }
  }

}
