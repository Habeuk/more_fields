<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
// use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'FieldGalleries' formatter.
 *
 * @FieldFormatter(
 *   id = "more_field_mit_gallery_formatter",
 *   label = @Translation("Field Mitor Gallery"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class FieldMitorGalleryFormatter extends ImageFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      "layoutgenentitystyles_view" => "more_fields/field-gallery-mitor",
    ]+parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['layoutgenentitystyles_view'] = [
      '#type' => 'hidden',
      // "#value" => "more_fields/field-files",
      "#value" => $this->getSetting("layoutgenentitystyles_view")
    ];
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('gabarit: @gabarit', [
      '@gabarit' => $this->getSetting('gabarit')
    ]);
    return array_merge($summary, parent::settingsSummary());
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [
      '#theme' => 'more_field_mit_gallery_formatter',
      'items' => parent::viewElements($items, $langcode),
    ];
    return $elements;
  }  
}