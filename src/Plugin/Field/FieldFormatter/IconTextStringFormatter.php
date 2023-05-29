<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'more_fields_icon_text_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_icon_text_string",
 *   label = @Translation("Icon text formatter string flat "),
 *   field_types = {
 *     "more_fields_icon_text"
 *   }
 * )
 */
class IconTextStringFormatter extends IconTextFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-link',
      'custom_class_container' => '',
      'custom_class_text' => 'text-dark h5 mb-1',
      'custom_class_icon' => 'text-dark'
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        '#value' => 'more_fields/field-link'
      ],
      'custom_class_container' => [
        '#type' => 'textfield',
        '#title' => 'Custom class container',
        '#default_value' => $this->getSetting('custom_class_container')
      ],
      'custom_class_text' => [
        '#type' => 'textfield',
        '#title' => 'Custom class text',
        '#default_value' => $this->getSetting('custom_class_text')
      ],
      'custom_class_icon' => [
        '#type' => 'textfield',
        '#title' => 'Custom class icon',
        '#default_value' => $this->getSetting('custom_class_icon')
      ]
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'link'
          ]
        ],
        // value
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              $this->getSetting('custom_class_text')
            ]
          ],
          $this->viewValue($item->value)
        ],
        // icon or text.
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              $this->getSetting('custom_class_icon')
            ]
          ],
          $this->viewValue($item->text)
        ]
      ];
    }
    $container[] = [
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'field-links',
            $this->getSetting('custom_class_container')
          ]
        ],
        $elements
      ]
    ];
    return $container;
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
