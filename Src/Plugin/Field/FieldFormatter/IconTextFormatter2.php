<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'more_fields_icon_text_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_icon_text_formatter 2 ",
 *   label = @Translation("Icon text formatter 2"),
 *   field_types = {
 *     "more_fields_icon_text"
 *   }
 * )
 */
class IconTextFormatter2 extends IconTextFormatter
{

  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings()
  {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-icon-svg'
    ] + parent::defaultSettings();
  }

  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
    return [
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        '#value' => 'more_fields/field-icon-svg'
      ]
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $elements = [];
    foreach ($items as $delta => $item) {

      $elements[$delta] = [
        '#theme' => 'more_fields_more_fields_icon_text_svg',
        '#item' => [
          'value' => Html::escape($item->value),
          'text' => $item->text
        ]
      ];
    }
    return $elements;
  }
}
