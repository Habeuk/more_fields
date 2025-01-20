<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
// use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\fullswiperoptions\Fullswiperoptions;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;

/**
 * Plugin implementation of the 'FieldGalleries' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_image_limiter",
 *   label = @Translation("Image limiter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageLimiterFormatter extends ImageFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'number_image' => ''
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['number_image'] = [
      '#title' => $this->t('Image limiter'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('number_image')
    ];
    return $element;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $limit = (int) $this->getSetting('number_image');
    $values = $items->getValue();
    if ($limit > 0 && count($values) > $limit) {
      $limited_items = array_slice($values, 0, $limit);
      $items->setValue($limited_items);
    }
    $elements = parent::viewElements($items, $langcode);
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter::settingsSummary()
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Image limit: @limit', [
      '@limit' => $this->getSetting('image_limit')
    ]);
    return array_merge($summary, parent::settingsSummary());
  }
}