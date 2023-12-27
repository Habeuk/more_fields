<?php

namespace Drupal\more_fields\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Element\ManagedFile;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\more_fields_video\Services\MoreFieldsVideoConverter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\more_fields_video\Entity\MultiformatVideo;

/**
 * Plugin implementation of the 'file_generic' widget.
 *
 * @FieldWidget(
 *   id = "hbk_file_generic",
 *   label = @Translation("File"),
 *   field_types = {
 *     "file",
 *     "more_fields_hbk_file"
 *   }
 * )
 */
class HbkFileWidget extends FileWidget {

    /**
     * The element info manager.
     * @var MoreFieldsVideoConverter $videoConverter
     */
    protected $videoConverter;
    /**
     * @var EntityTypeManagerInterface $entityManager
     */
    protected $entityManager;


    /**
     * {@inheritdoc}
     */
    public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, MoreFieldsVideoConverter $video_converter, EntityTypeManagerInterface $entity_manager) {
        parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info);
        $this->videoConverter = $video_converter;
        $this->entityManager = $entity_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $plugin_id,
            $plugin_definition,
            $configuration['field_definition'],
            $configuration['settings'],
            $configuration['third_party_settings'],
            $container->get('element_info'),
            $container->get("more_fields_video.video_converter"),
            $container->get('entity_type.manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        $element['#element_validate'][] = [$this, 'validateElement'];
        return $element;
    }


    /**
     * handling the validation of the field
     * and create the thumbnail for videos
     */
    public function validateElement($element, FormStateInterface &$form_state, $form) {
        $vid_extensions = ['mp4', 'ogv', 'webm'];
        /**
         * @var File $file
         */
        foreach ($element["#files"] as $id => $file) {
            $fileUri = $file->getFileUri();
            $fileExtension = pathinfo($fileUri, PATHINFO_EXTENSION);
            if (in_array($fileExtension, $vid_extensions)) {
                # code...
                $multiformat = $this->entityManager->getStorage("multiformat_video")->load($id);
                if (!$multiformat) {
                    # code...
                    $result = $this->videoConverter->createThumbFile($id);
                    if ($result !== FALSE) {
                        $this->sync_multiformat($id, $result);
                    }
                }
                if ($file->isPermanent()) {
                    /**
                     * @var MultiformatVideo $multiformat
                     */
                    $multiformat = $multiformat ?? $this->entityManager->getStorage("multiformat_video")->load($id);
                    $thumb_id = $multiformat->getThumbId();

                    /**
                     * @var File $thumb_file
                     */
                    $thumb_file = $this->entityManager->getStorage("file")->load($thumb_id);
                    if (!$thumb_file->isPermanent()) {
                        $thumb_file->setPermanent();
                    }
                }
            }
        }
        // dd($form);
    }



    public function sync_multiformat($video_id, $thumb_uri) {

        /**
         * @var File $thumb_file
         */
        $thumb_file = $this->entityManager->getStorage("file")->create();
        $thumb_file->setFileUri($thumb_uri);
        $thumbId = $thumb_file->save();

        //creating and handling the multiformat
        /**
         * @var MultiformatVideo $multiformat
         */
        $multiformat = $this->entityManager->getStorage('multiformat_video')->load($video_id) ??  $this->entityManager->getStorage("multiformat_video")->create();
        $multiformat->setThumbId($thumbId);
        $multiformat->setVideoId($video_id);
        return $multiformat->save();
    }
}
