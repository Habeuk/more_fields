<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\video\Plugin\Field\FieldFormatter\VideoPlayerListFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Template\Attribute;

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
    
    $settings = array_filter($this->getSettings(), function ($item) {
      return $item ? true : false;
    });
    $elements[] = [
      '#theme' => 'more_fields_video_player_with_type_formatter',
      '#items' => $video_items,
      '#video_attributes' => new Attribute($settings)
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