<?php

namespace Drupal\more_fields\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Controller\TitleResolver;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a titre de la page encours block.
 *
 * @Block(
 *   id = "more_fields_titre_de_la_page_encours",
 *   admin_label = @Translation("Title of current page"),
 *   category = @Translation("Custom")
 * )
 */
class TitreDeLaPageEncoursBlock extends BlockBase implements ContainerFactoryPluginInterface {
  
  /**
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param RequestStack $requestStack
   * @param RouteMatchInterface $route_match
   * @param TitleResolver $titleResolver
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, private readonly RequestStack $requestStack, private readonly RouteMatchInterface $route_match, private readonly TitleResolver $titleResolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('request_stack'), $container->get('current_route_match'), $container->get('title_resolver'));
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'suffix_title' => '',
      'prefix_title' => '',
      'tag' => 'h1',
      'custom_class' => ''
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['prefix_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('suffix title'),
      '#default_value' => $this->configuration['prefix_title']
    ];
    $form['suffix_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('suffix title'),
      '#default_value' => $this->configuration['suffix_title']
    ];
    $form['tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('tag to use'),
      '#default_value' => $this->configuration['tag']
    ];
    $form['custom_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('custom class'),
      '#default_value' => $this->configuration['custom_class']
    ];
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['suffix_title'] = $form_state->getValue('suffix_title');
    $this->configuration['prefix_title'] = $form_state->getValue('prefix_title');
    $this->configuration['tag'] = $form_state->getValue('tag');
    $this->configuration['custom_class'] = $form_state->getValue('custom_class');
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function build() {
    $title = $this->buildTitre();
    if (!empty($this->configuration['tag'])) {
      $build['title'] = [
        '#type' => 'html_tag',
        '#tag' => $this->configuration['tag'],
        '#attributes' => [
          'class' => [
            $this->configuration['custom_class']
          ]
        ],
        $this->viewValue($title)
      ];
    }
    else
      $build = $this->viewValue($title);
    return $build;
  }
  
  /**
   * Contruit le titre.
   *
   * @return string|NULL
   */
  protected function buildTitre() {
    $request = $this->requestStack->getCurrentRequest();
    $title = $this->titleResolver->getTitle($request, $this->route_match->getRouteObject());
    $suffix = $this->configuration['suffix_title'] ?? "";
    $prefix = $this->configuration['prefix_title'] ?? "";
    // If the title is a render array, we need to add the prefix and suffix
    if (is_array($title)) {
      $title["#markup"] = $prefix . $title["#markup"] . $suffix;
    }
    return $title;
  }
  
  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *        One field item.
   *        
   * @return array The textual output generated as a render array.
   */
  protected function viewValue($value) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|raw }}',
      '#context' => [
        'value' => $value
      ]
    ];
  }
  
  /**
   * Le cache varie par URL et par session utilisateur
   *
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [
      'url',
      'user',
      'session'
    ];
  }
  
}
