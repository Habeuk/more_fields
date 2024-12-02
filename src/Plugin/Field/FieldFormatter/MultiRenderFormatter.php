<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Exception;
use Drupal\Core\Template\Attribute;

/**
 * Plugin implementation of the 'more_fields_multirender_integer' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_multirender_integer",
 *   label = @Translation("Multirender Formatter"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class MultiRenderFormatter extends FormatterBase {

    /**
     * @return array
     */
    public function getFieldFormatterSettings() {
        $settings = $this->getSettings();
        if (empty($settings)) {
            $settings = $this->defaultSettings();
        }
        return $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = [];
        $settings = $this->getFieldFormatterSettings();

        $total = $settings["total"];
        $filled_icon = $settings['filled_icon']["value"] ?? $settings['filled_icon'];
        $unfilled_icon = $settings['unfilled_icon']["value"] ?? $settings['unfilled_icon'];

        foreach ($items as $delta => $item) {
            $value = (int)$item->value;
            if ($total != -1 && ($value > $total || $value < 0)) {
                throw new Exception("the value should be strictly positive and lesser than " . $total, 409);
            }

            $elements[$delta] = [
                '#theme' => "more_fields_multirender_formatter",
                '#item' => [
                    'filled_icon' => $filled_icon,
                    'unfilled_icon' => $unfilled_icon,
                    'total' => $total == -1 ? $value : $total,
                    'value' => $value
                ],
                '#attributes' => new Attribute(
                    [
                        "class" => \explode(" ", $settings["class"])
                    ]
                )
            ];
        }

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsSummary() {
        $summary = [];
        $settings = $this->getFieldFormatterSettings();
        $filled_icon = $settings["filled_icon"]["value"] ?? $settings["filled_icon"] ?? "empty";
        $maxSummaryLength = 60;
        if (strlen($filled_icon) > $maxSummaryLength) {
            $filled_icon = substr($filled_icon, 0, $maxSummaryLength) . '...';
        }
        $unfilled_icon = $settings["unfilled_icon"]["value"] ?? $settings["unfilled_icon"] ?? "empty";
        if (strlen($unfilled_icon) > $maxSummaryLength) {
            $unfilled_icon = substr($unfilled_icon, 0, $maxSummaryLength) . '...';
        }
        $summary[] = $this->t('Displays the integer value with custom formatting.');
        $summary[] = $this->t('Max number: @max', ['@max' => $this->getSetting('max_number')]);
        $summary[] = $this->t('Min number: @min', ['@min' => $this->getSetting('min_number')]);
        $summary[] = $this->t('Filled icon: @filled', ['@filled' => $filled_icon]);
        $summary[] = $this->t('Unfilled icon: @unfilled', ['@unfilled' => $unfilled_icon]);
        return $summary;
    }

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings() {

        return [
            'class' => "d-flex gap-3",
            'total' => 5,
            'min_number' => 5,
            'filled_icon' => '<svg  viewBox="0 0 15 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="m7.5 12.04-4.326 2.275L4 9.497.5 6.086l4.837-.703L7.5 1l2.163 4.383 4.837.703L11 9.497l.826 4.818z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'unfilled_icon' => '<svg  viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m7.5 12.04-4.326 2.275L4 9.497.5 6.086l4.837-.703L7.5 1l2.163 4.383 4.837.703L11 9.497l.826 4.818z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ] + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        $elements = [];
        $settings = $this->getFieldFormatterSettings();

        $elements['total'] = [
            '#type' => 'number',
            '#description' => "put -1 for unlimited",
            '#title' => $this->t('Total element'),
            '#min' => -1,
            '#default_value' => $settings['total'],
            '#required' => TRUE,
        ];

        $elements['filled_icon'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Filled icon'),
            '#default_value' => $settings['filled_icon']["value"] ?? $settings["filled_icon"],
            '#format' => 'full_html',
            '#required' => TRUE,
        ];

        $elements['unfilled_icon'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Unfilled icon'),
            '#default_value' => $settings['unfilled_icon']["value"] ?? $settings['unfilled_icon'],
            '#format' => 'full_html',
            '#required' => TRUE,
        ];

        $elements['class'] = [
            '#type' => 'textfield',
            '#title' => $this->t('fieldClass'),
            '#default_value' => $settings['class'],
            '#format' => 'full_html',
        ];

        return $elements;
    }
}
