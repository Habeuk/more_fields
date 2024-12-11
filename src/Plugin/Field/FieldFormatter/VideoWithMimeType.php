<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\video\Plugin\Field\FieldFormatter\VideoPlayerListFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'experience_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_video_with_converter",
 *   label = @Translation("Display videos with type"),
 *   field_types = {
 *     "video"
 *   }
 * )
 */
class VideoWithMimeType extends VideoPlayerListFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      "layoutgenentitystyles_view" => "more_fields/more_fields_video_with_converter",
      "control_js" => false
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['layoutgenentitystyles_view'] = [
      '#type' => 'hidden',
      "#value" => $this->getSetting("layoutgenentitystyles_view")
    ];
    $elements['control_js'] = [
      '#title' => t('Controls with JS'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('control_js')
    ];
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);
    
    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }
    
    // Collect cache tags to be added for each item in the field.
    $video_items = [];
    foreach ($files as $file) {
      /**
       *
       * @var \Drupal\file\Entity\File $file
       */
      $video_items[] = [
        'src' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
        'filemime' => $file->getMimeType()
      ];
    }
    $valids_attributes = [
      "width" => "width",
      "height" => "height",
      "autoplay" => "autoplay",
      "loop" => "loop",
      "muted" => "muted",
      "preload" => "preload",
      "controls" => "controls"
    ];
    $settings = $this->getSettings();
    $videos_settings = [
      'class' => [
        'videos_control',
        $this->getSetting("control_js") ? "with_js" : ""
      ]
    ];
    foreach ($valids_attributes as $value) {
      if (!empty($settings[$value]))
        $videos_settings[$value] = $settings[$value];
    }
    
    $elements[] = [
      '#theme' => 'more_fields_video_player_with_type_formatter',
      '#items' => $video_items,
      '#video_attributes' => new Attribute($videos_settings),
      '#all_settings' => $settings
    ];
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TRUE;
  }
}