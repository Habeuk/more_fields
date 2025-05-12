<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'FieldGalleries' formatter.
 *
 * @FieldFormatter(
 *   id = "more_field_mit_gallery_formatter",
 *   label = @Translation("Mitor Gallery (Grid)"),
 *   field_types = {
 *     "image"
 *   },
 *   multiple = true,
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
      "container_class" => "",
      "item_class" => ""
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['container_class'] = [
      "#type" => "text_field",
      "#title" => $this->t('Container class'),
      '#default_value' => $this->getSetting('container_class')
    ];
    $elements['item_class'] = [
      "#type" => "text_field",
      "#title" => $this->t('Item class'),
      '#default_value' => $this->getSetting('item_class')
    ];
    $elements['layoutgenentitystyles_view'] = [
      '#type' => 'hidden',
      "#value" => "more_fields/field-gallery-mitor"
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
      'items' => parent::viewElements($items, $langcode)
    ];
    return $elements;
  }
}