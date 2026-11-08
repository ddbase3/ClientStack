# ClientStack

**ClientStack** is a centralized asset management plugin for PHP frameworks. It provides structured access to frequently used frontend libraries such as AssetLoader, jQuery, Chart.js, jQuery UI, and JqueryDataTables. Designed for plugin-based architectures, ClientStack enables both static (built-in) and dynamic (JSON-based) asset registration, ensuring clean separation of concerns and flexible integration.

---

## 🚀 Features

* ✅ Built-in registration of core frontend libraries
* 📦 Automatic discovery of `assets.json` in other plugins
* 🧩 Clean separation of logical assets and physical asset files (JS/CSS)
* 🔁 Dependency Injection (DI) support and full service override
* 🌐 Centralized URL resolution using an internal `AssetResolver`
* 🔍 Query support: get by name, list all, list default assets, etc.

---

## 📂 Structure

An asset is represented as a **Logical Asset**, which may contain multiple **Asset Files** (e.g., `.js` and `.css`):

```php
$jqueryUI = new LogicalAsset(
    'jqueryui',
    [
        new AssetFile('libs/jquery-ui/jquery-ui.min.js', 'js'),
        new AssetFile('libs/jquery-ui/jquery-ui.min.css', 'css')
    ]
);
```

---

## 🧰 Usage

### Registering Assets

#### 1. Built-in (hardcoded)

`ClientStack` comes with pre-registered core libraries. You can extend these directly in the plugin code.

#### 2. Plugin-level `assets.json`

Each plugin can define an `assets.json` file in its root directory:

```
/Example/
├── src
|   ├── ExamplePlugin.php
├── local
|   ├── assets.json
```

### Example `assets.json`

```json
{
  "mywidget": {
    "files": [
      { "path": "js/mywidget.js", "type": "js" },
      { "path": "css/mywidget.css", "type": "css" }
    ],
    "default": false
  }
}
```

---

## 🛠️ Integration

### Get Asset by Name

```php
$asset = $assetService->getAsset('jqueryui');

foreach ($asset->files as $file) {
    $url = $resolver->resolveUrl($file->path);
    if ($file->type === 'js') {
        echo "<script src='$url'></script>";
    } elseif ($file->type === 'css') {
        echo "<link rel='stylesheet' href='$url'>";
    }
}
```

### Get All Asset Names

```php
$keys = $assetService->getAssetKeys();  // ['jquery', 'chartjs', 'mywidget', ...]
```

---

## 🧩 Customization

* **Override the AssetService**: Replace the service in your DI container.
* **Use custom JSON loaders** or asset filtering logic.
* **Support remote/CDN assets** by customizing the AssetResolver.

---

## 📄 Interface Overview

### `IAssetService`

```php
getAsset(string $name): ?LogicalAsset
getAssetKeys(): array
getDefaultAssets(): array
getAllAssets(): array
registerAsset(LogicalAsset $asset): void
hasAsset(string $name): bool
```

---

## 📃 License

GPL v3.0 License

## Classic Chatbot client

ClientStack owns the preserved classic BASE3 Chatbot browser client.

The development source is maintained as a standalone Git repository under:

```text
dev/ClassicChatbot/
```

The deployed browser files live under:

```text
assets/classicchatbot/
```

Deploy the current repository source with:

```bash
./deploy-classic-chatbot.sh
```

`ClientStack\Display\ClassicChatbotDisplay` implements `UiFoundation\Api\IChatbotDisplay`. `ClientStackPlugin` registers it as the replaceable default using `IContainer::NOOVERWRITE`.

## Modular Chatbot client

The new native ES module client is developed as a separate Git repository under:

```text
dev/ModularChatbot/
```

It is deployed unchanged to:

```text
assets/modularchatbot/
```

Deploy it with:

```bash
./deploy-modular-chatbot.sh
```

`ClientStack\Display\ModularChatbotDisplay` is discoverable as `modularchatbotdisplay`. The active `IChatbotDisplay` binding remains the preserved classic client until a project explicitly selects the modular display.

The modular display supports Chatbot's server-backed Conversation API. `ConversationPlugin` registers the chat-list and new-chat controls before loading the last active chat, hydrates stored messages through the normal renderer, and provides accessible controls for creating, activating, renaming and deleting chats. Conversation ids and history are not generated or persisted in the browser. ClassicChatbot remains the non-multi-chat fallback.

### DOM class customization

