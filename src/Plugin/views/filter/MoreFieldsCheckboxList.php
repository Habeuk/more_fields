<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;

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
        $this->FilterTermHasContent($query, $queryEntity);
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
   *
   * @see https://drupal.stackexchange.com/questions/184411/entityquery-group-by-clause
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   */
  public function FilterCountEntitiesHasterm() {
    /**
     *
     * @var \Drupal\Core\Entity\Query\QueryAggregateInterface $queryEntity
     */
    $queryEntity = \Drupal::entityQueryAggregate($this->configuration['entity_type'])->accessCheck(true);
    // On filtre les entites ayant un terme.
    $queryEntity->condition($this->configuration['field_name'], null, 'IS NOT NULL');
    // On filtre les entitées ayant une valeur dans le array.
    if ($this->options["filter_by_current_term"]) {
      $this->filterByCurrentTerm($queryEntity);
    }
    // On regroupe en fonction du terme tid.
    $queryEntity->groupBy($this->configuration['field_name']);
    // On compte les resultats.
    $queryEntity->aggregate($this->configuration['field_name'], 'COUNT', NULL, $this->alias_count);
    return $queryEntity;
  }

  /**
   * Le but est de determiner le nom du champs dans l'entite.
   *
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
  protected function FilterTermHasContent(QueryInterface &$query, QueryAggregateInterface $queryEntity) {
    $entities = $queryEntity->execute();
    if ($entities) {
      $tids = [];
      foreach ($entities as $value) {
        $tids[] = $value[$this->configuration['field']];
        $this->countsTerms[$value[$this->configuration['field']]] = $value[$this->alias_count];
      }
      $query->condition('tid', $tids, 'IN');
    }
    else {
      // s'il nya pas de correspondance, on vide la requete.
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