<?php

namespace Drupal\more_fields_video\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\more_fields_video\MultiformatVideoInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use FFMpeg\FFMpeg;
use Drupal\file\Entity\File;

/**
 * Defines the multiformat video entity class.
 *
 * @ContentEntityType(
 *   id = "multiformat_video",
 *   label = @Translation("MultiFormat Video"),
 *   label_collection = @Translation("MultiFormat Videos"),
 *   label_singular = @Translation("multiformat video"),
 *   label_plural = @Translation("multiformat videos"),
 *   label_count = @PluralTranslation(
 *     singular = "@count multiformat videos",
 *     plural = "@count multiformat videos",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\more_fields_video\MultiformatVideoListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\more_fields_video\Form\MultiformatVideoForm",
 *       "edit" = "Drupal\more_fields_video\Form\MultiformatVideoForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\more_fields_video\Routing\MultiformatVideoHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "multiformat_video",
 *   admin_permission = "administer multiformat video",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/multiformat-video",
 *     "add-form" = "/multiformat-video/add",
 *     "canonical" = "/multiformat-video/{multiformat_video}",
 *     "edit-form" = "/multiformat-video/{multiformat_video}",
 *     "delete-form" = "/multiformat-video/{multiformat_video}/delete",
 *   },
 *   field_ui_base_route = "entity.multiformat_video.settings",
 * )
 */
class MultiformatVideo extends ContentEntityBase implements MultiformatVideoInterface {

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields['thumb'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('file thumbnail id'))
            ->setDescription(t('The id of the generated file for thumb'))
            ->setSettings([
                'unsigned' => false,
                'min' => 1
            ]);
        return $fields;
    }

    /**
     * set thumbnail
     * @param int $video_id
     */
    public function createThumb($video_id) {
        // $file = File::load($video_id)
    }
}
