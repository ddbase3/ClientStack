# ClientStack Privacy and Data Processing

> This document describes privacy-relevant behavior of the ClientStack component itself. It does not replace a legal privacy notice and does not document the internal behavior of the individual client libraries stored under `assets/`.

## Scope

ClientStack is primarily an asset-management and browser-UI integration component. It provides:

- a logical asset registry,
- local browser asset bundles,
- asset discovery through plugin `local/assets.json` files,
- reusable display implementations,
- administration and diagnostic displays,
- development/build tooling for maintaining local asset copies.

The individual libraries and browser clients distributed below `assets/` remain separate software components. Their own privacy-relevant behavior must be evaluated from their respective source code and documentation when they are actually used.

## Core asset registry

The core `DefaultAssetService` keeps its logical asset registry in PHP memory for the running process.

It registers a small set of built-in logical assets and scans:

```text
DIR_PLUGIN/*/local/assets.json
```

for additional definitions.

The registry contains asset names, file paths, file types, default flags and optional version information. ClientStack does not require personal data for this registry and does not create a dedicated persistent store for it.

Plugin `assets.json` files should contain asset metadata only. Secrets or personal data do not belong in these files.

## Locally deployed browser assets

The normal deployed runtime uses files stored below:

```text
assets/
```

ClientStack itself does not need to contact the original download locations in order to serve these checked-in files.

Whether an individual browser library later performs network requests, uses browser storage, accesses browser capabilities or processes personal data depends on that library and on how the consuming application configures it. Those behaviors are outside the ClientStack component boundary documented here.

## Development and build-time network access

ClientStack contains explicit maintenance scripts that can contact external source hosts:

- `clone-all.sh` clones or updates configured Git repositories.
- `build-assets.php` can copy from temporary repository checkouts or download configured release files.
- dedicated deployment scripts copy selected client source trees from `dev/` into `assets/`.

These actions occur only when a developer or build process runs the scripts. They are not part of normal application request handling.

Such build-time requests disclose ordinary connection metadata of the build environment to the contacted source host, such as the source IP address and protocol metadata. ClientStack does not intentionally send application-user data as part of these acquisition requests.

## The `dev/` directory

`dev/` is a temporary working area for source repositories used during development and asset deployment.

The ClientStack repository tracks only:

```text
dev/.gitignore
```

Repository checkouts below `dev/` are ignored and are not intended to be committed or distributed as part of ClientStack.

Developers remain responsible for local workstation data, Git credentials, proxy configuration and any other information created by their development tools inside or around temporary checkouts.

## Build provenance metadata

`assets/versions.json` can contain build metadata such as:

- acquisition type,
- Git tag,
- Git branch,
- Git commit hash,
- source commit timestamp,
- local build timestamp.

This data describes software provenance. It is not intended to contain application-user data.

## Reusable UI displays

ClientStack contains reusable display classes. A display can receive content or structured data from the calling application and render it in the browser.

ClientStack does not define a single shared persistence model for display content. Any persistence, transmission or retention of the rendered data is determined by the service or application feature supplying that data and by the selected browser client.

Rich-text, chatbot, diagram and composition displays should therefore be assessed together with the feature that supplies their actual content.

## Administration and diagnostic displays

Several ClientStack displays expose operational information from the active BASE3 runtime. Depending on installation and data contents, this information can include personal, confidential or security-relevant data.

### Configuration administration

`ConfigurationAdminDisplay` can read, create, update, rename, reload and delete values through the active `IConfiguration` implementation.

Configuration can contain credentials, endpoints, operational settings or other sensitive values if an installation stores such information there. Access to this display must therefore be limited appropriately by the surrounding application.

ClientStack does not define the storage location or retention policy of the active configuration backend.

### Job administration

`JobsAdminDisplay` reads discovered jobs and can change job activation and priority values through the active configuration service.

The data is operational configuration. ClientStack does not define separate job-history storage in this display.

### Log inspection

`LogAdminDisplay` reads available logger scopes and log entries through `ILogger`.

Logs may contain personal data, request details, technical identifiers, exception messages or other sensitive information depending on what the surrounding application writes to the logger. ClientStack does not define the logger's collection or retention policy.

### State-store administration

`StateStoreAdminDisplay` reads and can modify or delete entries from the database-backed BASE3 state-store table used by the current implementation.

State values are generic and can therefore contain identifiers, cursors, locks, status values or other application-specific data. Their sensitivity depends on the service that created them.

