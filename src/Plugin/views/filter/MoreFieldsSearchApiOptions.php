<?php

namespace Drupal\more_fields\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\taxonomy\Entity\Term;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Component\Utility\Timer;
use Drupal\mysql\Driver\Database\mysql\Select;

/**
 * Filter by term id.
 * Permet de retouner les items de taxonomie possedant au moins une entité.
 * plugin : search_api_term
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("more_fields_checkbox_list")
 */
class MoreFieldsSearchApiOptions extends ManyToOne {
  
  /**
   * Adds a form for entering the value or values for the filter.
   *
   * Overridden to remove fields that won't be used (but aren't hidden either
   * because of a small bug/glitch in the original form code – see #2637674).
   *
   * @param array $form
   *        The form array, passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *        The current state of the form.
   *        
   * @see \Drupal\views\Plugin\views\filter\FilterPluginBase::valueForm()
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    
    if (isset($form['value']['min']) && !$this->operatorValues(2)) {
      unset($form['value']['min'], $form['value']['max']);
    }
  }
  
}