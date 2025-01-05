<?php

namespace Drupal\more_fields\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a titre de la page encours block.
 *
 * @Block(
 *   id = "more_fields_titre_de_la_page_encours",
 *   admin_label = @Translation("Title of current page"),
 *   category = @Translation("Custom")
 * )
 */
class TitreDeLaPageEncoursBlock extends BlockBase {
  protected $request;
  protected $route_match;
  
  /**
   *
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'suffix_title' => '',
      'tag' => 'h1',
      'custom_class' => ''
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
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
    $this->request = \Drupal::request();
    $this->route_match = \Drupal::routeMatch();
    /**
     *
     * @var \Drupal\Core\Controller\TitleResolver $titleResolver
     */
    $titleResolver = \Drupal::service('title_resolver');
    $title = $titleResolver->getTitle($this->request, $this->route_match->getRouteObject());
    if (!empty($this->configuration['suffix_title']))
      $title = $title . ' ' . $this->configuration['suffix_title'];
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
}