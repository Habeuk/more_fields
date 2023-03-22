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
 *   id = "more_fields_icon_buttons_rx",
 *   label = @Translation("Buttons flat RXs "),
 *   field_types = {
 *     "more_fields_icon_text"
 *   }
 * )
 */
class IconTextButtonsRx extends FormatterBase {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-buttons',
      'custom_class' => '',
      'show_text' => true,
      'options_background' => [
        'no-bg' => 'No background',
        'field-buttons--background' => 'BG from $wbu-background',
        'field-buttons--primary' => 'BG from $wbu-color-primary'
      ],
      'background' => 'field-buttons--background'
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'custom_class' => [
        '#type' => 'textfield',
        '#title' => 'Custom class',
        '#default_value' => $this->getSetting('custom_class')
      ],
      'background' => [
        '#type' => 'select',
        '#title' => 'Effet au hover',
        '#options' => $this->getSetting('options_background'),
        '#default_value' => $this->getSetting('background')
      ],
      'show_text' => [
        '#type' => 'checkbox',
        '#title' => 'Affichez le text',
        '#default_value' => $this->getSetting('show_text'),
        '#description' => "Permet d'afficher par example le nom du rx social"
      ],
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        '#value' => 'more_fields/field-buttons'
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
            'd-flex',
            'item'
          ]
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'svg'
            ]
          ],
          $this->viewValue($item->text)
        ]
      ];
      if ($this->getSetting('show_text'))
        $elements[$delta][] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'text'
            ]
          ],
          $this->viewValue($item->value)
        ];
    }
    $container['container'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'field-buttons',
          'd-flex',
          $this->getSetting('custom_class'),
          $this->getSetting('background')
        ]
      ],
      $elements
    ];
    // dump($container);
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
