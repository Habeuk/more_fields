<?php

namespace Drupal\more_fields\Plugin\Field\FieldWidget;

use Drupal\video\Plugin\Field\FieldWidget\VideoUploadWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Plugin implementation of the 'file_generic' widget.
 * NB: ce champs est limiter au selecteur multiple.
 *
 * @FieldWidget(
 *   id = "more_fields_upload_videos",
 *   label = @Translation("Video Upload with converter or check valid type"),
 *   field_types = {
 *     "video"
 *   }
 * )
 */
class VideoWithConverter extends VideoUploadWidget {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'file_extensions' => 'mp4 ogg webm mov quicktime',
      'check_format_mp4_h264' => true,
      'convert_video' => 'webm'
    ] + parent::defaultSettings();
    return $settings;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $alert_msg = '';
    if (!\Drupal::moduleHandler()->moduleExists('more_fields_video')) {
      $alert_msg = 'Module more_fields_video must be installed to use this features.';
    }
    $elements['check_format_mp4_h264'] = [
      "#type" => 'checkbox',
      '#title' => 'Verifie le format de la video',
      '#default_value' => $this->getSetting('check_format_mp4_h264')
    ];
    /**
     * Pour l'instant on n'a pas encore trouver le moyen d'injecter les données
     * dans le formulaire qui va etre enregistrer.
     * on va convertir le fichier video en webm.
     */
    $elements['convert_video'] = [
      "#type" => 'radios',
      '#title' => t("Select conversion formats"),
      '#default_value' => $this->getSetting('convert_video'),
      '#options' => [
        'mp4' => 'mp4',
        'ogg' => 'ogg',
        'webm' => 'webm'
      ],
      '#description' => t(
        "Select the formats of the concerts if necessary.ie if the video does not correspond to any of the formats all the formats are generated, otherwise except the missing formats are added.  
<div class='alert'> $alert_msg </div>")
    ];
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#element_validate'][] = [
      $this,
      'validateElementFileVideo'
    ];
    return $element;
  }
  
  /**
   * On part du postula que cest la meme video qui peut etre forunit dans des
   * format differents.
   * On va ajouter des formats manquant.
   *
   * @param array $element
   * @param FormStateInterface $form_state
   * @param array $form
   */
  public function validateElementFileVideo($element, FormStateInterface &$form_state, $form) {
    if (\Drupal::moduleHandler()->moduleExists('more_fields_video')) {
      // $files = [];
      $default_file = null;
      /**
       *
       * @var \Drupal\more_fields_video\Services\MoreFieldsVideoConverter $video_converter
       */
      $video_converter = \Drupal::service("more_fields_video.video_converter");
      $outputFormat = $this->getSetting('convert_video');
      
      $outputFormat = !is_array($outputFormat) ? [
        $outputFormat
      ] : $outputFormat;
      $outputFormat = array_filter($outputFormat, function ($item) {
        if ($item)
          return (bool) $item;
      });
      
      foreach ($element["#files"] as $file) {
        if (!$default_file)
          $default_file = $file;
        // verification des formats.
        $fileMime = explode("/", $file->getMimeType());
        if ($fileMime[0] === "video") {
          $extention = $fileMime[1];
          if ($extention == 'mp4' && $video_converter->isMp4H264($file)) {
            $index = array_search("mp4", $outputFormat);
            if ($index !== false) {
              unset($outputFormat[$index]);
            }
          }
          elseif ($extention == 'ogg') {
            $index = array_search("ogg", $outputFormat);
            if ($index !== false) {
              unset($outputFormat[$index]);
            }
          }
          elseif ($extention == 'webm') {
            $index = array_search("webm", $outputFormat);
            
            if ($index !== false) {
              unset($outputFormat[$index]);
            }
          }
        }
      }
      if ($default_file) {
        $newFiles = $video_converter->ConvertVideoToRightFormat($default_file, $outputFormat, true);
      /**
       * En attendant de trouver une approche permettant d'ajouter les fichiers.
       */
        // if ($newFiles) {
        // $files += $newFiles;
        // }
        // if ($files) {
        // $fieldName = $element['#field_name'];
        // $values = [];
        // foreach ($files as $file) {
        // $values[] = [
        // 'target_id' => $file->id()
        // ];
        // }
        // dd($entity->get($fieldName)->target_id);
        // }
      }
    }
  }
}