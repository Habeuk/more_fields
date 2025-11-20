<?php

namespace Drupal\more_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "more_fields_conditionalfield_widget",
 *   label = @Translation("ConditionalField Widget"),
 *   field_types = {"more_fields_conditionalfield"}
 * )
 */
class ConditionalfieldWidget extends WidgetBase {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getFieldSettings();
    $field_type = $settings['content_field_type'] ?? 'string';

    $element['content'] = $this->getContentElement($field_type, $items[$delta] ?? NULL, $settings);
    $element['content']['#title'] = $this->t('Content');

    $element['condition_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Condition'),
      '#default_value' => $items[$delta]->condition_value ?? $settings['default_condition'] ?? '',
      '#description' => $this->t('Enter the condition value for display'),
    ];

    return $element;
  }

  private function getContentElement($field_type, $item, $settings) {
    $element = [];

    switch ($field_type) {
      case 'text_long':
        $element['value'] = [
          '#type' => 'textarea',
          '#rows' => 5,
          '#default_value' => $item->content_value ?? NULL,
        ];
        break;

      case 'text_with_summary':
        $element['value'] = [
          '#type' => 'text_format',
          '#format' => $item->content_format ?? $settings['text_format'] ?? 'basic_html',
          '#default_value' => $item->content_value ?? NULL,
          '#rows' => 10,
        ];
        break;

      case 'integer':
        $element['value'] = [
          '#type' => 'number',
          '#default_value' => $item->content_value ?? NULL,
          '#min' => $settings['min'] ?? NULL,
          '#max' => $settings['max'] ?? NULL,
        ];
        break;

      case 'boolean':
        $element['value'] = [
          '#type' => 'checkbox',
          '#title' => $settings['checkbox_label'] ?? $this->t('Enabled'),
          '#default_value' => (bool) ($item->content_value ?? NULL),
        ];
        break;

      case 'email':
        $element['value'] = [
          '#type' => 'email',
          '#default_value' => $item->content_value ?? NULL,
        ];
        break;

      case 'datetime':
        $element['value'] = [
          '#type' => 'datetime',
          '#default_value' => $item->content_value ?? NULL,
        ];
        break;

      case 'entity_reference':
        $element['value'] = [
          '#type' => 'entity_autocomplete',
          '#target_type' => $settings['target_type'] ?? 'node',
          '#default_value' => $item->content_value ? \Drupal::entityTypeManager()->getStorage($settings['target_type'] ?? 'node')->load($item->content_value) : NULL,
        ];
        break;

      case 'link':
        $element['uri'] = [
          '#type' => 'url',
          '#title' => $this->t('URL'),
          '#default_value' => $item->content_uri ?? NULL,
        ];
        $element['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Link text'),
          '#default_value' => $item->content_title ?? NULL,
        ];
        break;

      case 'image':
        $element['target_id'] = [
          '#type' => 'managed_file',
          '#title' => $this->t('Image'),
          '#default_value' => $item->content_target_id ?? NULL,
          '#upload_location' => 'public://conditional-field/',
          '#upload_validators' => [
            'file_validate_extensions' => ['png gif jpg jpeg'],
          ],
        ];
        $element['alt'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Alt text'),
          '#default_value' => $item->content_alt ?? NULL,
        ];
        $element['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Title'),
          '#default_value' => $item->content_title ?? NULL,
        ];
        break;

      case 'list_string':
      case 'list_integer':
      case 'list_float':
        $options = $this->extractAllowedValues($settings['allowed_values'] ?? '');
        $element['value'] = [
          '#type' => 'select',
          '#options' => $options,
          '#empty_option' => $this->t('- Select -'),
          '#default_value' => $item->content_value ?? NULL,
        ];
        break;

      default: // string, string_long, text
        $element['value'] = [
          '#type' => 'textfield',
          '#default_value' => $item->content_value ?? NULL,
          '#size' => 60,
        ];
    }

    return $element;
  }

  private function extractAllowedValues($string) {
    $values = [];
    $lines = explode("\n", $string);
    foreach ($lines as $line) {
      $line = trim($line);
      if (strpos($line, '|') !== FALSE) {
        list($key, $value) = explode('|', $line, 2);
        $values[trim($key)] = trim($value);
      }
    }
    return $values;
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (isset($value['content'])) {
        // Gérer les types complexes
        $settings = $this->getFieldSettings();
        $field_type = $settings['content_field_type'] ?? 'string';

        switch ($field_type) {
          case 'image':
            $value['content_target_id'] = $value['content']['target_id'][0] ?? NULL;
            $value['content_alt'] = $value['content']['alt'] ?? '';
            $value['content_title'] = $value['content']['title'] ?? '';
            break;

          case 'link':
            $value['content_uri'] = $value['content']['uri'] ?? '';
            $value['content_title'] = $value['content']['title'] ?? '';
            break;

          case 'text_with_summary':
            $value['content_value'] = $value['content']['value']['value'] ?? '';
            $value['content_format'] = $value['content']['value']['format'] ?? '';
            break;

          default:
            $value['content_value'] = $value['content']['value'] ?? '';
        }
        unset($value['content']);
      }
    }
    return $values;
  }
}