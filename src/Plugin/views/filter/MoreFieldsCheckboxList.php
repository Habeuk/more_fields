<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\taxonomy\Entity\Term;
use Drupal\monitoring_drupal\Services\TimerMonitoring;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("more_fields_checkbox_list")
 */
class MoreFieldsCheckboxList extends TaxonomyIndexTid implements FilterCountInterface {
  use MoreFieldsBaseFilter;
  
  /**
   * Copier de la version : Drupal core 10.2.4
   *
   * {@inheritdoc}
   * @see \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid::valueForm()
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
    if (empty($vocabulary) && $this->options['limit']) {
      $form['markup'] = [
        '#markup' => '<div class="js-form-item form-item">' . $this->t('An invalid vocabulary is selected. Change it in the options.') . '</div>'
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
      
      if (!empty($this->options['hierarchy']) && $this->options['limit']) {
        $tree = $this->termStorage->loadTree($vocabulary->id(), 0, NULL, TRUE);
        $options = [];
        
        if ($tree) {
          foreach ($tree as $term) {
            if (!$term->isPublished() && !$this->currentUser->hasPermission('administer taxonomy')) {
              continue;
            }
            $tid = $term->id();
            // Verification de l'affichage du terme.
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
        // Pas utile à ce niveau mais doit etre ajouter dans le filtre.
        // $query = \Drupal::entityQuery('taxonomy_term')->accessCheck(TRUE)->
        // // @todo Sorting on vocabulary properties -
        // // https://www.drupal.org/node/1821274.
        // sort('weight')->sort('name')->addTag('taxonomy_term_access');
        // if (!$this->currentUser->hasPermission('administer taxonomy')) {
        // $query->condition('status', 1);
        // }
        // if ($this->options['limit']) {
        // $query->condition('vid', $vocabulary->id());
        // }
        if ($tids) {
          $terms = Term::loadMultiple($tids);
        }
        // $terms = Term::loadMultiple($query->execute());
        foreach ($terms as $term) {
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
   *
   * {@inheritdoc}
   * @see \Drupal\more_fields\Plugin\views\filter\FilterCountInterface::FilterCountEntitiesHasterm()
   */
  public function FilterCountEntitiesHasterm(): array {
    // TimerMonitoring::start('FilterCountEntitiesHasterm');
    $tids = [];
    /**
     * L'execution à l'interieur d'un fonction bc plus rapide.
     * de lordre de 7x plus rapide. ie, si à l'interieur on a une durée de 2 ms
     * à l'exterieur on aurra 14 ms.
     * NB: le temps d'exection est autour de [1.6 à 2.9]ms.
     *
     * @var boolean $test_code_inside
     */
    $test_code_inside = false;
    if ($test_code_inside) {
      /**
       *
       * @var \Drupal\views\ViewExecutable $viewInstance
       */
      $viewInstance = $this->view;
      /**
       * On initialise la vue, ie on construit la requete "select" de base.
       */
      $viewInstance->initQuery();
      $viewInstance->_build('filter');
      // On construit les autres requetes.
      $filters = $viewInstance->filter;
      
      // On recupere les valeurs exposeds.
      $exposed_inputs = $this->view->getExposedInput();
      
      // On s'assure que le champs encours de traitement est effectivement dans
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
      $select_query = $viewInstance->query->query();
    }
    else {
      // On recupere les valeurs exposeds.
      $exposed_inputs = $this->view->getExposedInput();
      $filters = $this->view->filter;
      $select_query = $this->buildBaseSql();
    }
    
    /**
     * On applique les valeurs exposeds s'ils existent.
     *
     * @var array $exposed_inputs
     */
    foreach ($exposed_inputs as $id => $value) {
      if (!empty($filters[$id])) {
        $filter = $filters[$id];
        // On implique la valeur encours si cela est explicitement definit.
        if (!($this->options['ignore_default_value'] && $filter->realField == $this->realField))
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
    $select_query->addField($alias, $colomn_name);
    $select_query->addExpression("count($alias.$colomn_name)", $this->alias_count);
    $select_query->groupBy($alias . '.' . $colomn_name);
    
    // dump($this->realField);
    // dump($select_query->__toString());
    // dump($select_query->execute()->fetchAll(\PDO::FETCH_ASSOC));
    $entities = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    // dump($this->realField, $entities);
    foreach ($entities as $value) {
      $this->countsTerms[$value[$this->realField]] = $value[$this->alias_count];
      $tids[$value[$this->realField]] = $value[$this->realField];
    }
    // $result = TimerMonitoring::stop('FilterCountEntitiesHasterm');
    // dump($result);
    return $tids;
  }
  
  /**
   * On ne filtre pas le html des labels car on doit afficher le html
   * inclut.
   */
  protected function prepareFilterSelectOptions(&$options) {
    // dump($options);
    // On retourne les données sans les filtrées (risque de securitée).
  }
  
}