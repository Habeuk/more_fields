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
 *   id = "more_fields_icon_text_formatter2",
 *   label = @Translation("Icon text formatter flat version "),
 *   field_types = {
 *     "more_fields_icon_text"
 *   }
 * )
 */
class IconTextFormatter2 extends IconTextFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-icon-svg',
      'variants_bg' => [
        'none' => 'none',
        'icon-bg-primary' => 'icon-bg-primary',
        'icon-bg-background' => 'icon-bg-background',
        'field-svg-square-background' => 'field-svg-square-background',
        'field-svg-square-primary' => 'field-svg-square-primary'
      ],
      'bg' => 'icon-bg-background',
      'variants_size' => [
        'none' => 'none',
        'icon-small' => 'icon-small',
        'icon-big' => 'icon-big'
      ],
      'size' => '',
      'custom_class_container' => '',
      'custom_class_sub_container' => 'd-flex',
      'custom_class_icon' => '',
      'custom_class_text' => 'font-weight-bold'
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        '#value' => 'more_fields/field-icon-svg'
      ],
      'size' => [
        '#type' => 'select',
        '#title' => 'Size',
        '#options' => $this->getSetting('variants_size'),
        '#default_value' => $this->getSetting('size')
      ],
      'bg' => [
        '#type' => 'select',
        '#title' => 'Background icon',
        '#options' => $this->getSetting('variants_bg'),
        '#default_value' => $this->getSetting('bg')
      ],
      'custom_class_container' => [
        '#type' => 'textfield',
        '#title' => 'Custom class container',
        '#default_value' => $this->getSetting('custom_class_container')
      ],
      'custom_class_sub_container' => [
        '#type' => 'textfield',
        '#title' => 'Custom class sous container',
        '#default_value' => $this->getSetting('custom_class_sub_container')
      ],
      'custom_class_icon' => [
        '#type' => 'textfield',
        '#title' => 'Custom class icon',
        '#default_value' => $this->getSetting('custom_class_icon')
      ],
      'custom_class_text' => [
        '#type' => 'textfield',
        '#title' => 'Custom class text',
        '#default_value' => $this->getSetting('custom_class_text')
      ]
    ] + parent::settingsForm($form, $form_state);
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
            'field-svg',
            $this->getSetting('custom_class_sub_container')
          ]
        ],
        // icon or text.
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'icon',
              $this->getSetting('custom_class_icon')
            ]
          ],
          $this->viewValue($item->text)
        ],
        // value
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'text',
              'd-flex',
              'align-items-center',
              $this->getSetting('custom_class_text')
            ]
          ],
          $this->viewValue($item->value)
        ]
      ];
    }
    $container[] = [
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'fields-svg',
            $this->getSetting('bg'),
            $this->getSetting('size'),
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
