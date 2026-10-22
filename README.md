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

`DiagnosticScopeDisplay` is the shared administrative `IDisplay` for `IDiagnosticScope` implementations naming `diagnosticscopedisplay`. It requires the host's `IAuthorizationService` to explicitly grant `system/admin`. Its HTML view lists classmap-discovered checks in the selected scope and automatically evaluates them using one CSRF-protected POST per check, sequentially. The summary shows passed/failed/warning/error/pending counts and a progress indicator. Search and status filters operate on the visible results without persisting them. A refresh reruns all checks.

The UI uses the existing `TabControlDisplay` setup: each navigation entry supplies `data: {scope: <name>}` for embedded rendering and `params: {diagnostic_scope: <name>}` for AJAX loading. Each scope can instead name its own `IDisplay` implementation. The display does not depend on MissionBay or a particular host. General UI strings belong in `ClientStack/lang/Diagnostics/{de,en}.ini`; scope and check strings remain in their supplying modules.

### Diagnostic report export

`DiagnosticScopeDisplay` enables **Copy report as JSON** only after the sequential run has completed. The browser constructs the report from the currently displayed, completed results, regardless of search or status filters. It includes the scope, completion timestamp, all check IDs and descriptions, statuses, translated messages, remediation text, aggregate counters and safe measurement labels/values. A rerun invalidates the previous report until all checks complete. The export uses `navigator.clipboard.writeText()` in a user click handler and performs no server-side export request or persistent storage. Clipboard permission or secure-context failures are displayed using the owning ClientStack translations.

The report is intended for technical review, including manual use in an AI assistant. Administrators should still review its contents before pasting it into an external service.

### Feature-gated diagnostics display

`DiagnosticScopeDisplay` checks both `system/admin` and `feature/diagnosticreportsfeature` through the active `IAuthorizationService` before showing diagnostic results or servicing per-check AJAX requests. Host navigation must independently filter the corresponding tab; hiding a tab is not sufficient authorization. The display continues to execute checks in order and only keeps the report in the current browser view for JSON copying.
