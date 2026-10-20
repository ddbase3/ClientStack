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

The forms retain the BASE3 any/all/not criterion grammar, show fixed code-owned memberships separately, and submit POST data with per-session CSRF tokens. Role suggestions and accepted criterion names come from the host service, not the UI. Without a configured host backend, these screens must not be included in navigation.
