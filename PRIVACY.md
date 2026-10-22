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

## TabControlDisplay first-tab AJAX rendering

`TabControlDisplay` now supports `initial_load_via_ajax` as a caller-controlled rendering choice. When enabled, the browser requests the initially active display with the same same-origin `fetch()` mechanism (`credentials: "same-origin"`, `cache: "no-store"`) already used for subsequent tab navigation. This does not add a data store, new recipient, separate cookie, upload endpoint, or ClientStack-owned persistence. The target display URL and its request parameters are supplied by the embedding application; the host must apply the same authorization to initial and later display requests. The browser request and its metadata may appear in host or infrastructure access logs subject to host retention policies. ClientStack does not define those retention periods.


## Global chatbot launcher (October 2026)

`GlobalChatbotLauncher` is a platform-neutral UI shell. On initialization it
reads a BASE3-owned window-configuration dataset (`global-chatbot-launcher/default`)
through the host integration. The dataset stores boolean layout options; the
backend-specific `ISettingsStore` determines its physical storage and retention.
Administrators can change the dataset through `GlobalChatbotLauncherConfigDisplay`,
which checks BASE3 `IAuthorizationService::decide()` for `system/admin`.

The browser shell uses origin-scoped `localStorage` for the last selected mode and
user-resized dimensions, and `sessionStorage` for whether the overlay/sidebar
should reopen in the same tab after navigation. Keys include the configured
chatbot group/name (not the user's name). Storage is removed or replaced through
ordinary browser controls or subsequent interactions; no automatic age-based
expiry is implemented. The browser may retain local-storage values until cleared.

The shell requests server-rendered chatbot markup at a host-provided same-origin
URL. The chatbot feature and configured AI providers control the ensuing data
processing. The launcher itself does not read/store conversation contents or
contact third-party endpoints. Actual retention, recipients, transfers and
hosting for the chatbot backend remain deployment-specific and must be verified
separately.

The GlobalChatbotLauncher source is an explicitly versioned exception to the
otherwise temporary `dev/` clones; its checked-in `src/` files are mirrored
unchanged to `assets/globalchatbotlauncher/`. No third-party CDN or external
asset loader is required by this UI shell.


### Global launcher header formatting (October 2026)

The launcher displays administrator-supplied title emphasis in the existing
header element. It renders only simple formatting tags and discards all
attributes and executable elements. This change does not introduce new
collection, storage, retention, external recipients or transmissions.

### Docked launcher stacking and host headers (October 2026)

The docked launcher has display priority over a host's fixed administration
header. Where configured, it adjusts only that header's inline layout while
the sidebar is open and restores its original values on close. This is a
visual DOM/CSS change: it does not add browser storage, collect personal data,
change network requests, or change retention or third-party recipients.


### ModularChatbot Markdown script resolution (October 2026)

The existing locally served `marked.js` bundle can now be resolved as an AMD
module when the host page uses RequireJS. The same local browser resource and
message-rendering flow are retained. The change does not introduce external
asset hosts, a new data store, new network recipients or an additional data
retention mechanism. Chat message content remains processed by the existing
modular client and the host-configured chatbot services; those services and
their retention policies are unchanged by this correction.

## Authorization update (2026-10-19)

The launcher configuration and usermanager diagnostics now use the project-bound
`IAuthorizationService` and its selected host authority for system administration
checks instead of `IUsermanager::can()`. No separate user-role or permission table is
created. The host authorization backend owns its logs, access state, cache expiry and
retention. The debug display may expose native roles and rights to authorized operators;
restrict its route accordingly.

## Authorization administration UI refactor

The shared ClientStack screens do not create another persistence layer. ILIAS group identifiers, user and role criterion IDs, and feature audience expressions continue to be processed and stored solely by the configured ILIAS host services. Access is checked server-side; POST mutations require a session CSRF token. The new UiFoundation interface provides no storage. Retention and erasure continue to follow the host settings backend as previously documented.

## ModularSelect deployment (2026-10-21)

The new `assets/modularselect/` is a locally served copy of
`dev/ModularSelect/src/`, reviewed at initial source creation on 2026-10-21.
It is a domain-neutral browser select: labels, optional descriptions, literal
search text and selected IDs are kept in its DOM and JavaScript instance
memory. It uses no dedicated cookies, persistent browser storage, telemetry,
remote scripts or hardcoded network requests. When the instance is destroyed,
its DOM and event listeners are removed and active searches are invalidated
and aborted, subject to caller-held data and backend processing outside the
module's control.

An application can supply an asynchronous source function that receives search
text and pagination parameters. For a user directory this can expose personal
information, so the consuming server endpoint must authenticate and authorize
each request, restrict the result projection and enforce pagination. The
source function, its recipients, server-side logs, caching, backups, retention,
rights handling, transfer location and controller/processor relationships are
not defined by ClientStack and require deployment-specific review. If the
containing form is submitted, ModularSelect's optional hidden inputs provide
selected string IDs; the actual submission is controlled by the host page.
In single-select the widget exposes at most one selected ID. In multi-select
it can expose multiple selected IDs in instance memory and, when configured,
repeated hidden form inputs. The repaired mouse/touch focus handling changes only event order. Keyboard
Backspace removal updates the same in-memory selection and optional hidden
form fields as the chip remove button. Its visually hidden live announcement
uses the already displayed option label. Neither behavior introduces
additional storage, directory lookups, network recipients or background activity.

Selected labels are visually shortened with CSS ellipsis when necessary,
but the original labels remain in the DOM and hover tooltip, including any
personal data in that text. Multi-select chips are horizontally scrollable;
this UI change creates no additional data processing or external recipient.

The module renders option labels/descriptions through `textContent`, not as
HTML. This does not replace server-side validation or access control. The
separate root inventory `dev/ModularSelect/PRIVACY.md` documents the module's
technical boundary, including source-specific risks and known gaps. The
presence of these assets does not activate any user-directory search or expose
an AJAX route until a consuming display is explicitly integrated.

## Group/feature user search integration (2026-10-21)

The shared BASE3 Groups and Features administration UI can now query the
selected host's `IUsermanager::searchUsers()` through the read-only
`AuthorizationAudienceUserSearchDisplay`. This is only available to the
current session actor when `IAuthorizationService` explicitly allows
`system/admin` **and** the host audience-administration service approves the
actor, such as the project-specific BASE3 development membership required in
ILIAS. The endpoint also requires a valid CSRF token from the corresponding
admin form. Unauthorized callers cannot read user directory entries.

**Purpose and fields:** After the administrator types at least one character,
ClientStack sends the literal search text, page limit and offset, form scope
and CSRF token in a same-origin POST request. The host backend performs the
search and supplies native user IDs, full/display names and login names.
ClientStack projects only numeric user IDs and a label composed of name, login
and ID into the JSON response; email addresses, role memberships and other
User attributes are not returned. For an already saved ID, an optional
single `getUserById()` request supplies its display label. Both searches are
restricted to the approved administrator. The native host can see the search
text and resolve it using its existing user directory.

**Storage, retention, access, erasure:** ClientStack creates no user cache,
database record, analytics event, cookie or browser storage for searches.
Transient search terms, results, selected IDs and labels live in the active
ModularSelect instance/DOM; outstanding requests are aborted when its UI is
removed. Already assigned numeric IDs continue to be stored by the host's
existing group/feature settings mechanism; removing an assignment uses that
host's existing save operation. ClientStack has no independent retention or
erasure mechanism. Host account deletion and settings deletion remain with
the selected identity/settings backends. HTTP responses use `no-store`; the
request URL contains no search term. Web-server/proxy/security logging of
POST bodies or response payloads, underlying native directory retention and
backup retention cannot be verified from ClientStack sources; deployment
owners must document and configure those separately.

**Recipients/transfers/security:** Requests remain same-origin to the BASE3
host backend; no third-party browser service or external select API is
introduced. Host and infrastructure operators may have access to the request
and relevant logs, depending on their native operational controls.
Authorization is checked at the actual JSON endpoint, not inferred from
visible navigation; CSRF validation, input limits, output encoding and a
minimal projection are applied. Transport encryption, operational logs and
native backend controls remain host responsibilities.

**Accessibility:** ModularSelect offers keyboard navigation, accessible
combobox/listbox roles, focus and status messages, and a numeric-input
fallback if the module fails to initialize. The shared editor destroys widget
instances on AJAX replacement, row deletion and tab unload. Actual manual
screen-reader, zoom and host CSS tests are still needed; automated unit and
browser checks alone do not establish WCAG 2.2 AA conformance.


### Diagnostic administration

The new `DiagnosticScopeDisplay` provides an administrator-only read-only diagnostics page. The backend checks `IAuthorizationService` for an explicit `system/admin` grant before listing or running checks. The compact Governance Diagnostics UI only starts an area upon user action (or through an explicitly scoped legacy deep link), expands the selected area in place, and runs each requested check through a separate CSRF-protected POST; the CSRF token is held in the existing authenticated session under `diagnostics.checks.csrf`. The response contains status, translated text, recommended action and measurements supplied by the check. ClientStack does not persist check results, does not introduce tracking or third-party requests and keeps the current results only in the live page DOM. Server-side access logging, session persistence and retention depend on the active host infrastructure. Plugin diagnostics must avoid returning sensitive content.

### Diagnostic report clipboard export

The diagnostics UI exposes a user-initiated JSON copy operation for the most recent **completed** run of the **selected diagnostic area**, not a system-wide all-areas report. The JSON includes scope name/title/description, completion time, all listed checks with their translated status/message/remediation and the aggregate values rendered in the UI. Search and status filters do not remove records from this export. It excludes the session CSRF token, transport endpoint and browser identity, and the UI does not store or transmit the report to an external destination. Results remain in the current page DOM; switching areas or rerunning checks disables copying until the selected run finishes. The clipboard is managed by the browser/OS, outside ClientStack's retention control. Users must separately assess any subsequent disclosure, especially when pasting into an external AI provider. Each check remains responsible for excluding sensitive values from its result.

### Diagnostic check feature permission (2026-10-22)

The existing `DiagnosticScopeDisplay` now requires both native `system/admin` authorization and the independent `feature/diagnosticreportsfeature` decision before returning either its HTML view or an AJAX check result. It uses the existing BASE3 `IAuthorizationService`, session token and in-memory result processing. The feature decision does not add persistent data collection, a user-rights replica or a separate authorization backend.
