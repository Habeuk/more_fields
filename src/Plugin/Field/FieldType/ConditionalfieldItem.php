<?php

declare(strict_types=1);

namespace Drupal\more_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *   id = "more_fields_conditionalfield",
 *   label = @Translation("ConditionalField"),
 *   description = @Translation("Wrap content with conditional display logic."),
 *   default_widget = "more_fields_conditionalfield_widget",
 *   default_formatter = "more_fields_conditionalfield_formatter",
 * )
 */
final class ConditionalfieldItem extends FieldItemBase {

  public static function defaultStorageSettings(): array {
    return [
      'content_field_type' => 'string',
      'default_condition' => '',
    ] + parent::defaultStorageSettings();
  }

  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);
    $settings = $this->getSettings();

    $element['content_field_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Field Type'),
      '#options' => $this->getAvailableFieldTypes(),
      '#default_value' => $settings['content_field_type'],
      '#empty_option' => $this->t('- Select -'),
    ];

    // Toujours afficher les settings pour le type actuel
    if (!empty($settings['content_field_type'])) {
      $element += $this->getTypeSpecificSettings($settings['content_field_type'], $settings);
    }

    $element['default_condition'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Condition'),
      '#default_value' => $settings['default_condition'],
    ];

    return $element;
  }

  public static function storageSettingsAjax(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents); // Remove the last element (content_field_type)
    $parents[] = 'type_settings';

    return \Drupal\Core\Render\Element::getVisibleChildren($form, $parents) ?
      $form['settings']['type_settings'] :
      ['#markup' => ''];
  }

  private function getAvailableFieldTypes() {
    return [
      'string' => $this->t('Text (plain)'),
      'string_long' => $this->t('Text (plain, long)'),
      'text' => $this->t('Text (formatted)'),
      'text_long' => $this->t('Text (formatted, long)'),
      'text_with_summary' => $this->t('Text (formatted, with summary)'),
      'integer' => $this->t('Number (integer)'),
      'decimal' => $this->t('Number (decimal)'),
      'float' => $this->t('Number (float)'),
      'boolean' => $this->t('Boolean'),
      'email' => $this->t('Email'),
      'datetime' => $this->t('Date'),
      'entity_reference' => $this->t('Entity reference'),
      'list_string' => $this->t('List (text)'),
      'list_integer' => $this->t('List (integer)'),
      'list_float' => $this->t('List (float)'),
      'link' => $this->t('Link'),
      'image' => $this->t('Image'),
    ];
  }

  private function getTypeSpecificSettings($field_type, $current_settings) {
    $element = [];

    switch ($field_type) {
      case 'entity_reference':
        $entity_types = \Drupal::entityTypeManager()->getDefinitions();
        $options = [];
        foreach ($entity_types as $id => $definition) {
          $options[$id] = $definition->getLabel();
        }

        $element['target_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Target entity type'),
          '#options' => $options,
          '#default_value' => $current_settings['target_type'] ?? 'node',
        ];
        break;

      case 'text_with_summary':
      case 'text_long':
        $formats = \Drupal::entityTypeManager()->getStorage('filter_format')->loadMultiple();
        $options = [];
        foreach ($formats as $format) {
          $options[$format->id()] = $format->label();
        }

        $element['text_format'] = [
          '#type' => 'select',
          '#title' => $this->t('Text format'),
          '#options' => $options,
          '#default_value' => $current_settings['text_format'] ?? 'basic_html',
        ];
        break;

      case 'list_string':
      case 'list_integer':
      case 'list_float':
        $element['allowed_values'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Allowed values'),
          '#default_value' => $current_settings['allowed_values'] ?? '',
          '#description' => $this->t('One value per line, in the format key|label.'),
        ];
        break;

      case 'datetime':
        $element['datetime_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Date type'),
          '#options' => [
            'datetime' => $this->t('Date and time'),
            'date' => $this->t('Date only'),
          ],
          '#default_value' => $current_settings['datetime_type'] ?? 'datetime',
        ];
        break;

      case 'link':
        $element['link_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Link type'),
          '#options' => [
            'link_generic' => $this->t('Generic'),
            'link_internal' => $this->t('Internal'),
            'link_external' => $this->t('External'),
          ],
          '#default_value' => $current_settings['link_type'] ?? 'link_generic',
        ];
        break;
    }

    return $element;
  }

  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = [];
    $settings = $field_definition->getSettings();
    $field_type = $settings['content_field_type'] ?? 'string';

    switch ($field_type) {
      case 'integer':
      case 'list_integer':
        $properties['content_value'] = DataDefinition::create('integer')
          ->setLabel(t('Content Value'));
        break;

      case 'float':
      case 'decimal':
      case 'list_float':
        $properties['content_value'] = DataDefinition::create('float')
          ->setLabel(t('Content Value'));
        break;

      case 'boolean':
        $properties['content_value'] = DataDefinition::create('boolean')
          ->setLabel(t('Content Value'));
        break;

      case 'datetime':
        $properties['content_value'] = DataDefinition::create('datetime_iso8601')
          ->setLabel(t('Content Value'));
        break;

      case 'image':
        $properties['content_target_id'] = DataDefinition::create('integer')
          ->setLabel(t('Image ID'));
        $properties['content_alt'] = DataDefinition::create('string')
          ->setLabel(t('Alt text'));
        $properties['content_title'] = DataDefinition::create('string')
          ->setLabel(t('Title'));
        break;

      case 'link':
        $properties['content_uri'] = DataDefinition::create('string')
          ->setLabel(t('URL'));
        $properties['content_title'] = DataDefinition::create('string')
          ->setLabel(t('Link text'));
        break;

      default:
        $properties['content_value'] = DataDefinition::create('string')
          ->setLabel(t('Content Value'));
    }

    if (in_array($field_type, ['text_with_summary', 'text_long'])) {
      $properties['content_format'] = DataDefinition::create('string')
        ->setLabel(t('Text format'));
    }

    $properties['condition_value'] = DataDefinition::create('string')
      ->setLabel(t('Condition Value'));

    return $properties;
  }

  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $settings = $field_definition->getSettings();
    $field_type = $settings['content_field_type'] ?? 'string';

    $columns = [];

    switch ($field_type) {
      case 'integer':
      case 'list_integer':
        $columns['content_value'] = ['type' => 'int', 'size' => 'normal'];
        break;

      case 'float':
      case 'decimal':
      case 'list_float':
        $columns['content_value'] = ['type' => 'float', 'size' => 'normal'];
        break;

      case 'boolean':
        $columns['content_value'] = ['type' => 'int', 'size' => 'tiny'];
        break;

      case 'datetime':
        $columns['content_value'] = ['type' => 'varchar', 'length' => 20];
        break;

      case 'email':
      case 'string':
      case 'list_string':
        $columns['content_value'] = ['type' => 'varchar', 'length' => 255];
        break;

      case 'string_long':
      case 'text':
      case 'text_long':
      case 'text_with_summary':
        $columns['content_value'] = ['type' => 'text', 'size' => 'big'];
        break;

      case 'image':
        $columns['content_target_id'] = ['type' => 'int', 'unsigned' => TRUE];
        $columns['content_alt'] = ['type' => 'varchar', 'length' => 255];
        $columns['content_title'] = ['type' => 'varchar', 'length' => 255];
        break;

      case 'link':
        $columns['content_uri'] = ['type' => 'varchar', 'length' => 2048];
        $columns['content_title'] = ['type' => 'varchar', 'length' => 255];
        break;

      default:
        $columns['content_value'] = ['type' => 'text'];
    }

    if (in_array($field_type, ['text_with_summary', 'text_long'])) {
      $columns['content_format'] = ['type' => 'varchar', 'length' => 255];
    }

    $columns['condition_value'] = ['type' => 'varchar', 'length' => 255];

    return ['columns' => $columns];
  }

  public function isEmpty(): bool {
    $settings = $this->getSettings();
    $field_type = $settings['content_field_type'] ?? 'string';

    switch ($field_type) {
      case 'image':
        $target_id = $this->get('content_target_id')->getValue();
        return empty($target_id);

      case 'link':
        $uri = $this->get('content_uri')->getValue();
        return empty($uri);

      default:
        $content_value = $this->get('content_value')->getValue();
        return empty($content_value);
    }
  }
}
