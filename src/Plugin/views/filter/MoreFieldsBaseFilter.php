<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\mysql\Driver\Database\mysql\Select;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Ficher de base poour les filtres vues.
 *
 * @author stephane
 *        
 */
trait MoreFieldsBaseFilter {
  
  /**
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $ViewsHandlerManager;
  
  /**
   * Le clé alias qui va stoker le nombre de valeur.
   *
   * @var string
   */
  protected $alias_count = 'count_termes';
  
  /**
   * Contient nombre d'entites par terms.
   *
   * @var array
   */
  protected $countsTerms = [];
  
  /**
   *
   * @var array
   */
  protected $ViewsQuerySubstitutions = [];
  
  /**
   *
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    
    $options['type'] = [
      'default' => 'select'
    ];
    $options['show_entities_numbers'] = [
      'default' => true
    ];
    // igonre la valeur selectionnée.
    $options['ignore_default_value'] = [
      'default' => false
    ];
    return $options;
  }
  
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    // on ajoute la possibilite d'afficher ou pas le nombre d'entité
    $form['show_entities_numbers'] = [
      '#type' => 'checkbox',
      '#title' => "Affiche le nombre d'entité par termes",
      '#default_value' => $this->options['show_entities_numbers']
    ];
    $form['ignore_default_value'] = [
      '#type' => 'checkbox',
      '#title' => "Ignore la valeur selectionnée",
      '#default_value' => $this->options['ignore_default_value'],
      '#description' => "Cela permet aux termes de fonctionner un peu comme un menu"
    ];
  }
  
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
    // dump($alias . '.' . $field, $value, $operator);
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
  
  protected function buildAnothersQuery(select $select_query) {
    $filters = $this->buildValidFilters();
    $base_table = $this->getTableNameFromIndex($this->table);
    $this->buildStaticQueryByViewsJoin($select_query, $filters, $base_table);
    $exposed_inputs = $this->view->getExposedInput();
    if ($exposed_inputs)
      $this->buildFilterExposedQueryByViewsJoin($select_query, $filters, $base_table, 'item_id', $exposed_inputs);
    
    if (!empty($this->view->argument))
      $this->buildFilterArguments($select_query, $this->view->argument, $this->view->args, $base_table, 'item_id');
    
    // apply views_substitutions
    \Drupal::moduleHandler()->loadInclude('views', "module");
    views_query_views_alter($select_query);
  }
  
  /**
   *
   * @return array
   */
  protected function buildValidFilters() {
    /**
     * Contient les informations sur chaque filtre.
     * On va ajouter les filtres statiques et aussi ajouter les filtre passé
     * en paramettre via les filtres exposés.
     *
     * @var array $filters
     */
    $defaultFilters = $this->view->filter;
    $filters = [];
    if ($defaultFilters) {
      foreach ($defaultFilters as $currentFilter) {
        //
        if ($currentFilter->getPluginId() == $this->pluginId || !empty($currentFilter->options['exposed'])) {
          $filters[$currentFilter->realField] = $currentFilter;
        }
      }
    }
    return $filters;
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
  
  /**
   *
   * @return \Drupal\search_api\Entity\Index
   */
  protected function getIndexFromCurrentTable() {
    return SearchApiQuery::getIndexFromTable($this->view->storage->get('base_table'));
  }
  
  /**
   * On va selectionner les entités qui possedent un terme dans le champs en
   * question, les groupes par tid, ensuite recuperer la liste des tids.
   *
   * @param \Drupal\Core\Entity\Query\Sql\Query $query
   * @see https://drupal.stackexchange.com/questions/184411/entityquery-group-by-clause
   * @deprecated car n'est plus utiliser
   */
  protected function FilterTermHasContent(QueryInterface &$query, \Drupal\mysql\Driver\Database\mysql\Select $queryEntity) {
    $entities = $queryEntity->execute()->fetchAll(\PDO::FETCH_ASSOC);
    if ($entities) {
      $tids = [];
      foreach ($entities as $value) {
        $tids[] = $value[$this->configuration['field']];
        $this->countsTerms[$value[$this->configuration['field']]] = $value[$this->alias_count];
      }
      
      $query->condition('tid', $tids, 'IN');
    }
    else {
      // S'il nya pas de correspondance, on vide la requete.
      // ( on verra si on peut faire cela autrement ).
      $query->condition('tid', null, "IS NULL");
    }
  }
  
  /**
   * Contruit les requetes de la vue à partir du filtre.
   * Ancinne approche,
   */
  public function FilterCountEntitiesHasterm() {
    /**
     * Le nom de la colonne utile.
     *
     * @var string $colomn_name
     */
    $colomn_name = $this->configuration['field'];
    /**
     * Contient les informations sur chaque filtre.
     * On va ajouter les filtres statiques et aussi ajouter les filtre passé
     * en paramettre via les filtres exposés.
     *
     * @var array $filters
     */
    $filters = $this->view->filter;
    
    $base_table = $this->view->storage->get('base_table');
    $field_id = $this->view->storage->get('base_field');
    $this->view->initDisplay();
    /**
     *
     * @var \Drupal\views\Plugin\views\filter\FilterPluginBase $currentFilter
     */
    $currentFilter = isset($filters['more_fields_' . $colomn_name]) ? $filters['more_fields_' . $colomn_name] : NULL;
    if ($currentFilter) {
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
        'relationship' => $this->view->storage->get('base_table')
      ];
      // constructions à partir de l'object
      /**
       *
       * @var \Drupal\mysql\Driver\Database\mysql\Select $select_query
       */
      $select_query = \Drupal::database()->select($base_table, $base_table);
      $select_query->fields($base_table, [
        $field_id
      ]);
      // On ajoute la table dans les tags et on y ajoute l'id du pludin afin
      // d'eviter que d'autre module sy connecte.
      $select_query->addTag('more_fields_checkbox_list__' . $base_table);
      if (!$this->view->query)
        $this->view->getQuery();
      /**
       *
       * @var \Drupal\views\Plugin\views\join\Standard $instance
       */
      $instance = $this->initViewsJoin()->createInstance("standard", $configuration);
      $instance->buildJoin($select_query, $table, $this->view->query);
      //
      $select_query->addField($table['alias'], $colomn_name);
      $select_query->addExpression("count($table[alias].$colomn_name)", $this->alias_count);
      $select_query->groupBy($table['alias'] . '.' . $colomn_name);
      $select_query->addTag('more_fields_checkbox_list__' . $currentFilter->table);
      // Add all query substitutions as metadata.
      $select_query->addMetaData('views_substitutions', $this->buildViewsQuerySubstitutions());
      // build orther query.
      $this->buildStaticQueryByViewsJoin($select_query, $filters, $base_table);
      /**
       * Tableau contennant les valeurs deja selectionner par l'utilisateur.
       *
       * @var array $exposed_inputs
       */
      $exposed_inputs = $this->view->getExposedInput();
      if ($exposed_inputs)
        $this->buildFilterExposedQueryByViewsJoin($select_query, $filters, $base_table, $field_id, $exposed_inputs);
      if (!empty($this->view->argument))
        $this->buildFilterArguments($select_query, $this->view->argument, $this->view->args, $base_table, $field_id);
      
      // apply views_substitutions
      \Drupal::moduleHandler()->loadInclude('views', "module");
      views_query_views_alter($select_query);
      return $select_query;
    }
  }
  
}