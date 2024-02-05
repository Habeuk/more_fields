<?php

namespace Drupal\more_fields\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\Links;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "mores_fields_lists",
 *   label = @Translation("More fields List"),
 * )
 */
class MoresFieldsLists extends Links implements ContainerFactoryPluginInterface {
  use TraitHelpper;
  /**
   * Permet de differencier les differents version d'affichage.
   *
   * @var string
   */
  protected $classByModel = 'more_fields_list_simple';

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
      $form[$field_id]['#theme'] = 'more_fields_links';
      $form[$field_id]['#attributes']['class'][] = 'more_fields_exposed_filter';
      $form[$field_id]['#attributes']['class'][] = $this->classByModel;
    }
  }

}