`ModularChatbotDisplay` accepts semantic DOM class assignments through `dom_classes`. A target may contain one whitespace-separated class string or an array of class strings.

```php
$display->setData([
	'dom_classes' => [
		'root' => ['customer-chatbot', 'theme-dark'],
		'main' => 'customer-chat',
		'composer' => ['customer-prompt']
	]
]);
```

Supported targets are:

| Target | DOM area |
| --- | --- |
| `root` | complete chatbot root |
| `conversation_panel` | conversation navigation panel |
| `opening` | opening or initial message |
| `main` | main chat area |
| `messages` | message log |
| `suggestions` | suggestion area |
| `canvas` | canvas side area |
| `composer` | complete prompt composer |
| `input` | prompt textarea |
| `actions` | composer action row |
| `ai_notice` | AI notice below or above the composer |

The target names are part of the ModularChatbot DOM contract. Configuration does not accept arbitrary CSS selectors. Existing ClientStack classes remain unchanged, so project-specific styling can be layered on top without replacing the built-in selectors.

### Realtime speech transcription

ModularChatbot supports live Mistral transcription through
`MistralRealtimeSpeechToTextProvider`. The provider requests a short-lived
session from Chatbot, captures mono microphone audio with `AudioWorklet`, sends
16 kHz PCM frames over the provider WebSocket, and writes incremental transcript
text into the instance-local composer.

Browser speech recognition remains the default when no configured speech service
is selected. The Voice plugin keeps the same manual microphone and automatic
dialog-mode lifecycle for both input providers.

Speech endpoint URLs contain only the chatbot `config_group` and `config_name`.
The backend resolves the selected STT or TTS service from that instance record;
the modular client neither receives nor chooses provider service ids.

## Mermaid diagrams

ClientStack owns the local Mermaid browser bundle and the corresponding BASE3 display.

The runtime asset is stored at:

```text
assets/mermaid/mermaid.min.js
```

`ClientStack\Display\MermaidDisplay` is discoverable as `mermaiddisplay` and loads the bundle through the shared AssetLoader. Consumers must resolve Mermaid through ClientStack and do not require a separate Mermaid BASE3 plugin.

## Display composition

`ClientStack\Display\TabControlDisplay` treats a tab entry id (`name`) separately from its optional renderer (`display`). Existing entries without `display` continue to use their `name` as the renderer name, while multiple entries may now point to the same `IDisplay` implementation with different data.

`ClientStack\Display\CompositeDisplay` composes multiple discoverable `IDisplay` instances into a responsive CSS grid. Each item has its own `id`, `display`, optional `data`, optional `title`, and optional `span`; the layout collapses to one column on small screens.

## Documentation

- [Frequently Asked Questions](docs/faq.md)
- [Privacy and data processing](PRIVACY.md)

## TabControlDisplay initial loading

`TabControlDisplay` normally renders the initial active display on the server and loads subsequent tabs through the configured display URLs.

When the calling application provides `initial_load_via_ajax => true` in the control's `setData()` array, the first display is also loaded through the existing AJAX tab navigation. The initial HTML contains an empty panel; the browser fetches the selected display through the same `loadTarget()` function used after clicking a tab. This is appropriate for host adapters whose AJAX endpoint must prepare display data before rendering. The option does not modify display configuration or file handling and defaults to `false` for existing consumers.

The calling application must make the configured display URL accessible in the authenticated browser context and provide a working JavaScript runtime. Loading and error messages are managed by the existing tab control.

## Global Chatbot Launcher

The platform-neutral global launcher is implemented once as an ES module under
`dev/GlobalChatbotLauncher/src/`. It publishes unchanged to
`assets/globalchatbotlauncher/` using `deploy-global-chatbot-launcher.sh`.
The new `GlobalChatbotLauncherConfigDisplay` stores presentation-only settings in
`ISettingsStore` dataset `global-chatbot-launcher/default`. It is independent of
chatbot backends, host permissions, and per-content chatbot identities.

Host adapters must only produce the launcher bootstrap element with a render URL,
labels, appearance, instance identity, and the settings read from the store,
then include the shared script and stylesheet through `IAssetResolver`.

An open overlay or sidebar can be restored after a full navigation within the
same browser tab when the respective option is enabled. The last display mode and
resized dimensions are kept in browser local storage. Window-open state is kept
in session storage. Browser fullscreen requires a user gesture and is never
entered automatically during restoration.

