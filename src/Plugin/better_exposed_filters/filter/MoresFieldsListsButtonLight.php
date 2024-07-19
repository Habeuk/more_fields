<?php

namespace Drupal\more_fields\Plugin\better_exposed_filters\filter;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "mores_fields_lists_button_light",
 *   label = @Translation("More fields List button Light"),
 * )
 */
class MoresFieldsListsButtonLight extends MoresFieldsLists {
  /**
   * Permet de differencier les differents version d'affichage.
   *
   * @var string
   */
  protected $classByModel = 'more_fields_list_button_light';

}