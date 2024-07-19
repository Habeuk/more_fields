<?php

/**
 *
 * @file
 * Provides views data for more_fields.module.
 */
use Drupal\more_fields\Plugin\views\filter\MoreFieldsSearchApiTerm;

/**
 * Implements hook_views_data_alter().
 *
 * @deprecated il faut utliser la surcharge et voir comment mettre à jour les
 *             differentes configurations.
 */
function more_fields_views_data_alter(&$data) {
  // \Stephane888\Debug\debugLog::$max_depth = 5;
  // \Stephane888\Debug\debugLog::symfonyDebug($data,
  // 'more_fields_views_data_alter', true);
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (!empty($field_data['filter'])) {
        if ($field_data['filter']['id'] == 'taxonomy_index_tid') {
          if (!empty($data[$table_name][$field_name]['title'])) {
            $mf = [
              'title' => t('Custom filter term taxonomy'),
              'group' => t('More fields'),
              'filter' => $data[$table_name][$field_name]['filter']
            ];
            $mf['filter']['id'] = 'more_fields_checkbox_list';
            $mf['filter']['title'] = $data[$table_name][$field_name]['title'];
            $data[$table_name]['more_fields_' . $field_name] = $mf;
          }
        }
      }
    }
  }
  // \Stephane888\Debug\debugLog::kintDebugDrupal($data,
  // 'more_fields_views_data_alter', true);
}

/**
 * Implements hook_views_plugins_filter_alter().
 */
function more_fields_views_plugins_filter_alter(&$plugins) {
  // Extend plufin search_api_term.
  // Pour le filtre
  // if (isset($plugins['search_api_term'])) {
  // $plugins['search_api_term']['class'] = MoreFieldsSearchApiTerm::class;
  // }
  $plugins['search_api_term'] = [
    'plugin_type' => 'filter',
    'id' => 'search_api_term',
    'class' => MoreFieldsSearchApiTerm::class,
    'provider' => 'search_api'
  ];
}