When `data-dock-target="body"` is selected, the launcher makes the reduced
body the real vertical scrollport while the dock is open and hides the root
viewport scrollbar. It preserves the current vertical scroll position while
switching between the body and document scrollports, including on close and
mode changes. Hosts with a separate layout target retain the existing
width/margin docking behavior. A host that uses the body as its dock target
must keep its own footer in document flow during docking; Base3Template
provides that rule for its standard page layout.


## ModularChatbot Markdown in AMD hosts

`MarkdownPlugin` uses the local `assets/marked/marked.js` UMD bundle. On
RequireJS/AMD hosts (such as Moodle), the UMD bundle registers a named
`marked` AMD module instead of setting `window.marked`. The plugin now
waits for the local script to load and resolves that AMD export when the
normal global parser is absent. It stores the parser on the plugin context,
so no new global object or host-specific loader is needed.

The canonical source is `dev/ModularChatbot/src/plugins/MarkdownPlugin.js`;
`deploy-modular-chatbot.sh` copies it to
`assets/modularchatbot/plugins/MarkdownPlugin.js`. Both versions must remain
identical. The corresponding ES-module `index.js` import is versioned to
invalidate the previous browser module cache. Test with `node test/MarkdownPluginAmdSmokeTest.mjs`.

## Shared authorization administration UI

`AuthorizationGroupsDisplay` and `AuthorizationFeaturesDisplay` are cross-host interfaces rendered in ClientStack. They require `UiFoundation\Api\IAuthorizationAudienceAdministrationService`, and perform a fresh `IAuthorizationService::decide(system/admin)` check before accessing backend data or accepting mutations. The host backend performs additional native guards; an AJAX action is not trusted merely because its tab was visible. The shared native-resource `AuthorizationAdministrationDisplay` remains unchanged.

The Groups and Features displays share a responsive audience editor with compact inputs, criterion fields below the assignment selector, and an advanced JSON area shown only when applicable. Generic editor labels are translated in ClientStack; criterion-specific display titles and property titles come from the criterion schema supplied by its owning module. Host-specific identifiers and existing form names are not modified. The user picker below performs only authorized, on-demand native directory queries. It does not change permission decisions, persistence or feature evaluation.


Host-managed groups provide a delete button next to their group description; code-defined groups do not. Deletion reuses the already-existing host removal operation, authorization and CSRF protection. Referenced or fallback groups remain protected by the host.

The forms retain the BASE3 any/all/not criterion grammar, show fixed code-owned memberships separately, and submit POST data with per-session CSRF tokens. Native role names and identifiers come from the existing host view; the editor shows role names and submits the original identifiers. When the host provides no role catalog, the existing numeric ID input remains available. Accepted criterion names continue to come from the host service. Without a configured host backend, these screens must not be included in navigation.

## Group and feature audience editor

`AuthorizationGroupsDisplay` and `AuthorizationFeaturesDisplay` share the same
`AuthorizationAudienceEditor` template, row renderer, styling and browser script.
The advanced-expression JSON editor aligns with the assignment type, and the
user-facing assignment types are host-neutral (`Group`, `Role`, `User`).

Group, role and criterion selects start with an explicit, non-selectable placeholder instead of silently preselecting the first value. The criterion configuration appears only after choosing a criterion.
Each discoverable criterion owns its localized `getSchema()["title"]` and its optional `properties[*]["title"]`, using the existing host translation service where available. ClientStack renders those schema fields without recognizing criterion identifiers, translating host/plugin-specific properties, or introducing another label resolver. Technical criterion names and stored expressions remain unchanged. When a schema lacks a title, the technical name is shown rather than inventing a translation.
The shared audience editor now enhances native user assignments through the
**ModularSelect** ES module while preserving the original numeric `[value]`
form field. If ES modules cannot load, the original numeric entry remains
usable. Existing IDs are resolved on demand for readable labels; adding users
uses bounded, server-paginated native searches rather than loading all users.
The display and the native host persist the unchanged numeric user IDs.

## ModularSelect client

ClientStack now includes the reusable **ModularSelect** ES module, a domain-neutral
single-/multi-select with a searchable accessible listbox. It accepts static
option arrays or an asynchronous paginated option source. No user, group,
feature or other business-specific behavior is built into the component.
Single-select is the default and returns a string ID (or `null`); `multiple: true`
returns an array of IDs. Mouse, touch and keyboard all commit selections.
Single-select closes after a pick, while multi-select stays open and allows
adding/removing multiple options. After choosing an item, the search field is
cleared and **Backspace** removes the most recently selected item when the field
is empty. With typed text, Backspace edits the search normally. Both modes keep
the chip's remove button for pointer and keyboard interaction. The
`closeOnSelect` setting is configurable.

