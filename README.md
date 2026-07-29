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
