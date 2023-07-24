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
 *   id = "restrained_text",
 *   label = @Translation("Restrained text"),
 *   field_types = {
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class restrainedTextLongFormatter extends StringFormatter
{


    /**
     *
     * {@inheritdoc}
     */
    public static function defaultSettings()
    {
        return [
            'layoutgenentitystyles_view' => 'more_fields/restrained-field',
            'resumed' => 300,
            'blur_size' => '20%',
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
                "#value" => $this->getSetting("layoutgenentitystyles_view"),
            ],
            'resumed' => [
                '#title' => t('Nombre de caractère'),
                '#type' => 'number',
                '#default_value' => $this->getSetting("resumed"),
            ],
            'blur_size' => [
                '#title' => t('taille de la zone masquée'),
                '#type' => 'textfield',
                '#value' => $this->getsetting("blur_size"),
            ]
        ] + parent::settingsForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];
        // The ProcessedText element already handles cache context & tag bubbling.
        // @see \Drupal\filter\Element\ProcessedText::preRenderText()
        foreach ($items as $delta => $item) {
            // $escapedItem = Html::escape($item->value);
            $escapedItem = $item->value;
            $value =  !(\Drupal::currentUser()->id()) ? $escapedItem : Truncator::truncate($escapedItem, $this->getSetting("resumed"));
            $elements[$delta] = [
                '#theme' => 'restrained_text_formatter',
                '#item' => [
                    'value' => $value,
                    'blur_size' => $this->getSetting("blur_size")
                ]
            ];
        }
        // dd($elements);
        return $elements[0];
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
}
