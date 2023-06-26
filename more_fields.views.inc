<?php

/**
 *
 * @file
 * Provides views data for more_fields.module.
 */
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data_alter().
 */
function more_fields_views_data_alter(&$data) {
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (!empty($field_data['filter']) && $field_data['filter']['id'] == 'taxonomy_index_tid') {
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

/**
 * Implements hook_field_views_data_alter().
 *
 * Views integration for entity reference fields which reference taxonomy terms.
 * Adds a term relationship to the default field data.
 *
 * @see views_field_default_views_data()
 */
function more_fields_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  // if ($field_storage->getType() == 'entity_reference' &&
  // $field_storage->getSetting('target_type') == 'taxonomy_term') {
  // foreach ($data as $table_name => $table_data) {
  // foreach ($table_data as $field_name => $field_data) {
  // if (isset($field_data['filter']) && $field_name != 'delta') {
  // // Add filter CheckboxList.
  // $mf = [
  // 'title' => t('Custom filter term taxonomy'),
  // 'group' => t('More fields'),
  // 'filter' => $data[$table_name][$field_name]['filter']
  // ];
  // $mf['filter']['id'] = 'more_fields_checkbox_list';
  // $data[$table_name]['more_fields_' . $field_name] = $mf;
  // $tmpfield = $data[$table_name][$field_name];
  // // re-order table
  // unset($data[$table_name][$field_name]);
  // $data[$table_name][$field_name] = $tmpfield;
  
  // // $data[$table_name]['more_fields_' . $field_name] = [
  // // 'title' => t('Custom filter term taxonomy'),
  // // 'group' => t('More fields'),
  // // 'filter' => [
  // // 'id' => 'more_fields_checkbox_list',
  // // 'title' => 'More fields : ' . $field_storage->getLabel(),
  // // 'field' => $field_name,
  // // 'table' => $table_name,
  // // 'allow empty' => true,
  // // 'field_name' => $field_storage->getName(),
  // // // 'entity_type' => $field_storage->get),
  // // 'help' => t('Offers multiple select display format, radios,
  // // checkboxes.')
  // // ]
  // // ];
  // }
  // }
  // }
  // }
}
