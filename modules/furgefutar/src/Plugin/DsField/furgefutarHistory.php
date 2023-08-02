<?php

namespace Drupal\furgefutar\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\furgefutar\Controller\FurgefutarController;

/**
* Plugin that renders the terms from a chosen taxonomy vocabulary.
*
* @DsField(
*   id = "furgefutar_history",
*   title = @Translation("Tracking informations"),
*   entity_type = "commerce_order",
*   provider = "furgefutar",
*   ui_limit = {"*|*"}
* )
*/
class furgefutarHistory extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $order = $this->entity();
    $furgefutarController = new FurgefutarController();

    $build = [
      '#theme' => 'table',
      '#header' => [
        ['data' => $this->t('Date'), 'class' => ['date']],
        ['data' => $this->t('Description'), 'class' => ['description']],
        ['data' => $this->t('Status'), 'class' => ['status']],
      ],
      '#rows' => [],
      '#attributes' => ['class' => ['order-pane-table uc-order-comments']],
      '#empty' => $this->t('This order has no tracking information.'),
    ];

    $tracking_informations = $furgefutarController->getTrackingInformations($order->id());

    if (!empty($tracking_informations)) {
      foreach ($tracking_informations as $tracking_information) {
        $status_desc = $furgefutarController->getTrackingStatusDesc($tracking_information->status);

        $build['#rows'][] = [
          ['data' => $tracking_information->date, 'class' => ['date']],
          ['data' => ['#markup' => $status_desc['desc']], 'class' => ['description']],
          ['data' => ['#children' => '<span style="color: '.$status_desc['color'].'"><strong>'.$status_desc['title'].'</strong></span>'], 'class' => ['status']],
        ];
      }
    }

    return $build;

  }
}
