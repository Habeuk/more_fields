<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\more_fields\Truncator;

/**
 * Plugin implementation of the 'text_long, text_with_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "relativeDate",
 *   label = @Translation("Relative date"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class RelativeDateFormatter extends StringFormatter {


    /**
     *
     * {@inheritdoc}
     */
    public static function defaultSettings() {
        // 'layoutgenentitystyles_view' => 'more_fields/restrained-field',
        return [
            'format' => 'd/m/y',
            'base_time' => null,
        ] + parent::defaultSettings();
    }


    /**
     *
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        // // utilile pour mettre à jour le style
        return [
            'layoutgenentitystyles_view' => [
                '#type' => 'hidden',
                "#value" => $this->getSetting("layoutgenentitystyles_view"),
            ],
            'format' => [
                '#title' => t('format d\'affichage de la date'),
                '#type' => 'textfield',
                '#default_value' => $this->getSetting("format"),
            ],
            'base_time' => [
                '#title' => t('date de base'),
                '#type' => 'textfield',
                '#value' => $this->getsetting("base_time"),
            ]
        ] + parent::settingsForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = [];
        foreach ($items as $delta => $item) {
            $baseTime = $this->getSetting("base_time");
            $time = strtotime($item->value, ($baseTime) ? $baseTime : null);
            $time = $time ? $time : null;
            // $elements[$delta] = (string) Date($item->value, $time);
            $elements[$delta] = $this->viewValue((string) Date((string) $this->getSetting("format"), $time));
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
