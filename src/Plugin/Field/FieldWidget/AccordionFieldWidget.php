<?php

namespace Drupal\more_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin implementation of the 'more_fields_accordion_field' widget.
 *
 * @FieldWidget(
 *   id = "more_fields_accordion_field_widget",
 *   module = "more_fields",
 *   label = @Translation("Accordion field widget type"),
 *   field_types = {
 *     "more_fields_accordion_field"
 *   }
 * )
 */
class AccordionFieldWidget extends WidgetBase {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label_1' => "Title",
      'label_2' => "Icon",
      'label_3' => "Description"
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['label_1'] = [
      '#type' => 'textfield',
      '#title' => t('label 1'),
      '#default_value' => $this->getSetting('label_1')
    ];
    $elements['label_2'] = [
      '#type' => 'textfield',
      '#title' => t('label 2'),
      '#default_value' => $this->getSetting('label_2')
    ];
    $elements['label_3'] = [
      '#type' => 'textfield',
      '#title' => t('label 3'),
      '#default_value' => $this->getSetting('label_3')
    ];
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $elements = [];
    if (!empty($element['#title_display']))
      unset($element['#title_display']);
    $elts['title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL
    ] + $element;
    $elts['field'] = [
      '#type' => 'details',
      '#title' => t('Description + Icon'),
      '#tree' => True
    ];
    //
    $elts['field']['icon'] = [
      '#title' => $this->t('Icon'),
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#default_value' => isset($items[$delta]->icon) ? $items[$delta]->icon : NULL
    ] + $element;
    //
    $elts['field']['description'] = [
      '#title' => $this->t('Description'),
      '#format' => isset($items[$delta]->format) ? $items[$delta]->format : 'basic_html',
      '#type' => 'text_format',
      '#default_value' => isset($items[$delta]->description) ? $items[$delta]->description : NULL
    ] + $element;
    return $elts;
  }
  
  function massageFormValues($values, $form, $form_state) {
    $vals = parent::massageFormValues($values, $form, $form_state);
    foreach ($vals as $k => &$val) {
      if (empty($val['title'])) {
        unset($vals[$k]);
        continue;
      }
      if (isset($val['field']['description']['format'])) {
        $val['format'] = $val['field']['description']['format'];
      }
      if (isset($val['icon']['format'])) {
        $val['format'] = $val['field']['icon']['format'];
      }
      $val['description'] = $val['field']['description']['value'];
      $val['icon'] = $val['field']['icon']['value'];
    }
    return $vals;
  }
  
}
