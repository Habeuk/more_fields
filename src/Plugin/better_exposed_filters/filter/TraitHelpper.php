<?php

namespace Drupal\more_fields\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\Links;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

trait TraitHelpper {

  /**
   *
   * @var \Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices
   */
  protected $LayoutgenentitystylesServices;

  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'layoutgenentitystyles_view' => 'more_fields/' . $this->classByModel
    ];
  }

  /**
   *
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$this->classByModel) {
      \Drupal::messenger()->addError("Error de configuration : " . $this->getPluginId());
    }
    elseif (!empty($this->configuration['layoutgenentitystyles_view'])) {
      $this->LayoutgenentitystylesServices->addStyleFromModule($this->configuration['layoutgenentitystyles_view'], "more_fields_exposed_filter", $this->classByModel, "better_exposed_filters/filter");
    }
  }

}