<?php

namespace Drupal\lando_pantheon_search\Solarium;

use Solarium\Core\Client\Endpoint as SolariumEndpoint;
use Solarium\Exception\UnexpectedValueException;

/**
 * Endpoint that tolerates empty context and empty path.
 *
 * Solarium's default Endpoint unconditionally inserts "{context}/" between the
 * server URI and the core, producing a "//" when context is an empty string
 * (which the Pantheon connector forces on any Pantheon environment). Pantheon
 * URLs don't include a /solr/ segment, so this subclass omits that slash when
 * context is empty. Same for getV1BaseUri().
 */
class LandoPantheonEndpoint extends SolariumEndpoint {

  /**
   * {@inheritdoc}
   */
  public function getServerUri(): string {
    $path = (string) $this->getPath();
    $path = $path === '' ? '' : '/' . trim($path, '/');
    return $this->getScheme() . '://' . $this->getHost() . ':' . $this->getPort() . $path . '/';
  }

  /**
   * {@inheritdoc}
   */
  public function getV1BaseUri(): string {
    $context = (string) $this->getContext();
    return $this->getServerUri() . ($context === '' ? '' : $context . '/');
  }

  /**
   * {@inheritdoc}
   */
  public function getCoreBaseUri(): string {
    $core = $this->getCore();
    if (!$core) {
      throw new UnexpectedValueException('No core set.');
    }
    $context = (string) $this->getContext();
    return $this->getServerUri() . ($context === '' ? '' : $context . '/') . $core . '/';
  }

}
