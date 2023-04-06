<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

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
class AccordionFieldFormatter extends FormatterBase
{

    /**
     *
     * {@inheritdoc}
     */
    public static function defaultSettings()
    {
        return [
            'layoutgenentitystyles_view' => 'more_fields/field-accordion'
        ] + parent::defaultSettings();
    }

    /**
     *
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        return [
            // utilile pour mettre à jour le style
            'layoutgenentitystyles_view' => [
                '#type' => 'hidden',
                '#value' => 'more_fields/field-accordion'
            ]
        ] + parent::settingsForm($form, $form_state);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function settingsSummary()
    {
        $summary = [];
        // Implement settings summary.

        return $summary;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];
        foreach ($items as $delta => $item) {
            $elements[$delta] = [
                '#theme' => 'more_fields_accordion_field_formatter',
                '#item' => [
                    'icon' => Html::escape($item->icon),
                    'title' => Html::escape($item->title),
                    'description' => Html::escape($item->description),
                    'id' => $this->getName(15)
                ]
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
    protected function viewValue($value)
    {
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


    public function getName($n)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }
}
