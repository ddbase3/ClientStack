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

LGPL License
