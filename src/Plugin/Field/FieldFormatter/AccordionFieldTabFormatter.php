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
 *   id = "more_fields_accordion_field_tab_formatter",
 *   label = @Translation("Tab field formatter type"),
 *   field_types = {
 *     "more_fields_accordion_field"
 *   }
 * )
 */
class AccordionFieldTabFormatter extends FormatterBase {

  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-accordion',
      'tab_settings' => [
        'toggle_type' => 'pill',
        'field_content_class' => '',
        'field_item_attribute' => '',
        'field_header_class' => 'nav nav-pills mb-3',
        'field_each_header_class' => 'nav-link',
        'field_each_content_class' => 'fade '
      ]
    ] + parent::defaultSettings();
  }

  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $tab_settings = $this->getsetting("tab_settings");
    $default_settings = static::defaultSettings()["tab_settings"];
    $tab_form["layoutgenentitystyles_view"] = [
      '#type' => 'hidden',
      '#value' => static::defaultSettings()["layoutgenentitystyles_view"]
    ];
    $tab_form["tab_settings"] = [
      '#type' => 'details',
      '#title' => $this->t("Tab formatter"),
      '#open' => true,
      '#tree' => true
    ];
    $tab_form["tab_settings"]["toggle_type"] = [
      '#type' => 'select',
      '#title' => $this->t('Toggle Type'),
      '#options' => [
        'pill' => $this->t('Pill'),
        'tab' => $this->t('tab')
      ],
      '#default_value' => $tab_settings['toggle_type'] ?? $default_settings['toggle_type']
    ];
    $tab_form["tab_settings"]["field_item_attribute"] = [
      '#type' => 'textfield',
      '#title' => $this->t('class for the field '),
      '#default_value' => $tab_settings['field_item_attribute'] ?? $default_settings['field_item_attribute']
    ];
    $tab_form["tab_settings"]["field_content_class"] = [
      '#type' => 'textfield',
      '#title' => $this->t("Class for the contents"),
      '#default_value' => $tab_settings['field_content_class'] ?? $default_settings['field_content_class']
    ];

    $tab_form["tab_settings"]["field_header_class"] =  [
      '#type' => 'textfield',
      '#title' => $this->t("Custom class for the tab header"),
      '#default_value' => $tab_settings['field_header_class'] ?? $default_settings['field_header_class']
    ];
    $tab_form["tab_settings"]["field_each_header_class"] = [
      '#type' => 'textfield',
      '#title' => $this->t("Class for each header"),
      '#default_value' => $tab_settings['field_each_header_class'] ?? $default_settings['field_each_header_class']
    ];
    $tab_form["tab_settings"]["field_each_content_class"] = [
      '#type' => 'textfield',
      '#title' => $this->t("Class for each Content"),
      '#default_value' => $tab_settings['field_each_content_class'] ?? $default_settings['field_each_content_class']
    ];
    return  $tab_form + parent::settingsForm($form, $form_state);
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
    $id = 'hbk-vy_tab' . AccordionFieldFormatter::getName(8);
    $settings = $this->getsetting("tab_settings") + static::defaultSettings()["tab_settings"];

    $field_content_class = new Attribute([
      'class' => explode(" ", "tab-content " . $settings["field_content_class"]),
    ]);
    $field_item_attribute = new Attribute([
      'class' => explode(" ", "accordion-item " . $settings["field_item_attribute"])
    ]);
    $field_header_class = new Attribute([
      'class' => explode(" ", $settings["field_header_class"]),
      'role' => 'tablist'
    ]);


    $elements = [
      '#theme' => 'more_fields_accordion_field_tab_formatter',
      '#items' => [],
      '#attribute_field' => $field_item_attribute,
      '#field_header_class' => $field_header_class,
      '#field_content_class' => $field_content_class
    ];
    foreach ($items as $delta => $item) {
      $tab_id = $id . '-' . $delta;
      $header_attributes = new Attribute(
        [
          'aria-selected' => "true",
          'aria-controls' => $tab_id,
          'data-bs-target' => "#" . $tab_id,
          'data-bs-toggle' => $settings["toggle_type"],
          'class' => explode(" ",  $settings["field_each_header_class"])
        ]
      );
      $content_attributes = new Attribute([
        "id" => $tab_id,
        'class' => explode(" ", "tab-pane " . $settings["field_each_content_class"])
      ]);
      if ($delta == 0) {
        $header_attributes->addClass('active');
        $content_attributes->addClass(["show", "active"]);
      }

      $elements['#items'][$delta] = [
        'icon' => $this->viewValue($item->icon),
        'title' => $this->viewValue($item->title),
        'description' => $this->viewValue($item->description),
        'header_attributes' => $header_attributes,
        'content_attributes' => $content_attributes
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
}
