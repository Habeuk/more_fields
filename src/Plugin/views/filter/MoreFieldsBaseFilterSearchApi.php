<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\mysql\Driver\Database\mysql\Select;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;

/**
 * Ficher de base poour les filtres issue de search_api.
 *
 * @author stephane
 *        
 */
trait MoreFieldsBaseFilterSearchApi {
  use MoreFieldsBaseFilter;
  
  /**
   * Construit la reuete de base.
   *
   * @return \Drupal\mysql\Driver\Database\mysql\Select
   */
  protected function buildBaseQuery() {
    // dump($filters);
    $base_table = $this->getTableNameFromIndex($this->table);
    $table_field = $base_table . '_' . $this->realField;
    /**
     *
     * @var Select $select_query
     */
    $select_query = \Drupal::database()->select($base_table, $base_table);
    // $select_query->addField($base_table, 'item_id');
    
    // On ajoute la table dans les tags et on y ajoute l'id du pludin afin
    // d'eviter que d'autre module s'y connecte.
    $select_query->addTag('more_fields_checkbox_list__' . $base_table);
    // On filtre les termes ayant au moins un parent.
    $configuration = [
      'type' => 'INNER',
      'table' => $base_table,
      'field' => 'item_id',
      'left_table' => $table_field,
      'left_field' => 'item_id',
      'extra_operator' => 'AND',
      'adjusted' => true
    ];
    $field_settings = $this->getIndexFromCurrentTable()->get("field_settings");
    
    if ($field_settings[$this->realField]['type'] === 'text') {
      // $this->buildQueryJoin($select_query, $configuration);
      $select_query->addField($base_table, $this->realField, $this->realField);
      $select_query->addExpression("count($base_table.$this->realField)", $this->alias_count);
      $select_query->groupBy($base_table . '.' . $this->realField);
    }
    else {
      $this->buildQueryJoin($select_query, $configuration);
      $select_query->addField($table_field, "value", $this->realField);
      $select_query->addExpression("count($table_field.value)", $this->alias_count);
      $select_query->groupBy($table_field . '.value');
    }
    
    // Add all query substitutions as metadata.
    $select_query->addMetaData('views_substitutions', $this->buildViewsQuerySubstitutions());
    return $select_query;
  }
  
  /**
   * vue renvoit les tables suivant le scheme : search_api_index_{id_index} or
   * la table reelle est search_api_db_{id_index};
   *
   * @param string $table
   */
  protected function getTableNameFromIndex($table) {
    // explode("search_api_index_", $table);
    if (str_starts_with($table, 'search_api_index_')) {
      $index_id = substr($table, 17);
      return "search_api_db_" . $index_id;
    }
    throw new \Exception("Impossible de determiner la table");
  }
  
  /**
   * On ajoute les filtres exposed ayant des valeurs.
   *
   * @param \Drupal\Core\Database\Query\Select $query
   * @param array $filters
   * @param string $base_table
   * @param string $field_id
   * @param array $exposed_inputs
   */
  protected function buildFilterExposedQueryByViewsJoin(Select &$select_query, array $filters, string $base_table, string $field_id, array $exposed_inputs) {
    foreach ($exposed_inputs as $filterId => $value) {
      if (!empty($filters[$filterId])) {
        /**
         *
         * @var \Drupal\views\Plugin\views\filter\FilterPluginBase $currentFilter
         */
        $currentFilter = $filters[$filterId];
        $table = $this->getTableNameFromIndex($currentFilter->table);
        $configuration = [
          'type' => 'INNER',
          'table' => $base_table,
          'field' => 'item_id',
          'left_table' => $table,
          'left_field' => $field_id,
          'extra_operator' => 'AND',
          'adjusted' => true
        ];
        $table = $this->getTableNameFromIndex($currentFilter->table);
        /**
         *
         * @var \Drupal\views\Plugin\views\join\Standard $instance
         */
        if (!$select_query->hasTag('more_fields_checkbox_list__' . $table)) {
          $this->buildQueryJoin($select_query, $configuration);
        }
        if (!($this->options['ignore_default_value'] && $currentFilter->realField == $this->realField))
          $this->buildCondition($select_query, $table, $currentFilter->realField, $value, $currentFilter->operator);
      }
    }
  }
  
  /**
   *
   * @return \Drupal\search_api\Entity\Index
   */
  protected function getIndexFromCurrentTable() {
    return SearchApiQuery::getIndexFromTable($this->view->storage->get('base_table'));
  }
  
}