<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\views\Plugin\views\query\Sql;

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
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $ViewsHandlerManager;

  /**
   *
   * @var array
   */
  protected $ViewsQuerySubstitutions = [];

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
   * Filtre, compte les entites regrouper par termes.
   * Il faut ternir aussi compte du filtre encours dans la vue. (assez complique
   * de trouver cela).
   * On va construire QUery avec les informations donc on dispose.
   *
   * @see https://drupal.stackexchange.com/questions/184411/entityquery-group-by-clause
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   */
  public function FilterCountEntitiesHasterm1() {

    /**
     * Le nom de la table du terme taxonomie.
     *
     * @var string $table_term
     */
    $table_term = $this->configuration['table'];
    $base_table = $this->view->storage->get('base_table');
    /**
     * Le nom de la colonne utile.
     *
     * @var string $colomn_name
     */
    $colomn_name = $this->configuration['field'];
    /**
     *
     * @var \Drupal\views\Entity\View $storage
     */
    // $storage = $this->view->storage;

    // $this->view->query->build($this->view);
    // dd($this->view->query->query()->__toString());
    // dd($this->view->getQuery());
    // $table_alias = $this->configuration['field'];
    /**
     * Le champs de reference de l'entité selectionné.
     * ( par example entite :node, $field_id=nid ).
     *
     * @var string $field_id
     */
    $field_id = $this->view->storage->get('base_field');

    /**
     *
     * @var \Drupal\views\ViewExecutable $view
     */
    $view = $this->view;

    /**
     * Contient les informations sur chaque filtre.
     * On va ajouter les filtres statiques et aussi ajouter les filtre passé
     * en paramettre via les filtres exposés.
     *
     * @var array $filters
     */
    $filters = $view->filter;
    /**
     *
     * @var \Drupal\views\Plugin\views\filter\FilterPluginBase $currentFilter
     */
    $currentFilter = $filters['more_fields_' . $colomn_name];
    if ($currentFilter) {
      // dump($currentFilter, $filters);
      /**
       * On va construire la base de notre filtre.
       * ( NB: on a pas reussi à avoir un query avec les informations de base
       * afin de juste compléter. )
       *
       * @var \Drupal\Core\Database\Query\Select $query
       */
      $query = \Drupal::database()->select($base_table, 'base_table');
      $query->fields('base_table', [
        $field_id
      ]);
      // Afin de determiner si la table est deja presente.
      $query->addTag($base_table);
      // on ajoute le fitre encours.
      $query->addJoin('INNER', $currentFilter->table, $currentFilter->field, $currentFilter->field . '.entity_id=base_table.' . $field_id);
      $query->addField($currentFilter->field, $colomn_name);
      $query->addExpression("count($currentFilter->field.$colomn_name)", 'count_termes');
      $query->groupBy($currentFilter->field . '.' . $colomn_name);
      // Afin de determiner si la table est deja presente.
      $query->addTag($table_term);
      // dump($this->configuration, $currentFilter);
      //
      // if ($this->field == 'more_fields_field_donnees_liees_target_id') {
      // dd($query->__toString());
      // dump('result : ', $query->execute()->fetchAll(\PDO::FETCH_ASSOC));
      // dd($this->configuration);
      // $queryClone->addField($table_term, $colomn_name);
      /**
       * Cette foix on esaaie d'utiliser l'approche en dessous tout en lui
       * passant les valeurs present dans le filtre exposed.
       */

      /**
       * Tableau contennant les valeurs deja selectionner par l'utilisateur.
       *
       * @var array $exposed_inputs
       */
      $exposed_inputs = $view->getExposedInput();

      $this->buildFilterQuery($query, $filters, $base_table, $field_id, $exposed_inputs);
      return $query;
    }
  }

  /**
   * On essayer de contruire les requetes en s'appuyant sur les APIs de vues.
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
    // dd($this->view);
    /**
     *
     * @var \Drupal\views\Plugin\views\filter\FilterPluginBase $currentFilter
     */
    $currentFilter = $filters['more_fields_' . $colomn_name];
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
      // dd($this->configuration, $this->view->getHandlers('filter'),
      // $this->view->filter['more_fields_field_donnees_liees_target_id']);

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
      $select_query->addExpression("count($table[alias].$colomn_name)", 'count_termes');
      $select_query->groupBy($table['alias'] . '.' . $colomn_name);
      $select_query->addTag('more_fields_checkbox_list__' . $currentFilter->table);
      // Add all query substitutions as metadata.
      $select_query->addMetaData('views_substitutions', $this->buildViewsQuerySubstitutions());
      // build orther query.
      $this->buildStaticQueryByViewsJoin($select_query, $filters, $base_table, $field_id);
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
      // dump($currentFilter->realField . ' :: ' . "\n" .
      // $select_query->__toString());
      // dump(' result : ',
      // $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC));
      // dump($select_query);
      // dd('END');
      //
      return $select_query;
    }
  }

  protected function buildStaticQueryByViewsJoin(\Drupal\Core\Database\Query\Select &$select_query, array $filters, string $base_table, string $field_id) {
    foreach ($filters as $currentFilter) {
      /**
       *
       * @var \Drupal\views\Plugin\views\filter\FilterPluginBase $currentFilter
       */
      if ($currentFilter->options['exposed'] === FALSE) {
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
        if ($select_query->hasTag('more_fields_checkbox_list__' . $currentFilter->table)) {
          $this->buildCondition($select_query, $table['alias'], $currentFilter->realField, $currentFilter->options['value'], $currentFilter->operator);
        }
      }
    }
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

  protected function buildCondition(\Drupal\Core\Database\Query\Select &$select_query, $alias, $field, $value, $operator) {
    // dump($field, $value, $operator);
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

    // $select_query->getMetaData($key)
    // dd($field, $value, $operator);
    $select_query->condition($alias . '.' . $field, $value, $operator);
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

  protected function buildFilterQuery(\Drupal\Core\Database\Query\Select &$query, $filters, $base_table, $field_id, array $exposed_inputs) {
    /**
     *
     * @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
     */
    $ignoreField = [
      'status'
    ];
    foreach ($filters as $filter_id => $filter) {
      if (in_array($filter_id, $ignoreField)) {
        continue;
      }
      // dump($filter->operator);
      if ($filter->operator == 'contains')
        $filter->operator = '%LIKE%';

      if (!$filter->options['exposed']) {
        if ($query->hasTag($filter->options['table'])) {
          if ($filter->table == $base_table) {
            $query->condition("base_table." . $filter->realField, $filter->value, $filter->operator);
          }
          else
            $query->condition($filter->field . '.' . $filter->realField, $filter->value, $filter->operator);
        }
        else {
          $query->addJoin('INNER', $filter->table, $filter->field, $filter->field . '.entity_id=base_table.' . $field_id);
          $query->condition($filter->field . '.' . $filter->realField, $filter->value, $filter->operator);
          $query->addTag($filter->table);
        }
      }
      elseif (isset($exposed_inputs[$filter_id])) {
        if ($query->hasTag($filter->options['table'])) {
          if ($filter->table == $base_table) {
            $query->condition("base_table." . $filter->realField, $exposed_inputs[$filter_id], $filter->operator);
          }
          else {
            $query->condition($filter->field . '.' . $filter->realField, $exposed_inputs[$filter_id], $filter->operator);
          }
        }
        else {

          $query->addJoin('INNER', $filter->table, $filter->field, $filter->field . '.entity_id=base_table.' . $field_id);
          if ($filter->operator == 'or') {
            $query->condition($filter->field . '.' . $filter->realField, $exposed_inputs[$filter_id], 'IN');
          }
          else
            $query->condition($filter->field . '.' . $filter->realField, $exposed_inputs[$filter_id], $filter->operator);
          $query->addTag($filter->table);
        }
      }
    }
    // dump($query->__toString());
    // $this->messenger()->addStatus($query->__toString(), true);
    // dump($query->execute()->fetchAll(\PDO::FETCH_ASSOC));
    // dd($filters['more_fields_field_type_target_id']);
  }

  /**
   * Le but est de determiner le nom du champs dans l'entite.
   *
   *
   * @deprecated Cette approche est deprecie.
   * @param \Drupal\Core\Entity\Query\QueryAggregateInterface $queryEntity
   */
  public function filterByCurrentTerm(\Drupal\Core\Entity\Query\QueryAggregateInterface &$queryEntity) {
    $routeName = \Drupal::routeMatch()->getRouteName();
    if ($routeName == 'entity.taxonomy_term.canonical') {
      /**
       *
       * @var \Drupal\taxonomy\Entity\Term $taxonomy_term
       */
      $taxonomy_term = \Drupal::routeMatch()->getParameter('taxonomy_term');
      /**
       *
       * Cette approche n'est pas ideale car elle ne tient pas vraiment compte
       * de toutes les options dans la requetes.
       * (fonctionne dans le cas d'une seule entité).
       * On determine le bundle.
       *
       * @var string $request
       */
      $request = "SELECT bundle FROM " . $this->configuration['table'] . " limit 1";
      $query = \Drupal::database()->query($request);
      $result = $query->fetch(\PDO::FETCH_ASSOC);
      if ($result) {
        // entity.node.field_ui_fields
        /**
         *
         * @var \Drupal\node\Entity\NodeType $currentEntityType
         */
        // $currentEntityType =
        // on recupere les champs de type de type reference.
        $fields = \Drupal::entityTypeManager()->getStorage("field_config")->loadByProperties([
          'entity_type' => 'node',
          'field_type' => 'entity_reference',
          'bundle' => $result["bundle"]
        ]);
        // On recupere celui qui a pour taxonimie la valeur encours dans l'url.
        $vocabulaire = $taxonomy_term->get('vid')->target_id;
        $fieldValid = NULL;
        foreach ($fields as $field) {
          /**
           *
           * @var \Drupal\field\Entity\FieldConfig $field
           */
          $handlerSettings = $field->getSettings();
          if (!empty($handlerSettings['handler_settings']['target_bundles']) && in_array($vocabulaire, $handlerSettings['handler_settings']['target_bundles'])) {
            $fieldValid = $field;
            break;
          }
        }
        if ($fieldValid) {
          $queryEntity->condition($fieldValid->get('field_name'), $taxonomy_term->id());
        }
      }
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
    // dump($entities);
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

  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
  }

  protected function exposedTranslate(&$form, $type) {
    parent::exposedTranslate($form, $type);
    // les types radios et checkboxes ne fonctionnent pas correctement use
    // better_exposed_filters.
    // if ($this->options['type'] == 'select') {
    // $form['#type'] = 'checkboxes';
    // }
    // dump($form);
  }

}