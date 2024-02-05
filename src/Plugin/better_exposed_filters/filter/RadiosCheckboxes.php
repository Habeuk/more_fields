<?php

namespace Drupal\more_fields\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\RadioButtons;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "mf_radios_checkboxes",
 *   label = @Translation("More fields Checkboxes/Radio Buttons"),
 * )
 */
class RadiosCheckboxes extends RadioButtons implements ContainerFactoryPluginInterface {

  /**
   *
   * @var \Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices
   */
  protected $LayoutgenentitystylesServices;

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

  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'layoutgenentitystyles_view_options' => [
        'more_fields/more_fields_bef_checkboxes' => 'button plein'
      ],
      'layoutgenentitystyles_view' => 'more_fields/more_fields_bef_checkboxes',
      'theme_color' => 'mf_bef_background'
    ];
  }

  /**
   *
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    $filter = $this->handler;
    // Form element is designated by the element ID which is user-
    // configurable.
    $field_id = $filter->options['is_grouped'] ? $filter->options['group_info']['identifier'] : $filter->options['expose']['identifier'];

    if (!empty($form[$field_id]['#multiple'])) {
      // Render as checkboxes if filter allows multiple selections.
      $form[$field_id]['#theme'] = 'more_fields_bef_checkboxes';
    }
    else {
      $form[$field_id]['#theme'] = 'more_fields_bef_radios';
    }
  }

  /**
   *
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['layoutgenentitystyles_view'] = [
      '#type' => 'select',
      '#title' => "Style d'affichage",
      '#options' => $this->configuration['layoutgenentitystyles_view_options'],
      '#default_value' => $this->configuration['layoutgenentitystyles_view']
    ];
    $form['theme_color'] = [
      '#type' => 'select',
      '#title' => 'Theme color',
      '#options' => [
        'mf_bef_primary' => 'primary',
        'mf_bef_background' => 'background'
      ],
      '#default_value' => $this->configuration['theme_color']
    ];
    return $form;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!empty($this->configuration['layoutgenentitystyles_view'])) {
      $this->LayoutgenentitystylesServices->addStyleFromModule($this->configuration['layoutgenentitystyles_view'], "mf_radios_checkboxes", "default", "better_exposed_filters/filter");
    }
  }

}