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
    $element = array(
      '#markup' => 'Presets page goes here',
    );
    return $element;
  }
  
  /**
   * Returns the library download page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function downloadLibraries() {
    
    module_load_include('module', 'wif');
    
    // Load wif.settings configuration
    $wif_settings = \Drupal::config('wif.settings');
    // Get needed libraries array with information about the libraries
    $libraries = $wif_settings->get('needed_libraries');
    
    // Get real path to the js files
    foreach($libraries as $library_name => $library_settings) {
      $libraries[$library_name]['path'] = _wif_get_path($library_name, $library_settings['path_to_js']);
    }
    
    // Create table rows for the libraries
    $rows = array();
    $installation_counter = 0;
    foreach($libraries as $library_name => $library) {
      $rows[] = array(
        'data' => array(
          $library['name'],
          array(
            'data' => ($library['path'])? t('Installed successfully!') : t('Library is not found.'),
            'class' => ($library['path'])? 'messages messages--status' : 'messages messages--error',
          ),
          'Install button goes here',
        ),
      );
      
      // If this library is installed, increase the installation_counter
      if ($library['path']) $installation_counter++;
    }
    
    if ($installation_counter == 3) {
      $rows[] = array(
        'data' => array(
          array(
            'data' => t('All needed libraries is installed successfully!'),
            'class' => 'messages messages--status',
            'colspan' => 3,
          ),
        ),
      );
    } else {
      
    }
    
    $element = array(
      '#theme' => 'table',
      '#header' => array(t('Library'), t('Status'), t('Action')),
      '#rows' => $rows,
    );
    return $element;
  }

}
?>