The standalone development tree is supplied at:

```text
dev/ModularSelect/
```

Its deployable `src/` files are copied, unchanged, to:

```text
assets/modularselect/
```

Run `./deploy-modular-select.sh` from ClientStack to update the runtime copy.
Use `plugin/ClientStack/assets/modularselect/index.js` and
`plugin/ClientStack/assets/modularselect/styles/modularselect.css` through the
BASE3 asset resolver in consuming displays. The groups/features editor configures
its authorized user search as a `source` callback, without adding
business-specific logic to ModularSelect.

Long selected labels are visually ellipsized without shifting the dropdown
button; multi-select chips use a single, horizontally scrollable row. The
full label remains available in the DOM and via the chip tooltip, and keyboard
or mouse removal remains supported. Layout regression checks are in
`dev/ModularSelect/tests/browser-smoke/layout-smoke.py`.

The development directory is intentionally git-ignored by ClientStack like
other independent module checkouts. The included source is a local initial
scaffold, not a claimed upstream Git clone; add its actual repository URL to
the repository-management process only once that repository exists. See
`dev/ModularSelect/README.md`, `docs/INTEGRATION.md` and `PRIVACY.md` within
the module for APIs, examples, accessibility and privacy responsibilities.
Real keyboard, pointer and touch tests for both modes are in
`dev/ModularSelect/tests/browser-smoke/pointer-smoke.py`; they require optional
Python Playwright and Chromium, but no external network service.

### Group and feature user options

`AuthorizationGroupsDisplay` and `AuthorizationFeaturesDisplay` now use their
shared `AuthorizationAudienceEditor` template to configure ModularSelect.
A single class-map output (`AuthorizationAudienceUserSearchDisplay`,
`out=json`) handles both search and resolving existing numeric user IDs.
The endpoint accepts only form-encoded POST bodies, not query strings:

- `scope=groups|features` and `csrf=<token>`: the existing form CSRF token
  associated with that administration screen; the endpoint verifies the session.
- `action=search`, `query=<literal search text>`, `limit=1..30`,
  `offset=0..100000`: paged `IUsermanager::searchUsers()`; the backend is
  asked for one extra record to determine `hasMore`.
- `action=resolve`, `id=<positive native user ID>`: resolves a previously
  saved assignment through `IUsermanager::getUserById()` without enumerating
  all users; returns `{item: null}` if unavailable.

Every call requires a fresh explicit `IAuthorizationService::decide(system/admin)`
ALLOW and `IAuthorizationAudienceAdministrationService::assertCanAdminister()`.
The endpoint returns only numeric IDs and display labels composed of names,
logins and IDs, never email, roles or host internals. It sets `Cache-Control:
private, no-store`; the search term is carried in the POST body rather than
URL query parameters. No new persistence, criteria, feature permissions or
host-specific logic is introduced. `IUsermanager::getAllUsers()` is not used.

The host must deploy the new `IUsermanager::searchUsers()` implementation and
the assets from the earlier ModularSelect patch before enabling this editor.
The shared editor destroys select instances on row removal, AJAX form
replacement and tab unload. Check keyboard, zoom and screen-reader behavior in
the real host UI as part of acceptance, including error/fallback handling.

### Verification

Run the existing PHPUnit display tests and the new
`test/Display/AuthorizationAudienceUserSearchDisplayTest.php` and
`test/Display/AuthorizationAudienceModularSelectIntegrationTest.php` against
the currently deployed Framework/UiFoundation contracts. For an optional
end-to-end browser smoke test, run:

```bash
node test/Display/AuthorizationAudienceModularSelectBrowserSmoke.mjs
```

The browser smoke test requires Chromium and PHP CLI; it exercises the actual
shared editor template, a local same-origin user-options endpoint and the
deployed ModularSelect ES module without needing a live ILIAS installation.
It is not part of the ILIAS class-map build and should run only when explicitly
invoked. Complete the final manual screen-reader, focus and ILIAS admin AJAX
reload checks on the target host.


### Diagnostic scope administration

