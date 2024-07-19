<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_reference_filter",
 *   label = @Translation("Entity render with filter by type"),
 *   description = "Permet de filtrer le rendu d'une entite à partir des types de ses entitées",
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityFilterReferenceFormatter extends EntityReferenceEntityFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'type_entities' => []
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['type_entities'] = [
      '#type' => 'select2',
      '#options' => $this->getEntitiesTypeOptions(),
      '#title' => $this->t('Select entitie type'),
      '#default_value' => $this->getDefaultValue(),
      '#required' => TRUE,
      '#autocomplete' => TRUE,
      '#target_type' => $this->getEntityTypeId(),
      '#selection_handler' => 'default',
      '#multiple' => 10
    ];
    $elements + parent::settingsForm($form, $form_state);
    return $elements;
  }
  
  /**
   *
   * @return string|NULL
   */
  protected function getEntityTypeId() {
    $entityStorage = $this->entityTypeManager->getStorage($this->getFieldSetting('target_type'));
    return $entityStorage->getEntityType()->getBundleEntityType();
  }
  
  /**
   * Recupere les valeurs par defaut.
   *
   * @return []
   */
  protected function getDefaultValue() {
    $ids = [];
    $entityStorage = $this->entityTypeManager->getStorage($this->getFieldSetting('target_type'));
    if ($entityStorage) {
      $storage = $this->entityTypeManager->getStorage($entityStorage->getEntityType()->getBundleEntityType());
      $default_value = $this->getSetting('type_entities');
      // \Stephane888\Debug\debugLog::kintDebugDrupal($default_value,
      // 'getDefaultValue', true);
      if ($storage && !empty($default_value)) {
        
        foreach ($default_value as $value) {
          if (isset($value['target_id']))
            $ids[] = $value['target_id'];
          else {
            $ids = $default_value;
            break;
          }
        }
      }
    }
    return $ids;
  }
  
  /**
   * Recupere la liste des options.
   *
   * @return []
   */
  protected function getEntitiesTypeOptions() {
    $entities = [];
    $entityStorage = $this->entityTypeManager->getStorage($this->getFieldSetting('target_type'));
    if ($entityStorage) {
      $storage = $this->entityTypeManager->getStorage($entityStorage->getEntityType()->getBundleEntityType());
      $default_value = $this->getSetting('type_entities');
      // \Stephane888\Debug\debugLog::kintDebugDrupal($default_value,
      // 'getEntitiesType', true);
      if ($storage && !empty($default_value)) {
        $ids = [];
        foreach ($default_value as $value) {
          if (isset($value['target_id']))
            $ids[] = $value['target_id'];
          else {
            $ids = $default_value;
            break;
          }
        }
        $contents = $storage->loadMultiple($ids);
        foreach ($contents as $content) {
          $entities[$content->id()] = $content->label();
        }
      }
    }
    return $entities;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $validsEntities = $this->getDefaultValue();
    $view_mode = $this->getSetting('view_mode');
    $elements = [];
    
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      /**
       *
       * @var \Drupal\paragraphs\Entity\Paragraph $entity
       */
      if (!in_array($entity->bundle(), $validsEntities))
        continue;
      // Due to render caching and delayed calls, the viewElements() method
      // will be called later in the rendering process through a '#pre_render'
      // callback, so we need to generate a counter that takes into account
      // all the relevant information about this field and the referenced
      // entity that is being rendered.
      $recursive_render_id = $items->getFieldDefinition()->getTargetEntityTypeId() . $items->getFieldDefinition()->getTargetBundle() . $items->getName() . 
      // We include the referencing entity, so we can render default images
      // without hitting recursive protections.
      $items->getEntity()->id() . $entity->getEntityTypeId() . $entity->id();
      
      if (isset(static::$recursiveRenderDepth[$recursive_render_id])) {
        static::$recursiveRenderDepth[$recursive_render_id]++;
      }
      else {
        static::$recursiveRenderDepth[$recursive_render_id] = 1;
      }
      
      // Protect ourselves from recursive rendering.
      if (static::$recursiveRenderDepth[$recursive_render_id] > static::RECURSIVE_RENDER_LIMIT) {
        $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering entity %entity_type: %entity_id, using the %field_name field on the %parent_entity_type:%parent_bundle %parent_entity_id entity. Aborting rendering.', [
          '%entity_type' => $entity->getEntityTypeId(),
          '%entity_id' => $entity->id(),
          '%field_name' => $items->getName(),
          '%parent_entity_type' => $items->getFieldDefinition()->getTargetEntityTypeId(),
          '%parent_bundle' => $items->getFieldDefinition()->getTargetBundle(),
          '%parent_entity_id' => $items->getEntity()->id()
        ]);
        return $elements;
      }
      
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $elements[$delta] = $view_builder->view($entity, $view_mode, $entity->language()->getId());
      
      // Add a resource attribute to set the mapping property's value to the
      // entity's url. Since we don't know what the markup of the entity will
      // be, we shouldn't rely on it for structured data such as RDFa.
      if (!empty($items[$delta]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
        $items[$delta]->_attributes += [
          'resource' => $entity->toUrl()->toString()
        ];
      }
    }
    
    return $elements;
  }
  
}