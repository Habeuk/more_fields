<?php

namespace Drupal\more_fields\Plugin\views\filter;

/**
 * Interface pour les filtres qui permettent de compter les nombres d'entités.
 *
 * @author stephane
 *        
 */
interface FilterCountInterface {
  
  /**
   * Contruit les requetes de la vue à partir du filtre.
   * NB: cette fonction n'impacte pas les resultats de recherche mais modifie
   * simplement les termes afficher à l'utilisateur.
   *
   * @return array
   */
  public function FilterCountEntitiesHasterm(): array;
  
}