`DiagnosticScopeDisplay` is the shared administrative `IDisplay` for `IDiagnosticScope` implementations naming `diagnosticscopedisplay`. It requires the host's `IAuthorizationService` to explicitly grant `system/admin`. Its HTML view lists all classmap-discovered diagnostic areas as compact rows. Opening the generic Governance Diagnostics subtab does **not** execute checks: the administrator starts one area with its Run button. The selected area then expands **in place** as a single accordion panel, while another area remains closed. An explicit direct URL with `diagnostic_scope` still starts that area, preserving earlier deep links. Only the selected area's checks are evaluated through sequential, CSRF-protected POSTs. A restrained one-line status summary and progress indicator are shown inside that area. Each check uses one narrow title/status row, followed by a second, unobtrusive line of measured values. Explanations and remediation open automatically for non-passed results and remain individually expandable for passed checks. Search and the compact status dropdown filter the visible results without persisting them. Clicking the active area's Run Again button repeats just that area's checks.

The UI uses the existing `TabControlDisplay` with a single `diagnosticscopedisplay` navigation entry, which needs no scope parameters. `IDiagnosticRunner` supplies the areas at runtime. Existing direct links with `diagnostic_scope` remain supported. The display does not depend on MissionBay, its check implementations, or a particular host. General UI strings belong in the supported `ClientStack/lang/Diagnostics/*.ini` catalogs; scope and check strings remain in their supplying modules.

### Diagnostic report export

`DiagnosticScopeDisplay` enables **Copy report as JSON** only after the sequential run has completed. The browser constructs the report from the last selected and completed diagnostic area, regardless of search or status filters. Switching areas invalidates the previous report and starts a new run. It includes the scope, completion timestamp, all check IDs and descriptions, statuses, translated messages, remediation text, aggregate counters and safe measurement labels/values. A rerun invalidates the previous report until all checks complete. The export uses `navigator.clipboard.writeText()` in a user click handler and performs no server-side export request or persistent storage. Clipboard permission or secure-context failures are displayed using the owning ClientStack translations.

The report is intended for technical review, including manual use in an AI assistant. Administrators should still review its contents before pasting it into an external service.

### Feature-gated diagnostics display

`DiagnosticScopeDisplay` checks both `system/admin` and `feature/diagnosticreportsfeature` through the active `IAuthorizationService` before showing diagnostic results or servicing per-check AJAX requests. Host navigation must independently filter the corresponding tab; hiding a tab is not sufficient authorization. The display continues to execute checks in order and only keeps the report in the current browser view for JSON copying.

## Settings navigation

`ClientStack\Display\SettingsDisplay` displays settings for authenticated
users and discovers `UiFoundation\Api\ISettingsTab` contributions through
`IClassMap`. It filters child entries using each tab's feature decision, then
sorts top-level groups and their children by `getOrder()` and `getName()`. Root
groups have no content; the first permitted child becomes active. A group without
permitted children is omitted.

The existing `TabControlDisplay` remains the only two-level tab renderer. Its
settings entry URLs address `SettingsDisplay` itself; it validates a selected
entry again on each AJAX fragment request and applies the trusted
`getDisplayData()` value. Its navigation URL does not provide an authorization
context to the target. Direct `IDisplay` and API requests must independently
check the same feature and the domain permissions relevant to their operations.
The Jobs administrator now also requires a session-bound CSRF token and POST
for changes; read-only list requests remain GET.

ClientStack owns only its own settings contributions and its renderer texts in
`ClientStack/lang/Settings/` and `ClientStack/lang/TabControl/`. Other plugins
provide their own tabs and translations; no cross-plugin translation catalog is
maintained here. The XRM page uses the `settingsdisplay` output name, not a
static registry of other plugins' tabs.


## BASE3 Features settings audience

The `AuthorizationfeaturesSettingsTab` feature defaults to the code-defined
`development` BASE3 group. The actual Features display and backend continue
to check the host's independent `system/admin` permission. This default
cannot override a saved feature audience in the host configuration; empty
stored expressions remain explicit denials. The definition of development
membership belongs to the shared Base3Template plugin, not to ClientStack.

### Feature editor defaults

The shared audience editor preserves the selected technical group name of a
declared feature audience, including when the current host cannot list that
group. An unavailable default group is presented visibly rather than as an
empty required select. Group discovery and validation remain the host
administration service's responsibility. The editor itself does not grant
rights or persist feature rules.

## Settings discovery additions

`GovernanceSettingsTab` provides the Governance first-level group and `DiagnosticscopeSettingsTab` contributes the diagnostic scope display. Diagnostics reuse the framework-owned `DiagnosticReportsFeature`, including its default audience, and require the existing `system/admin` decision. No second diagnostic feature is introduced. The display itself protects HTML and JSON operations.

