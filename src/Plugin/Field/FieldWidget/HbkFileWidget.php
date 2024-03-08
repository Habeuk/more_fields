<?php

namespace Drupal\more_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\more_fields_video\Services\MoreFieldsVideoConverter;
use Drupal\more_fields_video\Entity\MultiformatVideo;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Plugin implementation of the 'file_generic' widget.
 *
 * @FieldWidget(
 *   id = "hbk_file_generic",
 *   label = @Translation("Galleries File"),
 *   field_types = {
 *     "file",
 *     "more_fields_hbk_file"
 *   }
 * )
 */
class HbkFileWidget extends FileWidget {
  
  /**
   * The element info manager.
   *
   * @var MoreFieldsVideoConverter $videoConverter
   */
  protected $videoConverter;
  
  /**
   *
   * @var EntityStorageInterface $fileHandler
   */
  protected $fileHandler;
  
  /**
   *
   * @var EntityStorageInterface $multiformatHandler
   */
  protected $multiformatHandler;
  
  /**
   *
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, MoreFieldsVideoConverter $video_converter, EntityStorageInterface $file_handler, EntityStorageInterface $multiformat_handler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info);
    $this->videoConverter = $video_converter;
    // $this->entityManager = $entity_manager;
    $this->multiformatHandler = $multiformat_handler;
    $this->fileHandler = $file_handler;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'), $container->get("more_fields_video.video_converter"), $container->get('entity_type.manager')->getStorage("file"), $container->get('entity_type.manager')->getStorage("multiformat_video"));
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#element_validate'][] = [
      $this,
      'validateElement'
    ];
    return $element;
  }
  
  /**
   * handling the validation of the field
   * and create the thumbnail for videos
   */
  public function validateElement($element, FormStateInterface &$form_state, $form) {
    if (\Drupal::moduleHandler()->moduleExists('more_fields_video')) {
      $vid_extensions = [
        'mp4',
        'ogv',
        'webm'
      ];
      /**
       *
       * @var File $file
       */
      foreach ($element["#files"] as $id => $file) {
        $fileUri = $file->getFileUri();
        $fileExtension = pathinfo($fileUri, PATHINFO_EXTENSION);
        if (in_array($fileExtension, $vid_extensions)) {
          # code...
          $multiformat = $this->multiformatHandler->load($id);
          if (!$multiformat) {
            # code...
            $result = $this->videoConverter->createThumbFile($id);
            if ($result !== FALSE) {
              $this->sync_multiformat($id, $result);
            }
          }
        }
      }
    }
  }
  
  /**
   *
   * @var File $thumb_file
   */
  public function sync_multiformat($video_id, File $thumb_file) {
    // creating and handling the multiformat
    /**
     *
     * @var MultiformatVideo $multiformat
     */
    $multiformat = $this->multiformatHandler->load($video_id) ?? $this->multiformatHandler->create();
    $thumb_file->setPermanent();
    $thumb_file->save();
    $multiformat->setThumbId($thumb_file->id());
    $multiformat->setVideoId($video_id);
    $multiformat->save();
    return $multiformat;
  }
  
}
