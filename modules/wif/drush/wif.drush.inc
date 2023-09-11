<?php

/**
 * @file
 *   Drush integration for the libraries.
 */
 
/**
 * Implements hook_drush_command().
 */
function wif_drush_command() {
  $items = array();
  
  // The key in the $items array is the name of the command.
  $items['wif-library'] = array(
    'callback' => 'drush_wif_library',
    'description' => dt('Download and install libraries.'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL, // No bootstrap.
    'arguments' => array(
      'library' => dt('Optional. If a library name is given, only that library will be installed. Otherwise you will be asked, what you want to install.'),
    ),
  );

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function wif_drush_help($section) {
  switch ($section) {
    case 'drush:wif-library':
      return dt('Download and install the libraries. Default location is /libraries.');
  }
}

/**
 * Command to download the libraries.
 */
function drush_wif_library() {
  
  // Load wif.settings configuration
  $wif_settings = \Drupal::config('wif.settings');
  // Get information about the libraries
  $libraries = $wif_settings->get('needed_libraries');
  
  $args = func_get_args();
  if (!empty($args[0])) {
    $library = $args[0];
  }
  else {
    $options = array('all' => dt('Install all libraries below'));
    foreach($libraries as $library_name => $library_value) {
      $options[$library_name] = $library_name;
    }
    $library = drush_choice($options, dt('Which library do you want to install?'));
  }
  
  $path = 'libraries';
  
  // Create the path if it does not exist.
  if (!is_dir($path)) {
    drush_op('mkdir', $path);
    drush_log(dt('Directory @path was created', array('@path' => $path)), 'notice');
  }

  // Set the directory to the download location.
  $olddir = getcwd();
  chdir($path);
  
  // Set witch libraries need installation
  $selected_library = array();
  if ( $library == 'all' ) {
    $selected_library = array_keys($libraries);
  } else {
    if (isset($libraries[$library])) {
      $selected_library[] = $library;
    }
  }
  
  foreach($selected_library as $library_name){

    // Download the zip archive.
    if ($filepath = drush_download_file($libraries[$library_name]['download_url'])) {
      $filename = basename($filepath);
      $dirname = basename($filepath, '.zip');

      // Remove library if it previously installed
      if (is_dir($dirname)) {
        drush_delete_dir($dirname, TRUE);
        drush_log(dt('A existing @name library was deleted from @path', array('@name' => $libraries[$library_name]['name'],'@path' => $path . '/' . $dirname)), 'notice');
      }
      
      // Remove library if it previously installed
      if (is_dir($library_name)) {
        drush_delete_dir($library_name, TRUE);
        drush_log(dt('A existing @name library was deleted from @path', array('@name' => $libraries[$library_name]['name'],'@path' => $path . '/' . $library_name)), 'notice');
      }

      // Decompress the zip archive.
      drush_tarball_extract($filename, $dirname);
      
      // Change the directory name to the $library_name if needed.
      if ($dirname != $library_name) {
        // If the library is in an unnecessary subdirectory 
        if ( isset($libraries[$library_name]['subdirectory']) ) {
          // Move the subdirectory insted the parent directory
          drush_move_dir($dirname . '/' . $libraries[$library_name]['subdirectory'], $library_name, TRUE);
          drush_delete_dir($dirname);
        } else {
          // Otherwise move the parent directory (Rename)
          drush_move_dir($dirname, $library_name, TRUE);
        }
        
        //drush_delete_dir($dirname);
        $dirname = $library_name;
      }
    }
    
    if (is_dir($dirname)) {
      drush_log(dt('@name library has been installed in @path', array('@name' => $libraries[$library_name]['name'], '@path' => $path . '/' . $dirname)), 'success');
    }
    else {
      drush_log(dt('Drush was unable to install the @name library to @path', array('@name' => $libraries[$library_name]['name'], '@path' => $path)), 'error');
    }
  }
  
  // Set working directory back to the previous working directory.
  chdir($olddir);
}
