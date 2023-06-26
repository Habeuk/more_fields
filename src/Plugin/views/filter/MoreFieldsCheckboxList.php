<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\Core\Session\AccountInterface;
//
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("more_fields_checkbox_list")
 */
class MoreFieldsCheckboxList extends TaxonomyIndexTid {
  
  protected function defineOptions() {
    $options = parent::defineOptions();
    
    $options['type'] = [
      'default' => 'select'
    ];
    return $options;
  }
  
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    // dump($this->options);
    if ($this->options['type'] == 'select') {
      $form['value']['#type'] = 'checkboxes';
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