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
        '#tag' => 'h2',
        '#attributes' => [
          'class' => [
            'field-bar'
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
      'layoutgenentitystyles_view' => 'more_fields/field-bar'
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // utilile pour mettre à jour le style
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        '#value' => 'more_fields/field-bar'
      ]
    ] + parent::settingsForm($form, $form_state);
  }
  
}