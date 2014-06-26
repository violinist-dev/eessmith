<?php

function acquia_support_update_views_data() {

  $data = array();

  $data['acquia_nspi_sites']['table']['group'] = t('SPI General Information');
  $data['acquia_nspi_sites']['table']['join'] = array(
      'node' => array(
        'left_field' => 'uuid',
        'field' => 'subscription_uuid',
      ),
  );

  $data['acquia_nspi_log_entry']['table']['group'] = t('SPI Log Data');
  $data['acquia_nspi_log_entry']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_sites',
    'left_field' => 'site_id',
    'field' => 'site_id',
	);

  $data['acquia_nspi_drupal_quantum']['table']['group'] = t('SPI Drupal Quantum');
  $data['acquia_nspi_drupal_quantum']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_log_entry',
    'left_field' => 'spi_log_id',
    'field' => 'spi_log_id',
	);

  $data['acquia_nspi_platform_lkp']['table']['group'] = t('SPI Platform Data');
  $data['acquia_nspi_platform_lkp']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_log_entry',
    'left_field' => 'spi_log_id',
    'field' => 'spi_log_id',
	);

  $data['acquia_nspi_platform']['table']['group'] = t('SPI Platform Data');
  $data['acquia_nspi_platform']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_platform_lkp',
    'left_field' => 'pl_log_id',
    'field' => 'pl_log_id',
	);

  $data['acquia_nspi_disti_log']['table']['group'] = t('SPI Distribution Information');
  $data['acquia_nspi_disti_log']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_log_entry',
    'left_field' => 'spi_log_id',
    'field' => 'spi_log_id',
	);

  $data['acquia_nspi_distributions']['table']['group'] = t('SPI Distribution Information');
  $data['acquia_nspi_distributions']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_disti_log',
    'left_field' => 'distribution_id',
    'field' => 'distribution_id',
	);

  $data['acquia_nspi_release_hashes']['table']['group'] = t('SPI Distribution Information');
  $data['acquia_nspi_release_hashes']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_disti_log',
    'left_field' => 'hashes_id',
    'field' => 'hashes_id',
	);

  $data['acquia_nspi_disti_diff_log']['table']['group'] = t('SPI Distribution Information');
  $data['acquia_nspi_disti_diff_log']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_disti_log',
    'left_field' => 'disti_log_id',
    'field' => 'disti_log_id',
	);

  $data['acquia_nspi_modules_lkp']['table']['group'] = t('SPI Module Data');
  $data['acquia_nspi_modules_lkp']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_log_entry',
    'left_field' => 'spi_log_id',
    'field' => 'spi_log_id',
	);

  $data['acquia_nspi_modules_lkp']['table']['group'] = t('SPI Module Data');
  $data['acquia_nspi_modules_lkp']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_log_entry',
    'left_field' => 'spi_log_id',
    'field' => 'spi_log_id',
	);

  $data['acquia_nspi_modules_data']['table']['group'] = t('SPI Module Data');
  $data['acquia_nspi_modules_data']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_modules_lkp',
    'left_field' => 'module_data_id',
    'field' => 'module_data_id',
	);

  $data['acquia_nspi_modules_diff']['table']['group'] = t('SPI Module Data');
  $data['acquia_nspi_modules_diff']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_modules_lkp',
    'left_field' => 'module_data_id',
    'field' => 'module_data_id',
	);

  $data['acquia_nspi_modules']['table']['group'] = t('SPI Module Data');
  $data['acquia_nspi_modules']['table']['join']['node'] = array(
		'left_table' => 'acquia_nspi_modules_lkp',
    'left_field' => 'module_id',
    'field' => 'module_id',
	);


  /*
   *
   * Distribution and Hashes table columns
   *
   */

  $data['acquia_nspi_modules_lkp']['module_data_id'] = array(
    'title' => t('Module Data ID'),
    'help' => t('The ID representing a distinct set of information for this module and site.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules_lkp']['status'] = array(
    'title' => t('Module status'),
    'help' => t('Status of module. Is \'enabled\' or blank.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules']['module_id'] = array(
    'title' => t('Module ID'),
    'help' => t('A unique ID representing a specific version of a specific module.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules']['name'] = array(
    'title' => t('Module name'),
    'help' => t('Name of the module.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules']['version'] = array(
    'title' => t('Module version'),
    'help' => t('The version of this module.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules']['package'] = array(
    'title' => t('Module package'),
    'help' => t('Package this module belongs to.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );


  $data['acquia_nspi_modules']['project'] = array(
    'title' => t('Project name'),
    'help' => t('Project name this module belongs to.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules']['newer_release'] = array(
    'title' => t('Newer Release'),
    'help' => t('Yes or no for whether or not there is a newer release of this module.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules']['fixes_bugs'] = array(
    'title' => t('Fixes bugs'),
    'help' => t('Yes or no for whether or not this version was released to fix bugs.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules']['new_features'] = array(
    'title' => t('New features'),
    'help' => t('Yes or no for whether or not this version was released with new features.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules']['fixes_security'] = array(
    'title' => t('Security fixes'),
    'help' => t('Yes or no for whether or not this version was released to fix security issues.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules']['exists_on_do'] = array(
    'title' => t('Drupal.org module'),
    'help' => t('Yes or no for whether or not this module version exists on Drupal.org.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules_data']['has_diff'] = array(
    'title' => t('Module has diff'),
    'help' => t('Yes, no or unknown for whether or not this module has a diff.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  
  $data['acquia_nspi_modules_diff']['filename'] = array(
    'title' => t('Diff file path'),
    'help' => t('Path to file with diff.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  
  $data['acquia_nspi_modules_diff']['diff'] = array(
    'title' => t('Diff contents'),
    'help' => t('Diff between file and official release, or \'new file\' if the file is entirely new and there is no diff.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_modules_diff']['diff_exists'] = array(
    'title' => t('Diff exists'),
    'help' => t('Yes or no for whether or not the diff column contains a diff or other notation.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );



  /*
   *
   * Distribution and Hashes table columns
   *
   */

  $data['acquia_nspi_disti_log']['distribution_id'] = array(
    'title' => t('Distribution ID'),
    'help' => t('The ID the distribution used on the site.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_disti_log']['disti_changed'] = array(
    'title' => t('Distribution changed'),
    'help' => t('Yes or no for whether or not the distribution code has been altered.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_distributions']['distribution_name'] = array(
    'title' => t('Distribution name'),
    'help' => t('Distribution name from the distributions table.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_release_hashes']['hashes_id'] = array(
    'title' => t('Hashes ID'),
    'help' => t('ID of the hash for this distribution/release.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_release_hashes']['base_version'] = array(
    'title' => t('Base Drupal version'),
    'help' => t('Base version of Drupal used for this distribution.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_release_hashes']['version'] = array(
    'title' => t('Distribution version'),
    'help' => t('Distribution\'s version number.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_disti_diff_log']['path'] = array(
    'title' => t('Changed path'),
    'help' => t('Path of the file or directory which is changed.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_disti_diff_log']['diff_type'] = array(
    'title' => t('Change type'),
    'help' => t('Type of change for the file or directory.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );



  /*
   *
   * Site table columns
   *
   */

  $data['acquia_nspi_sites']['site_id'] = array(
    'title' => t('Site ID'),
    'help' => t('The ID of a site communicating with SPI.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_sites']['host'] = array(
    'title' => t('Site Host Name'),
    'help' => t('The host name (domain) of the site communicting with SPI.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_sites']['ip'] = array(
    'title' => t('Site IP Address'),
    'help' => t('The ip address (domain) of the site communicting with SPI.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_sites']['name'] = array(
    'title' => t('Site Custom Name'),
    'help' => t('Custom name set to identify this host.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );


  /*
   *
   * General logging table columns
   *
   */

  $data['acquia_nspi_log_entry']['spi_log_id'] = array(
    'title' => t('Log entry ID'),
    'help' => t('ID of the site log entry.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_log_entry']['time'] = array(
    'title' => t('Log time'),
    'help' => t('Time of the log entry.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
  );
  
  $data['acquia_nspi_log_entry']['quantum_saved'] = array(
    'title' => t('Quantum Saved'),
    'help' => t('True or False for whether or not this log entry saved quantum data.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
    
  $data['acquia_nspi_log_entry']['module_disti_saved'] = array(
    'title' => t('Module, Disti, Platform Saved'),
    'help' => t('True or False for whehter or not this log entry saved module, distribution and platform data.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );


  /*
   *
   * Quantum table columns
   *
   */

  $data['acquia_nspi_drupal_quantum']['distribution'] = array(
    'title' => t('Drupal distribution'),
    'help' => t('The name of the detected Drupal distribution.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['base_version'] = array(
    'title' => t('Base Drupal version'),
    'help' => t('The base version the installation of Drupal is running on.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['node_count'] = array(
    'title' => t('Node count'),
    'help' => t('The total number of nodes on the site.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['user_count'] = array(
    'title' => t('User count'),
    'help' => t('The total number of users on the site.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['comment_count'] = array(
    'title' => t('Comment count'),
    'help' => t('The total number of comments on the site.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['last_cron'] = array(
    'title' => t('Time of last cron'),
    'help' => t('Time of the previous cron run.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['watchdog_size'] = array(
    'title' => t('Watchdog size'),
    'help' => t('The number of rows in the watchdog table.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['extra_files_exist'] = array(
    'title' => t('Do extra files exist'),
    'help' => t('Yes or no for whether or not extra files exist.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['ssl_login_required'] = array(
    'title' => t('SSL login required'),
    'help' => t('Yes or no for whether or not ssl login is required.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['settings_read_only'] = array(
    'title' => t('Settings file is read only.'),
    'help' => t('Yes or no for whether or not the settings file is read only.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['super_user_name_strong'] = array(
    'title' => t('Admin name is strong.'),
    'help' => t('Yes or no for whether or not the admin name is strong.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['via_ssl'] = array(
    'title' => t('SSL data delivery.'),
    'help' => t('Yes or no for whether or not the data was delivered via SSL.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_drupal_quantum']['admin_user_count'] = array(
    'title' => t('Admin user count.'),
    'help' => t('Number of users on site with admin-level permissions (the super user may not be counted in this list if they have no roles).'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );



  /*
   *
   * Platform table columns
   *
   */

  $data['acquia_nspi_platform_lkp']['pl_log_id'] = array(
    'title' => t('Platform log ID'),
    'help' => t('Log ID of the platform record.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_platform']['system_type'] = array(
    'title' => t('Server OS type'),
    'help' => t('The OS of the server that Drupal is running on.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_platform']['system_version'] = array(
    'title' => t('Server OS Version'),
    'help' => t('The OS version and misc info for the server Drupal is running on.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_platform']['php'] = array(
    'title' => t('Server PHP version'),
    'help' => t('Version of PHP the server uses.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_platform']['database_type'] = array(
    'title' => t('Server database type'),
    'help' => t('The database type the Drupal install uses.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_platform']['database_version'] = array(
    'title' => t('Server database version'),
    'help' => t('The version of the database software Drupal uses.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_platform']['webserver_type'] = array(
    'title' => t('Webserver type'),
    'help' => t('The type of webserver Drupal is running on.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['acquia_nspi_platform']['webserver_version'] = array(
    'title' => t('Webserver version'),
    'help' => t('The version of the webserver Drupal is running on.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );


  /*
   *
   * Disti table columns
   *
   */



  return $data;
  
}
