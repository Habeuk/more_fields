<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Plugin implementation of the 'experience_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_experience",
 *   label = @Translation("Experience formatter type"),
 *   field_types = {
 *     "more_fields_experience_type"
 *   }
 * )
 */
class ExperienceFormatterType extends FormatterBase {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/time-line'
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Utilile pour mettre à jour le style
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        '#value' => 'more_fields/time-line'
      ]
    ] + parent::settingsForm($form, $form_state);
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
    $elements = [];
    
    foreach ($items as $delta => $item) {
      $date_debut = '';
      if (!empty($item->date_debut)) {
        $date_debut = DrupalDateTime::createFromTimestamp($item->date_debut);
        $date_debut = $date_debut->format("m/Y");
      }
      
      $date_fin = '';
      if (!empty($item->date_fin)) {
        $date_fin = DrupalDateTime::createFromTimestamp($item->date_fin);
        $date_fin = $date_fin->format("m/Y");
      }
      
      $elements[$delta] = [
        '#theme' => 'more_fields_experience_formatter',
        '#item' => [
          'value' => Html::escape($item->value),
          'company' => Html::escape($item->company),
          'address' => Html::escape($item->address),
          'date_debut' => $date_debut,
          'date_fin' => $date_fin,
          'description' => $item->description,
          'en_poste' => $item->en_poste
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
  
  protected function getDate($date_string, $format = "m/Y") {
    if (!empty($date_string)) {
      $date = DrupalDateTime::createFromTimestamp($date_string);
      if ($date)
        return $date->format("m/Y");
    }
    return null;
  }
  
}
