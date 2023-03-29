<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'experience_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_date_under_text",
 *   label = @Translation("Experience formatter model date au dessus de la description "),
 *   field_types = {
 *     "more_fields_experience_type"
 *   }
 * )
 */
class ExperienceDateUnderText extends ExperienceFormatterType {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-title',
      'custom_class_titre' => 'font-weight-bolder text-dark h6',
      'custom_class_date' => 'h6',
      'custom_class_text' => ''
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
        '#value' => 'more_fields/field-title'
      ]
    ] + parent::settingsForm($form, $form_state);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'field-content'
          ]
        ],
        // competenace, entreprise, London
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              $this->getSetting('custom_class_titre')
            ]
          ],
          [
            '#type' => 'inline_template',
            '#template' => '{{ value|raw }}, ',
            '#context' => [
              'value' => $item->value
            ]
          ],
          [
            '#type' => 'inline_template',
            '#template' => '{{ value|raw }}, ',
            '#context' => [
              'value' => $item->company
            ]
          ],
          [
            '#type' => 'inline_template',
            '#template' => '{{ value|raw }}, ',
            '#context' => [
              'value' => $item->address
            ]
          ]
        ],
        // dates
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              $this->getSetting('custom_class_date')
            ]
          ],
          [
            '#type' => 'inline_template',
            '#template' => '{{ value|raw }} - ',
            '#context' => [
              'value' => $this->getDate($item->date_debut)
            ]
          ],
          [
            '#type' => 'inline_template',
            '#template' => '{{ value|raw }} ',
            '#context' => [
              'value' => $this->getDate($item->date_fin)
            ]
          ]
        ],
        // text
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              $this->getSetting('custom_class_text')
            ]
          ],
          $this->viewValue($item->description)
        ]
      ];
    }
    $container[] = [
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'field-title',
            $this->getSetting('custom_class')
          ]
        ],
        $elements
      ]
    ];
    return $container;
  }
  
}
