<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_read_more",
 *   label = @Translation("Read more formatter"),
 *   field_types = {
 *     "string",
 *     "uri",
 *     "integer",
 *   }
 * )
 */
class ReadMoreFormatter extends StringFormatter {
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
    }
    return $elements;
  }
  
}