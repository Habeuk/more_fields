<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\mysql\Driver\Database\mysql\Select;
use Drupal\Component\Utility\Timer;

/**
 *
 * @author stephane
 * @deprecated supprimé une fois que les choses seront ok.
 *            
 */
trait MoreFieldsTests {
  
  /**
   * On remarque qu'il faut construire les filtres.
   *
   * @deprecated
   */
  protected function getRequetWithfilter3() {
    
    /**
     *
     * @var \Drupal\search_api\Plugin\views\query\SearchApiQuery $SearchApiQuery
     */
    $SearchApiQuery = &$this->view->query;
    if (!$SearchApiQuery)
      $this->view->initQuery();
    //
    // dump($SearchApiQuery->getWhere());
    $this->view->_build('relationship');
    $this->view->_build('filter');
    // $SearchApiQuery->execute($this->view);
    dd($SearchApiQuery);
  }
  
  /**
   * Pour avoir condition dans la requete, il faut faire le build.
   * On essaie de build à partir de la requette cela ne fonctionne pas il manque
   * le where.
   *
   * @deprecated
   */
  protected function getRequetWithfilter2() {
    /**
     *
     * @var \Drupal\search_api\Plugin\views\query\SearchApiQuery $SearchApiQuery
     */
    $SearchApiQuery = &$this->view->query;
    if (!$SearchApiQuery)
      $this->view->initQuery();
    //
    // dump('field custom build');
    $SearchApiQuery->build($this->view);
    $query = $SearchApiQuery->query();
  }
  
  /**
   * On essaie de determiner la requete qui contient deja le filtre.
   *
   * @deprecated
   */
  protected function getRequetWithfilter() {
    /**
     *
     * @var \Drupal\search_api\Plugin\views\query\SearchApiQuery $SearchApiQuery
     */
    $SearchApiQuery = $this->query;
    /**
     * NB : $SearchApiQuery, cest pas toujours definit.
     *
     * @var \Drupal\search_api\Query\Query $query
     */
    if ($SearchApiQuery) {
      // cet appelle ne contient pas condition.
      $query = $SearchApiQuery->query();
    }
  }
  
  /**
   * Il est assez difficile de partir de requetes de views afin d'obenir notre
   * filtre.
   * On doit chager la table en cours , left join de la table principal tout en
   * comptant les items.
   */
  protected function getRequetWithfilter4() {
    Timer::start('getRequetWithfilter4');
    /**
     * Contient les informations sur chaque filtre.
     * On va ajouter les filtres statiques et aussi ajouter les filtre passé
     * en paramettre via les filtres exposés.
     *
     * @var array $filters
     */
    $filters = $this->view->filter;
    $currentFilter = isset($filters[$this->realField]) ? $filters[$this->realField] : NULL;
    
    $base_table = $this->getTableNameFromIndex($this->table);
    $table_field = $base_table . '_' . $this->realField;
    /**
     *
     * @var Select $select_query
     */
    $select_query = \Drupal::database()->select($table_field, $table_field);
    $select_query->addField($table_field, 'value', $this->realField);
    
    // On ajoute la table dans les tags et on y ajoute l'id du pludin afin
    // d'eviter que d'autre module sy connecte.
    $select_query->addTag('more_fields_checkbox_list__' . $table_field);
    // On filtre les termes ayant au moins un parent.
    $configuration = [
      'type' => 'INNER',
      'table' => $table_field,
      'field' => 'item_id',
      'left_table' => $base_table,
      'left_field' => 'item_id',
      'extra_operator' => 'AND',
      'adjusted' => true
    ];
    $this->buildQueryJoin($select_query, $configuration);
    $select_query->addExpression("count($table_field.value)", $this->alias_count);
    $select_query->groupBy($table_field . '.value');
    // Add all query substitutions as metadata.
    $select_query->addMetaData('views_substitutions', $this->buildViewsQuerySubstitutions());
    $this->buildStaticQueryByViewsJoin($select_query, $filters, $table_field);
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
    //
    dump($select_query->__toString());
    dump($select_query->execute()->fetchAll(\PDO::FETCH_ASSOC));
    dump(Timer::stop('getRequetWithfilter4'));
  }
  
}