<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("more_fields_checkbox_list")
 */
class MoreFieldsCheckboxList extends TaxonomyIndexTid {
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
      if (!empty($this->options['hierarchy']) && $this->options['limit']) {
        $tree = $this->termStorage->loadTree($vocabulary->id(), 0, NULL, TRUE);
        $options = [];
        if ($tree) {
          foreach ($tree as $term) {
            if (!$term->isPublished() && !$this->currentUser->hasPermission('administer taxonomy')) {
              continue;
            }
            $choice = new \stdClass();
            $choice->option = [
              $term->id() => str_repeat('-', $term->depth) . \Drupal::service('entity.repository')->getTranslationFromContext($term)->label()
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
        // Add custom code.
        $queryEntity = $this->FilterCountEntitiesHasterm();
        if ($queryEntity) {
          $this->FilterTermHasContent($query, $queryEntity);
        }
        // $this->messenger()->addStatus($query->__toString(), true);
        // End custom code.
        $terms = Term::loadMultiple($query->execute());
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
   * Contruit les requtes de la vue à partir du filtre.
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
  
  /**
   * On va selectionner les entités qui possedent un terme dans le champs en
   * question, les groupes par tid, ensuite recuperer la liste des tids.
   *
   * @param \Drupal\Core\Entity\Query\Sql\Query $query
   * @see https://drupal.stackexchange.com/questions/184411/entityquery-group-by-clause
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
  
  protected function exposedTranslate(&$form, $type) {
    parent::exposedTranslate($form, $type);
    // les types radios et checkboxes ne fonctionnent pas correctement use
    // better_exposed_filters.
  }
  
}