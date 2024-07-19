<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_button_htl_formatter",
 *   label = @Translation("Button Htl Btn"),
 *   description = "ideal for link elements, i.e. containing the a tag",
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ButtonHtlBtnFormatter extends StringFormatter {
  use TraitHtlBtn;
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements[] = [
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'htl-btn',
            $this->getSetting('size'),
            $this->getSetting('variant'),
            !$this->getSetting('haslinktag') ? 'hasnotlink' : ''
          ]
        ],
        parent::viewElements($items, $langcode)
      ]
    ];
    return $elements;
  }
  
}