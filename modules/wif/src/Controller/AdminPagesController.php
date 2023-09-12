<?php
/**
 * @file
 * Contains \Drupal\wif\Controller\AdminPagesController.
 */

namespace Drupal\wif\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Webshop Image Formatter module.
 */
class AdminPagesController extends ControllerBase {

  /**
   * Returns the presets page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function presets() {
    $element = [
      '#markup' => 'Presets page goes here',
    ];
    return $element;
  }

  /**
   * Returns the library download page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function downloadLibraries() {
    if (\Drupal::hasService('extension.list.module')) {
      /** @var \Drupal\Core\Extension\ModuleExtensionList $module_list */
      $module_list = \Drupal::service('extension.list.module');
      $file = DRUPAL_ROOT . '/' . $module_list->getPath('wif') . "/wif.module";
      if (is_file($file)) {
        require_once $file;
      }
    }
    else {
      return;
    }

    // Load wif.settings configuration
    $wif_settings = \Drupal::config('wif.settings');
    // Get needed libraries array with information about the libraries
    $libraries = $wif_settings->get('needed_libraries');

    // Get real path to the js files
    foreach ($libraries as $library_name => $library_settings) {
      $libraries[$library_name]['path'] = _wif_get_path($library_name, $library_settings['path_to_js']);
    }

    // Create table rows for the libraries
    $rows = [];
    $installation_counter = 0;
    foreach ($libraries as $library_name => $library) {
      $rows[] = [
        'data' => [
          $library['name'],
          [
            'data' => ($library['path']) ? t('Installed successfully!') : t('Library is not found.'),
            'class' => ($library['path']) ? 'messages messages--status' : 'messages messages--error',
          ],
          'Install button goes here',
        ],
      ];

      // If this library is installed, increase the installation_counter
      if ($library['path']) {
        $installation_counter++;
      }
    }

    if ($installation_counter == 3) {
      $rows[] = [
        'data' => [
          [
            'data' => t('All needed libraries is installed successfully!'),
            'class' => 'messages messages--status',
            'colspan' => 3,
          ],
        ],
      ];
    }
    else {
    }

    $element = [
      '#theme' => 'table',
      '#header' => [t('Library'), t('Status'), t('Action')],
      '#rows' => $rows,
    ];
    return $element;
  }

}

?>