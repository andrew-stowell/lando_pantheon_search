# Lando Pantheon Search

Makes the [`search_api_pantheon`](https://www.drupal.org/project/search_api_pantheon) connector work inside Lando. Safe to leave enabled in every environment — it's a no-op wherever the upstream module already connects correctly.

## Why this exists

Between `search_api_pantheon` 8.3.x and 8.4.x the module was rewritten. The 8.4.x version extends `StandardSolrConnector` directly and relies on Solarium's stock URL building, which has two mismatches with what Lando's Pantheon recipe exposes:

1. **Hardcoded HTTPS.** `PantheonSolrConnector::getEnvironmentVariables()` hardcodes `'scheme' => 'https'` instead of reading `PANTHEON_INDEX_SCHEME`. Lando serves Solr over HTTP on port 8983.
2. **Empty-context double slash.** On any Pantheon environment the connector forces `context = ''` so URLs omit the `/solr/` segment. Solarium's `Endpoint::getV1BaseUri()` and `getCoreBaseUri()` unconditionally insert `"{context}/"` between the server URI and the core, producing a leading `//` when context is empty. Result: requests go to `http://index:8983//lando/admin/system` and the Jetty server returns 404.

Both bugs hit Lando because Lando's `PANTHEON_INDEX_PATH` is `/` (unlike production's `v1`), and `PANTHEON_ENVIRONMENT` is set (so the "on Pantheon" override branch runs).

## How it works

- `hook_search_api_solr_connector_info_alter()` swaps the class on the existing `pantheon` connector ID. No server config changes required.
- `LandoPantheonSolrConnector::getEnvironmentVariables()` reads `PANTHEON_INDEX_SCHEME` and normalizes `PANTHEON_INDEX_PATH` (so `/` becomes `''` and `v1` becomes `/v1`).
- `LandoPantheonSolrConnector::connect()` replaces every Solarium `Endpoint` on the client with `LandoPantheonEndpoint`, which skips the `{context}/` insertion when context is empty. The swap happens in `connect()` (not `createClient()`) because `SolrConnectorPluginBase::connect()` attaches the default endpoint *after* `createClient()` returns.

## Files

```
lando_pantheon_search.info.yml
lando_pantheon_search.module                                  alter hook
src/Plugin/SolrConnector/LandoPantheonSolrConnector.php       env var + endpoint fixes
src/Solarium/LandoPantheonEndpoint.php                        Solarium Endpoint subclass
```

## When to remove this module

Delete it once `search_api_pantheon` upstream either:

- reads `PANTHEON_INDEX_SCHEME` instead of hardcoding `'https'`, **and**
- stops producing malformed URLs when `context = ''` (most likely by shipping its own Endpoint subclass, as the 8.3.x version did).

## Verification

Go to server view page for the Pantheon server on your local lando site (default path is /admin/config/search/search-api/server/pantheon_search).

Verify the Server can be reached.
