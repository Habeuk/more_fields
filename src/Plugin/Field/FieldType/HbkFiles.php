<?php

namespace Drupal\more_fields\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'more_fields_icon_text' field type.
 *
 * @FieldType(
 *   id = "more_fields_hbk_file",
 *   label = @Translation("HBK Galleries Files (videos and images) "),
 *   description = @Translation("This field stores the ID of a file(video or image) as an integer value."),
 *   default_widget = "file_generic",
 *   default_formatter = "more_fields_hbk_file_formatter",
 *   category = "Complex fields",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class HbkFiles extends FileItem {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $parentFieldSettings = parent::defaultFieldSettings();
    if (isset($parentFieldSettings["file_extensions"])) {
      unset($parentFieldSettings["file_extensions"]);
    }
    return [
      'file_extensions' => 'mp4,ogv,webm,png,gif,jpg,jpeg,webp',
      'image_extensions' => 'png, gif, jpg, jpeg, webp',
      'video_extensions' => 'mp4, ogv, webm'
    ] + $parentFieldSettings;
  }
  
/**
 *
 * {@inheritdoc}
 */
  // public function fieldSettingsForm(array $form, FormStateInterface
  // $form_state) {
  // $settings = $this->getSettings();
  // $element = parent::fieldSettingsForm($form, $form_state);
  // $element["image_extensions"] = $element["file_extensions"];
  // $element["video_extensions"] = $element["file_extensions"];
  // $element["image_extensions"]["#default_value"] =
  // $settings["image_extensions"] ?? "";
  // $element["video_extensions"]["#default_value"] =
  // $settings["video_extensions"] ?? "";
  // unset($element["file_extensions"]);
  // // dump([$element, $settings]);
  // return $element;
  // }
}
