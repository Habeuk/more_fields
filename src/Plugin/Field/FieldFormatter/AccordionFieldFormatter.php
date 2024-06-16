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
      'custom_class_item' => '',
      'attribute_content' => 'h6 text-black-50',
      'attribute_header' => '',
      'use_as_accordion' => true
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
        '#type' => 'select',
        '#title' => 'Selectionner le style',
        '#options' => [
          '' => 'Default template bootstrap5',
          'more_fields/field-accordion' => 'fields-box',
          'more_fields/clean-box-accordion' => 'clean-box-accordion'
        ],
        '#default_value' => $this->getSetting('layoutgenentitystyles_view')
      ],
      'use_as_accordion' => [
        '#type' => 'checkbox',
        '#title' => 'use_as_accordion',
        '#default_value' => $this->getSetting('use_as_accordion')
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
        '#title' => "Class personaliser pour l'accorddion",
        '#default_value' => $this->getSetting('custom_class'),
        '#description' => "Add class 'accordion-flush' to clear border"
      ],
      'custom_class_item' => [
        '#type' => 'textfield',
        '#title' => "Class personaliser pour chaque item d'accorddion",
        '#default_value' => $this->getSetting('custom_class_item')
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
    $id = 'hbk-' . $this->getName(8);
    $attribute = new Attribute([
      'class' => [
        $this->getSetting('custom_class')
      ],
      'id' => $id
    ]);
    if ($this->getSetting('use_as_accordion'))
      $attribute->addClass("accordion");
    $attribute_box = new Attribute([
      'class' => [
        'accordion-item',
        $this->getSetting('custom_class_item')
      ]
    ]);
    if ($this->getSetting('layoutgenentitystyles_view') == 'more_fields/field-accordion') {
      $attribute->addClass("fields-box");
      $attribute_box->addClass('field-box');
    }
    elseif ($this->getSetting('layoutgenentitystyles_view') == 'more_fields/clean-box-accordion') {
      $attribute->addClass("clean-box-accordion");
    }
    $elements = [
      '#theme' => 'more_fields_accordion_field_formatter',
      '#items' => [],
      '#attribute' => $attribute,
      '#attribute_box' => $attribute_box
    ];
    $open_action = $this->getSetting('open_action');
    foreach ($items as $delta => $item) {
      $attribute_header = new Attribute([
        'class' => [
          'accordion-header',
          'd-flex'
        ],
        'data-bs-toggle' => "collapse",
        'data-bs-target' => "#" . $id . '-' . $delta,
        'aria-expanded' => "true",
        'aria-controls' => $id
      ]);
      $attribute_title = new Attribute([
        'class' => [
          'field-title',
          'font-weight-bold'
        ]
        // 'data-bs-toggle' => "collapse",
        // 'data-bs-target' => "#" . $id . '-' . $delta,
        // 'aria-expanded' => "true",
        // 'aria-controls' => $id
      ]);
      if ($this->getSetting('use_as_accordion'))
        $attribute_header->addClass('accordion-button');
      if ($this->getSetting('layoutgenentitystyles_view') == 'more_fields/field-accordion') {
        $attribute_header->addClass('btn btn-block p-0 border-0');
      }
      $attribute_header->addClass($this->getSetting('attribute_header'));
      $attr_desc = new Attribute([
        'class' => [
          'collapse',
          'accordion-collapse'
        ],
        'data-bs-parent' => "#" . $id,
        'id' => $id . '-' . $delta
      ]);
      if (($open_action == 'fisrt' && $delta == 0) || ($open_action == 'all')) {
        $attr_desc->addClass('show');
      }
      else {
        $attribute_header->addClass('collapsed');
      }
      $attr_desc->addClass($this->getSetting('attribute_content'));
      $elements['#items'][$delta] = [
        'icon' => $this->viewValue($item->icon),
        'title' => $this->viewValue($item->title),
        'description' => $this->viewValue($item->description),
        'attribute_title' => $attribute_title,
        'attribute_content' => $attr_desc,
        'attribute_header' => $attribute_header
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
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $lgt = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $n; $i++) {
      $index = rand(0, $lgt - 1);
      $randomString .= $characters[$index];
    }
    return $randomString;
  }
}
