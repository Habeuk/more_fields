<?php

use Drupal\Core\Template\Attribute;

function template_preprocess_more_fields_experience_formatter(&$vars) {
  $vars['item'] = [];
  if (!empty($vars['element']['#item'])) {
    $vars['item'] = $vars['element']['#item'];
  }
}

function template_preprocess_more_fields_value_niveau_formatter(&$vars) {
  // dump($vars);
  if (!empty($vars['element']['#item'])) {
    $vars['item'] = $vars['element']['#item'];
  }
}

function template_preprocess_more_fields_experience_formatter3(&$vars) {
  $vars['item'] = [];
  if (!empty($vars['element']['#item'])) {
    $vars['item'] = $vars['element']['#item'];
  }
}

function template_preprocess_more_fields_icon_text(&$vars) {
  $vars['item'] = [];
  if (!empty($vars['element']['#item'])) {
    $vars['item'] = $vars['element']['#item'];
  }
}

function template_preprocess_more_fields_text_bg(&$vars) {
  $vars['item'] = [];
  if (!empty($vars['element']['#item'])) {
    $vars['item'] = $vars['element']['#item'];
  }
}

function template_preprocess_more_fields_accordion_field_formatter(&$vars) {
  if (!empty($vars['element']['#items'])) {
    $vars['items'] = $vars['element']['#items'];
    $vars['attribute'] = $vars['element']['#attribute'];
    $vars['attribute_box'] = $vars['element']['#attribute_box'];
  }
}

function template_preprocess_more_fields_bef_checkboxes(&$vars) {
  //
}

function template_preprocess_more_fields_bef_radios(&$vars) {
  /**
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  $moduleHandler = \Drupal::moduleHandler();
  $moduleHandler->loadInclude('better_exposed_filters', "inc", 'includes/better_exposed_filters.theme');
  template_preprocess_bef_radios($vars);
}

function template_preprocess_more_fields_field_chart(&$vars) {
  //
}
function template_preprocess_restrained_text_formatter(&$vars) {
  $vars['#attached']['library'][] = 'more_fields/restrained_field';
  if (!empty($vars["element"]["#item"])) {
    $vars["item"] = $vars["element"]["#item"];
  }
}
function template_preprocess_more_field_file_image_video(&$vars) {
  $vars['#attached']['library'][] = 'more_fields/more_field_file_image_video';
  if (!empty($vars["element"]["#item"])) {
    $vars["item"] = $vars["element"]["#item"];
  }
}

function template_preprocess_gallery_overlay(&$vars) {
  $image_attributes = [];
  $stop = 0;
  foreach ($vars['image_attributes'] as $key => $value) {
    if (gettype($value) == 'array') {
      $image_attributes[$key] = new Attribute($value);
    } else {
      $image_attributes[$key] = new Attribute(['class' => explode(' ', $value)]);
    }
  }
  $vars['image_attributes'] = $image_attributes;
  $vars['#attached']['library'][] = 'more_fields/more_field_gallery_overlay';
}
