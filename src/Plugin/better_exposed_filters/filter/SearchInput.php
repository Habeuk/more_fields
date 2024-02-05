<?php

namespace Drupal\more_fields\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\RadioButtons;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersString;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "more_fields_search",
 *   label = @Translation("More fields search"),
 * )
 */
class SearchInput extends FilterWidgetBase implements ContainerFactoryPluginInterface {
  use TraitHelpper;

  /**
   * Permet de differencier les differents version d'affichage.
   *
   * @var string
   */
  protected $classByModel = 'more_fields_search';

  /**
   *
   * {@inheritdoc}
   */
  public static function isApplicable($filter = NULL, array $filter_options = []) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $is_applicable = FALSE;
    if (is_a($filter, 'Drupal\views\Plugin\views\filter\StringFilter')) {
      $is_applicable = TRUE;
    }
    return $is_applicable;
  }

  function __construct($configuration, $plugin_id, $plugin_definition, LayoutgenentitystylesServices $LayoutgenentitystylesServices) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->LayoutgenentitystylesServices = $LayoutgenentitystylesServices;
  }

  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('layoutgenentitystyles.add.style.theme'));
  }

  /**
   *
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);
    $field_id = $this->getExposedFilterFieldId();
    if (!empty($form[$field_id])) {
      $form[$field_id]['#attributes']['class'][] = 'more_fields_exposed_filter';
      $form[$field_id]['#attributes']['class'][] = $this->classByModel;
    }
  }

}