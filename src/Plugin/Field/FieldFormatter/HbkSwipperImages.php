<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formatage_models\Plugin\Field\FieldFormatter\SwiperjsImageFormatter;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;

/**
 * Plugin implementation of the 'text_long, text_with_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_hbk_swiper_img_formatter",
 *   label = @Translation("Swipper only images with Swiper"),
 *   description = "Doit etre ameliorer, afin d'eviter de charger les videos",
 *   field_types = {
 *     "more_fields_hbk_file"
 *   }
 * )
 */
class HbkSwipperImages extends SwiperjsImageFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [ //
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    //
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /**
     *
     * @var ItemList $items
     */
    $values = $items->getValue();
    foreach ($values as $value) {
      $fid = $value['target_id'] ?? false;
      if ($fid) {
        $file = \Drupal\file\Entity\File::load($fid);
        if ($file && str_contains($file->getMimeType(), "video")) {
          // Les index sont reconstruit apres chaque remove.
          $delta_to_delete = $this->findIndexByFid($items, $fid);
          if ($delta_to_delete !== NULL)
            $items->removeItem($delta_to_delete);
        }
      }
    }
    $elements = parent::viewElements($items, $langcode);
    return $elements;
  }
  
  /**
   * Recherche l'index en function de l'id du fichier.
   */
  protected function findIndexByFid(FieldItemListInterface $items, $fid) {
    $values = $items->getValue();
    foreach ($values as $delta => $value) {
      if ($value['target_id'] == $fid)
        return $delta;
    }
    return NULL;
  }
}
