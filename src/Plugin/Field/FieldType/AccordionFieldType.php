<?php

namespace Drupal\more_fields\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'more_fields_icon_text' field type.
 *
 * @FieldType(
 *   id = "more_fields_accordion_field",
 *   label = @Translation("accordion field(event on header) "),
 *   description = @Translation("Allows to generate an element of an accordion"),
 *   default_widget = "more_fields_accordion_field_widget",
 *   default_formatter = "more_fields_accordion_field_formatter",
 *   category = "Complex fields"
 * )
 */
class AccordionFieldType extends FieldItemBase
{

    /**
     *
     * {@inheritdoc}
     */
    public static function defaultStorageSettings()
    {
        return [
            'max_length' => 100
        ] + parent::defaultStorageSettings();
    }

    /**
     *
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        // @see grep -nr "@DataType" web/core/
        // Prevent early t() calls by using the TranslatableMarkup.
        $properties['icon'] = DataDefinition::create('string')->setLabel(new TranslatableMarkup('text'))->setRequired(TRUE);
        $properties['title'] = DataDefinition::create('string')->setLabel(new TranslatableMarkup('text'))->setRequired(TRUE);
        $properties['description'] = DataDefinition::create('string')->setLabel(new TranslatableMarkup('text'))->setRequired(TRUE);
        $properties['format'] = DataDefinition::create('filter_format')->setLabel(t('Text format'));
        return $properties;
    }

    /**
     *
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        $schema = [
            'columns' => [
                'icon' => [
                    'type' => 'text',
                    'unsigned' => FALSE,
                    'binary' => ''
                ],
                'title' => [
                    'type' => 'text',
                    'unsigned' => FALSE,
                    'binary' => ''
                ],
                'description' => [
                    'type' => 'text',
                    'unsigned' => FALSE,
                    'binary' => ''
                ],
                'format' => [
                    'type' => 'varchar_ascii',
                    'length' => 50
                ]
            ]
        ];
        return $schema;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getConstraints()
    {
        $constraints = parent::getConstraints();

        if ($max_length = $this->getSetting('max_length')) {
            $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
            $constraints[] = $constraint_manager->create('ComplexData', []);
        }

        return $constraints;
    }

    /**
     *
     * {@inheritdoc}
     */
    public static function generateSampleValue(FieldDefinitionInterface $field_definition)
    {
        $random = new Random();
        $values['value'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
        return $values;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data)
    {
        // $elements = [];

        // $elements['max_length'] = [
        //     '#type' => 'number',
        //     '#title' => t('Maximum length'),
        //     '#default_value' => $this->getSetting('max_length'),
        //     '#required' => TRUE,
        //     '#description' => t('The maximum length of the field in characters.'),
        //     '#min' => 1,
        //     '#disabled' => $has_data
        // ];

        return [];
    }
}
