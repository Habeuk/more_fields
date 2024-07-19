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

  /**
   *
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'suffix_title' => '',
      'tag' => 'h1'
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
    return $form;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['suffix_title'] = $form_state->getValue('suffix_title');
    $this->configuration['tag'] = $form_state->getValue('tag');
  }

  /**
   *
   * {@inheritdoc}
   */
  public function build() {
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();
    /**
     *
     * @var \Drupal\Core\Controller\TitleResolver $titleResolver
     */
    $titleResolver = \Drupal::service('title_resolver');
    $title = $titleResolver->getTitle($request, $route_match->getRouteObject());
    if (!empty($this->configuration['suffix_title']))
      $title = $title . ' ' . $this->configuration['suffix_title'];

    if (!empty($this->configuration['tag'])) {
      $build['title'] = [
        '#type' => 'html_tag',
        '#tag' => $this->configuration['tag'],
        $this->viewValue($title)
      ];
    }
    else
      $build = $this->viewValue($title);

    return $build;
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