The display is an administration surface, not the owner of the state data or its retention rules.

### User and permission diagnostics

`UsermanagerDebugDisplay` can display information returned by the active `IUsermanager`, including the current user, groups, roles, permissions and target-specific permission checks.

Those values can be personal data or authorization metadata. The display is intended for diagnostics and must be exposed only where such diagnostics are appropriate.

### Service diagnostics

`ServicesAdminDisplay` lists selected BASE3 service interfaces and the concrete implementation class currently bound to each interface.

This is technical runtime metadata. Although it is not normally personal data, it can reveal implementation details useful for system reconnaissance and should be treated as administrative information.

### Agent-flow inspection

`AgentFlowAdminDisplay` scans plugin `local/` directories for JSON files whose filename contains `flow` and can render their metadata, nodes, resources and connections.

ClientStack does not control what a flow file contains. If flow definitions contain personal data, credentials, prompts, endpoint details or other confidential values, the viewer can expose them to users who can access the display.

Flow definitions should therefore avoid embedding secrets and should be protected according to their contents.

## Access-control boundary

The administration and diagnostic display classes described above do not define an independent ClientStack-specific authentication or authorization system.

The surrounding application is responsible for deciding whether those displays are discoverable, routable and accessible to a given user. This access boundary is especially important for configuration, logs, state, user information and flow definitions.

## Data mutation

Although ClientStack is primarily a client-asset component, some administration displays can modify data in the services they administer.

Current examples include:

- configuration values,
- job activation and priority configuration,
- state-store entries.

Those changes are persisted by the respective underlying backend. ClientStack does not create a parallel copy or fallback persistence layer for them.

## Browser storage and browser permissions

ClientStack itself does not define one global browser-storage policy for all bundled client libraries.

Some individual browser libraries or clients may use facilities such as `localStorage`, browser media APIs, network requests or other browser capabilities. Those behaviors belong to the respective library or client implementation and should be documented and reviewed there.

ClientStack's responsibility is to make the selected local bundles available and to integrate them into displays.

## External client libraries

The current asset inventory includes third-party and BASE3-specific client bundles such as Chart.js, CKEditor, Dropzone, FullCalendar, jQuery, jQuery UI, Leaflet, Marked, MathJax, Mermaid and several BASE3 browser projects.

This privacy document intentionally does not restate their individual processing behavior. The fact that a library is present in `assets/` does not mean that it is loaded, configured or used in every deployment.

For any library actually used in a product, review that library's own privacy-relevant behavior and the concrete application configuration.

## Logging by ClientStack

The analyzed ClientStack core does not establish a separate ClientStack log store. The log administration display reads the active BASE3 logger.

If future ClientStack code starts writing operational or browser telemetry, that processing should be documented here together with purpose, data fields, storage location and retention.

## Cookies and sessions

ClientStack does not define its own server-side session or cookie store in the analyzed component.

A bundled browser client or the surrounding application may use cookies or session state. Such processing belongs to that client or host feature and is not automatically created by the ClientStack asset registry.

## Retention and deletion

ClientStack has no single retention policy because it does not own the underlying configuration, logger, state store or flow-definition storage.

Retention and deletion must be defined where those data stores are implemented. In particular, installations should define appropriate policies for:

- logs exposed through `LogAdminDisplay`,
- values stored in configuration,
- entries in the state store,
- local flow definitions,
- any browser-side storage used by selected client libraries.

## Security considerations

For a production deployment, at minimum verify that:

- administration and diagnostic displays are restricted to appropriate users,
- configuration secrets are not unnecessarily exposed through generic administration views,
- log content is minimized and retained only as long as required,
- state-store values do not contain avoidable personal data or secrets,
- flow JSON files do not embed credentials unnecessarily,
- only required client bundles are exposed by the application,
- library versions and license obligations are maintained separately,
- temporary repositories below `dev/` are not packaged as committed ClientStack source.

## Library-specific privacy reviews

ClientStack's local delivery model reduces the need to fetch the JavaScript and CSS bundles themselves from public CDNs at runtime. It does not, by itself, guarantee that every bundled library operates without external requests.

A privacy review for a concrete application must therefore consider both layers separately:

1. ClientStack as the local asset and display integration component.
2. Each selected client library or browser client as configured by the consuming feature.

## Related documentation

See [docs/faq.md](docs/faq.md) for operational and architectural questions about ClientStack.
