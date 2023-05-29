<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_string_bar_formatter",
 *   label = @Translation("String with bar"),
 *   field_types = {
 *     "string",
 *     "uri",
 *     "integer",
 *   }
 * )
 */
class StringBarFormatter extends StringFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements[] = [
      'value' => [
        '#type' => 'html_tag',
        '#tag' => $this->getSetting('tag_render'),
        '#attributes' => [
          'class' => [
            'field-bar',
            $this->getSetting('class_css')
          ]
        ],
        parent::viewElements($items, $langcode)
      ]
    ];
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-bar',
      'tag_render' => 'h2',
      'class_css' => ''
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Utilile pour mettre à jour le style.
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        '#value' => 'more_fields/field-bar'
      ],
      'tag_render' => [
        '#type' => 'textfield',
        '#title' => 'Balise rendu',
        '#default_value' => $this->getSetting('tag_render')
      ],
      'class_css' => [
        '#type' => 'textfield',
        '#title' => 'Class css',
        '#default_value' => $this->getSetting('class_css')
      ]
    ] + parent::settingsForm($form, $form_state);
  }
  
}