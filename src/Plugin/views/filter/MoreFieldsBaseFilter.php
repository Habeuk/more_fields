<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\mysql\Driver\Database\mysql\Select;

trait MoreFieldsBaseFilter {
  
  /**
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $ViewsHandlerManager;
  
  /**
   * Permet de construire les de type joins.
   * example :
   * $configuration = [
   * 'type' => 'INNER',
   * 'table' => $table_field,
   * 'field' => 'entity_id',
   * 'left_table' => $base_table,
   * 'left_field' => 'field_id',
   * 'extra_operator' => 'AND',
   * 'adjusted' => true
   * ];
   *
   * @param Select $select
   */
  protected function buildQueryJoin(Select $select, array $configuration) {
    $select->addJoin($configuration['type'], $configuration['left_table'], $configuration['left_table'], $this->buildQueryJoinCondition($configuration));
    $select->addTag('more_fields_checkbox_list__' . $configuration['left_table']);
  }
  
  private function buildQueryJoinCondition(array $configuration) {
    return $configuration['left_table'] . '.' . $configuration['left_field'] . "=" . $configuration['table'] . '.' . $configuration['field'];
  }
  
  /**
   * Construit les requetes statiques.
   * ( permet d'ajouter ce prendre en compte les filtres definie au niveau de la
   * vue ).
   *
   * @param Select $select_query
   * @param array $filters
   * @param string $base_table
   */
  protected function buildStaticQueryByViewsJoin(Select &$select_query, array $filters, string $base_table) {
    foreach ($filters as $currentFilter) {
      /**
       *
       * @var \Drupal\views\Plugin\views\filter\FilterPluginBase $currentFilter
       */
      if ($currentFilter->options['exposed'] === FALSE) {
        $table = [
          'table' => $currentFilter->table,
          'num' => 1,
          'alias' => $currentFilter->tableAlias ? $currentFilter->tableAlias : $currentFilter->table,
          // 'join'=>
          'relationship' => $base_table
        ];
        // Le cas ou le champs est inclus dans la table principal.
        if ($select_query->hasTag('more_fields_checkbox_list__' . $currentFilter->table)) {
          $this->buildCondition($select_query, $table['alias'], $currentFilter->realField, $currentFilter->options['value'], $currentFilter->operator);
        }
      }
    }
  }
  
  protected function buildCondition(\Drupal\Core\Database\Query\Select &$select_query, $alias, $field, $value, $operator) {
    if ($operator == 'or') {
      $operator = 'in';
      // Specifique à or car les données sont censer etre dans un array.
      if (!is_array($value))
        $value = [
          $value
        ];
    }
    elseif ($operator == 'contains') {
      $operator = 'LIKE';
      $value = '%' . $select_query->escapeLike($value) . '%';
    }
    $select_query->condition($alias . '.' . $field, $value, $operator);
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
        $configuration = [
          'type' => 'INNER',
          'table' => $currentFilter->table,
          'field' => 'entity_id',
          'left_table' => $base_table,
          'left_field' => $field_id,
          'extra_operator' => 'AND',
          'adjusted' => true
        ];
        $table = [
          'table' => $currentFilter->table,
          'num' => 1,
          'alias' => $currentFilter->tableAlias ? $currentFilter->tableAlias : $currentFilter->table,
          // 'join'=>
          'relationship' => $base_table
        ];
        /**
         *
         * @var \Drupal\views\Plugin\views\join\Standard $instance
         */
        if (!$select_query->hasTag('more_fields_checkbox_list__' . $currentFilter->table)) {
          $instance = $this->initViewsJoin()->createInstance("standard", $configuration);
          $instance->buildJoin($select_query, $table, $this->view->query);
          $select_query->addTag('more_fields_checkbox_list__' . $currentFilter->table);
        }
        
        $this->buildCondition($select_query, $table['alias'], $currentFilter->realField, $value, $currentFilter->operator);
      }
    }
  }
  
  /**
   * Cette fonction n'est pas automatique, elle fonctionnera au cas par cas en
   * attandant de la rendre dynamique.
   *
   * @param \Drupal\Core\Database\Query\Select $select_query
   * @param array $arguments
   * @param string $base_table
   * @param string $field_id
   */
  protected function buildFilterArguments(\Drupal\Core\Database\Query\Select &$select_query, array $arguments, array $args, string $base_table, string $field_id) {
    $position = 0;
    // cas : $base_table == node_field_data et argument => taxonomy_index
    if ($base_table == 'node_field_data') {
      foreach ($arguments as $argument) {
        if (isset($args[$position])) {
          $arg = $args[$position];
          $position++;
        }
        // s'il nya pas d'argument on continue.
        if (!isset($arg))
          continue;
        
        $configuration = [
          'type' => 'INNER',
          'table' => $argument->table,
          'field' => 'nid',
          'left_table' => $base_table,
          'left_field' => $field_id,
          'extra_operator' => 'AND',
          'adjusted' => true
        ];
        $table = [
          'table' => $argument->table,
          'num' => 1,
          'alias' => $argument->tableAlias ? $argument->tableAlias : $argument->table,
          // 'join'=>
          'relationship' => $base_table
        ];
        /**
         *
         * @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument
         */
        if ($argument->table == 'taxonomy_index') {
          /**
           *
           * @var \Drupal\views\Plugin\views\join\Standard $instance
           */
          if (!$select_query->hasTag('more_fields_checkbox_list__' . $argument->table)) {
            $instance = $this->initViewsJoin()->createInstance("standard", $configuration);
            $instance->buildJoin($select_query, $table, $this->view->query);
            $select_query->addTag('more_fields_checkbox_list__' . $argument->table);
          }
          $this->buildCondition($select_query, $table['alias'], $argument->realField, $arg, $argument->operator);
        }
      }
    }
  }
  
  /**
   *
   * @return array
   */
  protected function buildViewsQuerySubstitutions() {
    if (!$this->ViewsQuerySubstitutions) {
      $this->ViewsQuerySubstitutions = \Drupal::moduleHandler()->invokeAll('views_query_substitutions', [
        $this->view
      ]);
    }
    return $this->ViewsQuerySubstitutions;
  }
  
  /**
   *
   * @return \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected function initViewsJoin() {
    if (!$this->ViewsHandlerManager) {
      /**
       *
       * @var \Drupal\views\Plugin\ViewsHandlerManager $ViewsHandlerManager
       */
      $this->ViewsHandlerManager = \Drupal::service('plugin.manager.views.join');
    }
    return $this->ViewsHandlerManager;
  }
  
}