<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_link_htl_formatter",
 *   label = @Translation("Button Htl Btn"),
 *   description = "ideal for link elements, i.e. containing the a tag",
 *   field_types = {
 *     "link",
 *   }
 * )
 */
class LinkHtlBtnFormatter extends LinkFormatter {
  use TraitHtlBtn;
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as &$element) {
      $element['#options']['attributes']['class'][] = 'htl-btn';
      $element['#options']['attributes']['class'][] = $this->getSetting('size');
      $element['#options']['attributes']['class'][] = $this->getSetting('variant');
      $element['#options']['attributes']['class'][] = !$this->getSetting('haslinktag') ? 'hasnotlink' : '';
      $element['#options']['attributes']['class'][] = $this->getSetting('custom_class');
    }
    return $elements;
  }
  
}