<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\taxonomy\Entity\Term;

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
  
  /**
   * On ne filtre pas le html des labels car on doit afficher le html
   * inclut.
   */
  protected function prepareFilterSelectOptions(&$options) {
    // On retourne les données sans les filtrées risque de securitée.
  }
  
  /**
   * Copier de la version : Drupal core 9.5.9
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
        // if ($queryEntity) {
        // $this->FilterTermHasContent($query, $queryEntity);
        // }
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
  
  public function FilterCountEntitiesHasterm() {
    /**
     *
     * @var \Drupal\views\ViewExecutable $viewClone
     */
    $viewClone = clone $this->view;
    /**
     * On initialise la vue, ie on construit la requete "select" de base.
     */
    $viewClone->initQuery();
    $viewClone->_build('filter');
    // On construit les autres requetes.
    $filters = $viewClone->filter;
    // foreach ($filters as $filter) {
    // /**
    // * Pas logique cette application.
    // *
    // * @var \Drupal\more_fields\Plugin\views\filter\MoreFieldsCheckboxList
    // $filter
    // */
    // if ($filter->isExposed()) {
    // // $filter->ensureMyTable();
    // // N'intervient dans le cadre des elements exposed.
    // // $filter->query();
    // }
    // }
    
    // On recupere les valeurs exposeds.
    $exposed_inputs = $this->view->getExposedInput();
    
    // On s'assure que la champs encours de traitement est effectivement dans
    // les jointures.
    $this->ensureMyTable();
    
    // On construit les jointures uniquement avec les valeurs exposed.
    foreach ($exposed_inputs as $id => $value) {
      if (!empty($filters[$id])) {
        $filter = $filters[$id];
        $filter->ensureMyTable();
      }
    }
    
    /**
     * On recupere la requete select apres toutes les constructions.
     * ( elle peut etre mise en cache pour une requete données ).
     *
     * @var \Drupal\mysql\Driver\Database\mysql\Select $select
     */
    $select_query = $viewClone->query->query();
    
    /**
     * On applique les valeurs exposeds s'ils existent.
     *
     * @var array $exposed_inputs
     */
    foreach ($exposed_inputs as $id => $value) {
      if (!empty($filters[$id])) {
        $filter = $filters[$id];
        // On implique la valeur encours si cela est explicitement definit.
        
        $this->buildCondition($select_query, $filter->tableAlias, $filter->realField, $value, $filter->operator);
      }
    }
    
    /**
     * On applique ce qui est necessaire au champs en cours.
     * On a besoin de ressortir la liste des termes rataché au moins à une
     * entité et les groupés par entité afin d'avoir le nombre.
     */
    $alias = $this->tableAlias ? $this->tableAlias : $this->table;
    $colomn_name = $this->realField;
    $select_query->addExpression("count($alias.$colomn_name)", $this->alias_count);
    $select_query->groupBy($alias . '.' . $colomn_name);
    
    // dump($this->realField);
    dump($select_query->__toString());
    dump($select_query->execute()->fetchAll(\PDO::FETCH_ASSOC));
  }
  
  protected function exposedTranslate(&$form, $type) {
    parent::exposedTranslate($form, $type);
    // les types radios et checkboxes ne fonctionnent pas correctement use
    // better_exposed_filters.
  }
  
  public function query() {
    parent::query();
  }
  
  public function opHelper() {
    parent::opHelper();
  }
  
  public function ensureMyTable() {
    parent::ensureMyTable();
  }
  
}