`GlobalChatbotLauncherSettingsTab` contributes ClientStack's launcher configuration below the `chatbotsettingstab` root supplied by the Chatbot plugin when installed. Its own feature and the existing `system/admin` requirement gate both the tab and direct display. All new ClientStack navigation labels are translated in `ClientStack/lang/Settings/`. The Settings renderer and tab control remain unchanged.

The existing `AgentFlowAdminDisplay` is also exposed as a BASE3 Settings tab through `AgentflowadminSettingsTab`. Both its HTML and JSON endpoints check that feature before scanning plugin-local flow definitions.

## Shared global launcher bootstrap and FileManager HTTP (2026-11-05)

`ClientStack\GlobalChatbotLauncher\GlobalChatbotLauncherBootstrap` is the single PHP renderer for the existing `assets/globalchatbotlauncher` ES module and CSS. It loads presentation flags from `GlobalChatbotLauncherSettings` (`global-chatbot-launcher/default`), produces escaped data attributes, and accepts an already-authorized host render URL, chatbot configuration identity, optional host appearance, and translated-label overrides. The launcher has no default icon. Host-specific branding belongs to its integration, not ClientStack. Use the same renderer in standalone BASE3 pages and, in the later ILIAS migration, the ILIAS UIHook. The host remains responsible for enforcing `GlobalChatbotLauncherFeature` at the launcher insertion and chatbot render endpoints.

`ClientStack\FileManager\FileManagerHttpService` implements the existing `assets/filemanager/adapters/HttpFileManagerAdapter.js` protocol, including resumable chunk uploads through `FileUploadService`. The service never selects a native user, storage owner, or authorization authority from a request. The host must independently authorize the request and pass trusted actor and owner identifiers. Its injected `IManagedFileStorageService` chooses the backend. File upload staging directory is injected by the project. The AJAX adapter now sends the `X-Requested-With: XMLHttpRequest` header for POST requests; a host should reject requests that lack it. The `FileManagerHttpService::buildEndpoints()` helper generates action URLs for the existing browser adapter.

For `chat.base3.de`, the project plugin binds FileBridge's `LocalManagedFileStorageService`, a shared `FileUploadService`, and the dedicated `ChatbotFileManagerOutput`. This initial phase supplies the shared runtime and protected HTTP endpoint. Enabling the resource section in the chatbot's automatic Settings tab and placing the launcher on all page templates are subsequent integration steps, not hidden alternative components.

## Phase 2: Authorized launcher insertion (2026-11-05)

`GlobalChatbotLauncherBootstrap::renderAuthorized()` checks the existing
`IAuthorizationService` for `feature/globalchatbotlauncherfeature` before
producing any launcher markup. It then uses the same `render()` implementation,
settings and deployed browser assets as ILIAS. The host must check the same
feature independently on its chatbot HTML endpoint. No UIHook icon or ILIAS
layout selector is a ClientStack default. `render()` remains available to
already-authorized host integrations; projects should use the explicit
`renderAuthorized()` method for a page insertion.

The existing `ClientStack\FileManager\FileManagerHttpService::buildEndpoints()`
returns the endpoint data required by the standard FileManager browser adapter.
Chat's settings tab passes this data unchanged to `ChatbotConfigDisplay`; the
project decides which storage owner and route are authorized.


## Games navigation

The discoverable `GamesSettingsTab` groups independently installed game contributions without requiring either game. PulseCore and NeonDrift own their feature-backed child tabs and existing gameplay authorization. All tab discovery continues through `SettingsDisplay` and `IClassMap`; no host-specific games registry exists.

## Host-independent FileManager services

`ClientStackPlugin` registers the existing `FileUploadService` and `FileManagerHttpService` as lazy, replaceable shared DI bindings. The upload session staging root is `DIR_TMP/filemanager-uploads`, outside the domain file store. A host or consumer output must authorize the authenticated actor, the owner identity and the requested action before invoking `FileManagerHttpService::handle()`. The service receives the real `IManagedFileStorageService` supplied by the active BASE3 host composition and never chooses an ILIAS backend itself.

The new MissionBay agent resources use this existing service through their own agent-owner administration output. Other clients such as the existing ILIAS chatbot retain their current host-specific integrations. If a host does not provide `IManagedFileStorageService`, FileManager endpoints cannot operate and must not be advertised. Resumable upload sessions left by failed or abandoned transfers need a host-controlled cleanup policy; the service does not create a second persistence system or an automatic retention mechanism.
