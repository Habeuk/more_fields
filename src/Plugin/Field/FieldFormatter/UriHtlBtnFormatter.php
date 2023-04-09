<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_uri_htl_formatter",
 *   label = @Translation("Button Htl Btn"),
 *   description = "ideal for link elements, i.e. containing the a tag",
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class UriHtlBtnFormatter extends ButtonHtlBtnFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    
    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $elements[$delta] = [
          '#type' => 'link',
          '#url' => Url::fromUri($item->value),
          '#title' => $item->value,
          '#options' => [
            'attributes' => [
              'class' => [
                'htl-btn',
                $this->getSetting('size'),
                $this->getSetting('variant'),
                !$this->getSetting('haslinktag') ? 'hasnotlink' : ''
              ]
            ]
          ]
        ];
      }
    }
    
    return $elements;
  }
  
}