<?php

namespace Drupal\more_fields\Plugin\views\query;

use Drupal\search_api\Plugin\views\query\SearchApiQuery;

/**
 * Vise principalement à ajouter des fonctions permettant de compter et de
 * grouper les resultats.
 *
 * @author stephane
 * @ViewsQuery(
 *   id = "custom_search_api_query",
 *   title = @Translation("Custom Search API Query"),
 *   help = @Translation("The query will be generated and run using the Search API.")
 * )
 */
class CustomSearchApiQuery extends SearchApiQuery {
  
}