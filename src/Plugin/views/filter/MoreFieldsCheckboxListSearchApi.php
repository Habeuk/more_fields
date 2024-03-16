<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Component\Utility\Timer;
use Drupal\mysql\Driver\Database\mysql\Select;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("more_fields_checkbox_list")
 */
class MoreFieldsCheckboxListSearchApi extends TaxonomyIndexTid {
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
  use MoreFieldsBaseFilter;
  
  protected function defineOptions() {
    $options = parent::defineOptions();
    
    $options['type'] = [
      'default' => 'select'
    ];
    $options['show_entities_numbers'] = [
      'default' => true
    ];
    $options['filter_by_current_term'] = [
      'default' => false
    ];
    return $options;
  }
  
  /**
   * Sanitizes the HTML select element's options.
   *
   * The function is recursive to support optgroups.
   */
  protected function prepareFilterSelectOptions(&$options) {
    // On retourne les données sans les filtrées risque de securitée.
  }
  
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    // on ajoute la possibilite d'afficher ou pas le nombre d'entité
    $form['show_entities_numbers'] = [
      '#type' => 'checkbox',
      '#title' => "Affiche le nombre d'entité par termes",
      '#default_value' => $this->options['show_entities_numbers']
    ];
    $form['filter_by_current_term'] = [
      '#type' => 'checkbox',
      '#title' => "Filtre en fonction du terme taxonomie",
      '#default_value' => $this->options['filter_by_current_term'],
      '#description' => "Permet de filtrer en fonction de la page en court si cette derniere est un terme taxonomie"
    ];
  }
  
  /**
   * Copier de la ersion : Drupal core 9.5.9
   *
   * {@inheritdoc}
   * @see \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid::valueForm()
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // if ($this->realField == "field_angle_de_vision")
    // dump($this->realField, $this->options);
    $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
    if (empty($vocabulary) && $this->options['limit']) {
      $form['markup'] = [
        '#markup' => '<div class="js-form-item form-item">' . $this->t('An invalid vocabulary is selected. Please change it in the options.') . '</div>'
      ];
      return;
    }
    
    if ($this->options['type'] == 'textfield') {
      $terms = $this->value ? Term::loadMultiple(($this->value)) : [];
      $form['value'] = [
        '#title' => $this->options['limit'] ? $this->t('Select terms from vocabulary @voc', [
          '@voc' => $vocabulary->label()
        ]) : $this->t('Select terms'),
        '#type' => 'textfield',
        '#default_value' => EntityAutocomplete::getEntityLabels($terms)
      ];
      
      if ($this->options['limit']) {
        $form['value']['#type'] = 'entity_autocomplete';
        $form['value']['#target_type'] = 'taxonomy_term';
        $form['value']['#selection_settings']['target_bundles'] = [
          $vocabulary->id()
        ];
        $form['value']['#tags'] = TRUE;
        $form['value']['#process_default_value'] = FALSE;
      }
    }
    else {
      // Add custom code.
      $terms = [];
      $tids = $this->FilterCountEntitiesHasterm();
      
      if ($tids) {
        $terms = Term::loadMultiple($tids);
      }
      // End custom code
      if (!empty($this->options['hierarchy']) && $this->options['limit']) {
        
        $tree = $this->termStorage->loadTree($vocabulary->id(), 0, NULL, TRUE);
        
        $options = [];
        if ($tree) {
          foreach ($tree as $term) {
            if (!$term->isPublished() && !$this->currentUser->hasPermission('administer taxonomy')) {
              continue;
            }
            $tid = $term->id();
            // verification de l'affichage du terme.
            if (empty($tids[$tid])) {
              continue;
            }
            
            $choice = new \stdClass();
            $label = str_repeat('-', $term->depth) . \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
            if (!empty($this->countsTerms[$tid])) {
              // on doit configurer cela, afin de pouvoir l'ajouter ou pas.
              // on peut faire cela avec before et after.
              // $label .= ' <span> (' . $this->countsTerms[$tid] . ')</span> ';
              $label .= ' <span> ' . $this->countsTerms[$tid] . '</span> ';
            }
            $choice->option = [
              $tid => $label
            ];
            $options[] = $choice;
          }
        }
      }
      else {
        $options = [];
        $query = \Drupal::entityQuery('taxonomy_term')->accessCheck(TRUE)->
        // @todo Sorting on vocabulary properties -
        // https://www.drupal.org/node/1821274.
        sort('weight')->sort('name')->addTag('taxonomy_term_access');
        if (!$this->currentUser->hasPermission('administer taxonomy')) {
          $query->condition('status', 1);
        }
        if ($this->options['limit']) {
          $query->condition('vid', $vocabulary->id());
        }
        
        foreach ($terms as $term) {
          // On ajoute le nombre de valeur
          if ($this->options['show_entities_numbers'] && $this->countsTerms) {
            $tid = $term->id();
            $label = \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
            if (!empty($this->countsTerms[$tid])) {
              // on doit configurer cela, afin de pouvoir l'ajouter ou pas.
              // on peut faire cela avec before et after.
              // $label .= ' <span> (' . $this->countsTerms[$tid] . ')</span> ';
              $label .= ' <span> ' . $this->countsTerms[$tid] . '</span> ';
            }
            $options[$tid] = $label;
          }
          else
            $options[$term->id()] = \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
        }
      }
      
      $default_value = (array) $this->value;
      
      if ($exposed = $form_state->get('exposed')) {
        $identifier = $this->options['expose']['identifier'];
        
        if (!empty($this->options['expose']['reduce'])) {
          $options = $this->reduceValueOptions($options);
          
          if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
            $default_value = [];
          }
        }
        
        if (empty($this->options['expose']['multiple'])) {
          if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce']))) {
            $default_value = 'All';
          }
          elseif (empty($default_value)) {
            $keys = array_keys($options);
            $default_value = array_shift($keys);
          }
          // Due to #1464174 there is a chance that array('') was saved in the
          // admin ui.
          // Let's choose a safe default value.
          elseif ($default_value == [
            ''
          ]) {
            $default_value = 'All';
          }
          else {
            $copy = $default_value;
            $default_value = array_shift($copy);
          }
        }
      }
      
      $form['value'] = [
        '#type' => 'select',
        '#title' => $this->options['limit'] ? $this->t('Select terms from vocabulary @voc', [
          '@voc' => $vocabulary->label()
        ]) : $this->t('Select terms'),
        '#multiple' => TRUE,
        '#options' => $options,
        '#size' => min(9, count($options)),
        '#default_value' => $default_value
      ];
      
      $user_input = $form_state->getUserInput();
      if ($exposed && isset($identifier) && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $default_value;
        $form_state->setUserInput($user_input);
      }
    }
    
    if (!$form_state->get('exposed')) {
      // Retain the helper option
      $this->helper->buildOptionsForm($form, $form_state);
      
      // Show help text if not exposed to end users.
      $form['value']['#description'] = $this->t('Leave blank for all. Otherwise, the first selected term will be the default instead of "Any".');
    }
  }
  
  /**
   * Contruit les requetes de la vue à partir du filtre.
   */
  public function FilterCountEntitiesHasterm() {
    // Timer::start('FilterCountEntitiesHasterm');
    $tids = [];
    /**
     * Contient les informations sur chaque filtre.
     * On va ajouter les filtres statiques et aussi ajouter les filtre passé
     * en paramettre via les filtres exposés.
     *
     * @var array $filters
     */
    $filters = $this->view->filter;
    if ($filters) {
      
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
        $this->buildFilterExposedQueryByViewsJoin($select_query, $filters, $table_field, 'item_id', $exposed_inputs);
      
      if (!empty($this->view->argument))
        $this->buildFilterArguments($select_query, $this->view->argument, $this->view->args, $table_field, 'item_id');
      
      // apply views_substitutions
      \Drupal::moduleHandler()->loadInclude('views', "module");
      views_query_views_alter($select_query);
      //
      
      $entities = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      // dump($this->realField, $entities);
      foreach ($entities as $value) {
        $this->countsTerms[$value[$this->realField]] = $value[$this->alias_count];
        $tids[$value[$this->realField]] = $value[$this->realField];
      }
      // dump(Timer::stop('FilterCountEntitiesHasterm'));
    }
    return $tids;
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
  protected function buildFilterExposedQueryByViewsJoin(\Drupal\Core\Database\Query\Select &$select_query, array $filters, string $base_table, string $field_id, array $exposed_inputs) {
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
        $this->buildCondition($select_query, $table, $currentFilter->realField, $value, $currentFilter->operator);
      }
    }
  }
  
  /**
   * // on surcharge afin de tenir compte de specificité de searchApi.
   * Construit les requetes statiques.
   * ( permet d'ajouter ce prendre en compte les filtres definie au niveau de la
   * vue ).
   */
  protected function buildStaticQueryByViewsJoin(&$select_query, array $filters, string $base_table) {
    foreach ($filters as $currentFilter) {
      /**
       *
       * @var \Drupal\views\Plugin\views\filter\FilterPluginBase $currentFilter
       */
      if ($currentFilter->options['exposed'] === FALSE) {
        $table = $this->getTableNameFromIndex($currentFilter->table);
        // Le cas ou le champs est inclus dans la table principal.
        if ($select_query->hasTag('more_fields_checkbox_list__' . $table)) {
          $this->buildCondition($select_query, $table, $currentFilter->realField, $currentFilter->options['value'], $currentFilter->operator);
        }
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
  
  protected function exposedTranslate(&$form, $type) {
    parent::exposedTranslate($form, $type);
    // les types radios et checkboxes ne fonctionnent pas correctement use
    // better_exposed_filters.
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
  
}