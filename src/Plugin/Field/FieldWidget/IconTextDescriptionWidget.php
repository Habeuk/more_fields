<?php

namespace Drupal\more_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin implementation of the 'more_fields_icon_text_widget' widget.
 *
 * @FieldWidget(
 *   id = "more_fields_icon_text_description_widget",
 *   module = "more_fields",
 *   label = @Translation("Icon text description widget"), *
 *   field_types = {
 *     "more_fields_icon_text"
 *   }
 * )
 */
class IconTextDescriptionWidget extends IconTextWidget {
  
  /**
   *
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $elements = [];
    if (!empty($element['#title_display']))
      unset($element['#title_display']);
    $elements['value'] = [
      '#title' => t($this->getSetting('label_1')),
      '#type' => 'text_format',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#description' => 'cest un champs type varchar, le but est mettre du html'
    ] + $element;
    $elements['text'] = [
      '#title' => t($this->getSetting('label_2')),
      '#type' => 'text_format',
      '#format' => isset($items[$delta]->format) ? $items[$delta]->format : 'basic_html',
      '#default_value' => isset($items[$delta]->text) ? $items[$delta]->text : NULL
    ] + $element;
    
    return $elements;
  }
  
  function massageFormValues($values, $form, $form_state) {
    $vals = parent::massageFormValues($values, $form, $form_state);
    foreach ($vals as &$val) {
      if (isset($val['text']['format'])) {
        $val['format'] = $val['text']['format'];
      }
      if (isset($val['text']['value'])) {
        $val['text'] = $val['text']['value'];
      }
      if (isset($val['value']['value'])) {
        $val['value'] = $val['value']['value'];
      }
    }
    return $vals;
  }
  
}
