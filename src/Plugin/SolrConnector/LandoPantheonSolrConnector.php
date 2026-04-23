<?php

namespace Drupal\lando_pantheon_search\Plugin\SolrConnector;

use Drupal\lando_pantheon_search\Solarium\LandoPantheonEndpoint;
use Drupal\search_api_pantheon\Plugin\SolrConnector\PantheonSolrConnector;

/**
 * Pantheon Solr connector that also works under Lando.
 *
 * Registered in place of the upstream "pantheon" connector via
 * hook_search_api_solr_connector_info_alter().
 */
class LandoPantheonSolrConnector extends PantheonSolrConnector {

  /**
   * {@inheritdoc}
   *
   * Upstream hardcodes scheme=https and prefixes "/" onto PANTHEON_INDEX_PATH
   * without normalizing. Lando exposes http on 8983 with PANTHEON_INDEX_PATH="/",
   * which upstream turns into the malformed path "//".
   */
  public static function getEnvironmentVariables(): array {
    if (!getenv('PANTHEON_ENVIRONMENT')) {
      return [];
    }
    return [
      'scheme' => getenv('PANTHEON_INDEX_SCHEME') ?: 'https',
      'host' => getenv('PANTHEON_INDEX_HOST'),
      'port' => getenv('PANTHEON_INDEX_PORT'),
      'path' => rtrim('/' . ltrim(getenv('PANTHEON_INDEX_PATH'), '/'), '/'),
      'core' => trim(getenv('PANTHEON_INDEX_CORE'), '/'),
      'solr_version' => 8,
      'search_api_pantheon_schema_endpoint' => trim(getenv('PANTHEON_INDEX_SCHEMA'), '/'),
      'search_api_pantheon_reload_endpoint' => trim(getenv('PANTHEON_INDEX_RELOAD_PATH'), '/'),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Swap every Solarium Endpoint attached to the client with our
   * LandoPantheonEndpoint subclass so URLs are built correctly when the
   * context is empty (the state the Pantheon connector forces on any Pantheon
   * environment). We do this here rather than in createClient() because
   * SolrConnectorPluginBase::connect() calls createClient() first (returning
   * an endpoint-less client) and only then attaches a standard Solarium
   * Endpoint via createEndpoint() — so the swap has to happen after.
   */
  protected function connect() {
    parent::connect();

    foreach ($this->solr->getEndpoints() as $endpoint) {
      if ($endpoint instanceof LandoPantheonEndpoint) {
        continue;
      }
      $replacement = new LandoPantheonEndpoint($endpoint->getOptions());
      $this->solr->removeEndpoint($endpoint);
      $this->solr->addEndpoint($replacement);
    }
  }

}
