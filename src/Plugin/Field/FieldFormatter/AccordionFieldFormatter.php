<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Plugin implementation of the 'more_fields_accordion_field' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_accordion_field_formatter",
 *   label = @Translation("Accordion field formatter type"),
 *   field_types = {
 *     "more_fields_accordion_field"
 *   }
 * )
 */
class AccordionFieldFormatter extends FormatterBase {

  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-accordion',
      'open_action' => 'fisrt',
      'custom_class' => '',
      'attribute_content' => 'h6 text-black-50',
      'attribute_header' => ''
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
        '#value' => 'more_fields/field-accordion'
      ],
      'open_action' => [
        '#type' => 'select',
        "#title" => "Open accordion",
        "#options" => [
          '' => 'None',
          'fisrt' => 'open first',
          "all" => "all open"
        ],
        '#default_value' => $this->getSetting('open_action')
      ],
      'custom_class' => [
        '#type' => 'textfield',
        '#title' => 'Custom class for accordion',
        '#default_value' => $this->getSetting('custom_class')
      ],
      'attribute_header' => [
        '#type' => 'textfield',
        '#title' => 'attribute_header',
        '#default_value' => $this->getSetting('attribute_header')
      ],
      'attribute_content' => [
        '#type' => 'textfield',
        '#title' => 'attribute_content',
        '#default_value' => $this->getSetting('attribute_content')
      ]
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   *
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $id = 'habeuk-' . $this->getName(8);
    $attribute = new Attribute([
      'class' => [
        'accordion',
        'fields-box',
        $this->getSetting('custom_class')
      ],
      'id' => $id
    ]);
    $attribute_box = new Attribute([
      'class' => [
        'field-box',
        'mb-3'
      ]
    ]);
    $elements = [
      '#theme' => 'more_fields_accordion_field_formatter',
      '#items' => [],
      '#attribute' => $attribute,
      '#attribute_box' => $attribute_box
    ];
    $open_action = $this->getSetting('open_action');
    foreach ($items as $delta => $item) {
      $attribute_t = new Attribute([
        'class' => [
          'field-meta',
          'btn btn-block p-0 border-0'
        ],
        'data-toggle' => "collapse",
        'data-target' => "#" . $id . '-' . $delta,
        'aria-expanded' => "true",
        'aria-controls' => $id
      ]);
      $attribute_t->addClass($this->getSetting('attribute_header'));
      $attr_desc = new Attribute([
        'class' => [
          'collapse',
          ($open_action == 'fisrt' && $delta == 0) || ($open_action == 'all') ? 'show' : ''
        ],
        'data-parent' => "#" . $id,
        'id' => $id . '-' . $delta
      ]);
      $attr_desc->addClass($this->getSetting('attribute_content'));
      $elements['#items'][$delta] = [
        'icon' => $this->viewValue($item->icon),
        'title' => $this->viewValue($item->title),
        'description' => $this->viewValue($item->description),
        'attribute_title' => $attribute_t,
        'attribute_content' => $attr_desc
      ];
    }
    return $elements;
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
   *
   * @param
   *        $n
   * @return string
   */
  public function getName($n) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lgt = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $n; $i++) {
      $index = rand(0, $lgt - 1);
      $randomString .= $characters[$index];
    }
    return $randomString;
  }
}
