<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'experience_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_niveau_progress_bar",
 *   label = @Translation("Value Niveau formatter with progress bar"),
 *   field_types = {
 *     "more_fields_value_niveau_type"
 *   }
 * )
 */
class ValueNiveauProgressBar extends ValueNiveauFormatterType {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-skill-region',
      'custom_class' => '',
      'custom_class_item' => 'col-md-6 justify-content-center align-items-center',
      'custom_class_titre' => 'font-weight-bolder h6',
      'custom_class_progress' => ''
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // utilile pour mettre à jour le style.
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        '#value' => 'more_fields/field-skill-region'
      ]
    ] + parent::settingsForm($form, $form_state);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $taxonomy_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    //
    foreach ($items as $delta => $item) {
      /**
       *
       * @var \Drupal\taxonomy\Entity\Term $term
       */
      $term = $taxonomy_term->load($item->target_id);
      if (!$term)
        continue;
      if ($term->hasTranslation($langcode)) {
        $term = $term->getTranslation($langcode);
      }
      $name = $term->label();
      if (!$name)
        continue;
      $niveau = $item->niveau * 20;
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'field-skill-region',
            $this->getSetting('custom_class_item')
          ]
        ],
        // Titre
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'text',
              $this->getSetting('custom_class_titre')
            ]
          ],
          $this->viewValue($name)
        ],
        // Progress
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'progress bar',
              $this->getSetting('custom_class_progress')
            ]
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => [
              'class' => [
                'niveau',
                'progress-bar niveau'
              ],
              'style' => 'width:' . $niveau . '%;',
              'aria-valuemin' => 0,
              'aria-valuemax' => 100,
              'aria-valuenow' => $niveau,
              'role' => 'progressbar'
            ]
          ]
        ]
      ];
    }
    //
    $container[] = [
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'skill row',
            $this->getSetting('custom_class')
          ]
        ],
        $elements
      ]
    ];
    return $container;
  }
  
}
