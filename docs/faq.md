# ClientStack FAQ

## What is ClientStack?

ClientStack is the BASE3 plugin that provides a central registry and delivery layer for browser-side assets and reusable client-side UI building blocks.

It groups JavaScript, CSS, icons and related browser resources under stable logical asset names, makes those assets available through `IAssetService`, and uses the BASE3 asset resolver for deployment-specific public URLs.

ClientStack also contains reusable display implementations for common browser controls and administration views.

## What is the boundary between ClientStack and the libraries stored under `assets/`?

ClientStack manages and serves the local asset bundles. The individual libraries remain separate projects with their own behavior, documentation, versioning and licenses.

The ClientStack documentation therefore describes how those files are acquired, stored, registered and exposed. It does not replace the documentation of the individual libraries.

For library-specific APIs, browser behavior, security notes, configuration options and licensing terms, consult the respective library or project documentation.

## Which client libraries and bundles are currently present?

The current `assets/` tree contains the following top-level bundles:

| Bundle | Local path |
| --- | --- |
| AssetLoader | `assets/assetloader/` |
| Chart.js | `assets/chart/` |
| ChronoPicker | `assets/chronopicker/` |
| CKEditor 5 | `assets/ckeditor/` |
| ClassicChatbot | `assets/classicchatbot/` |
| DbDesigner | `assets/dbdesigner/` |
| Dropzone | `assets/dropzone/` |
| FullCalendar | `assets/fullcalendar/` |
| Bootstrap Icons | `assets/icons/` |
| jQuery | `assets/jquery/` |
| JqueryDataTable | `assets/jquerydatatable/` |
| jQuery UI | `assets/jqueryui/` |
| JsonLens | `assets/jsonlens/` |
| Leaflet | `assets/leaflet/` |
| Marked | `assets/marked/` |
| MathJax | `assets/mathjax/` |
| Mermaid | `assets/mermaid/` |
| ModularChatbot | `assets/modularchatbot/` |
| ModularDialog | `assets/modulardialog/` |
| ModularGrid | `assets/modulargrid/` |

This table is an inventory of locally deployed bundles only. It does not imply that every bundle is registered as a default logical asset or loaded on every page.

## Are the files under `dev/` part of ClientStack?

No. The `dev/` directory is a temporary developer workspace for checking out source repositories that are used while building or updating deployed client assets.

Only `dev/.gitignore` is tracked by the ClientStack repository. Repository checkouts below `dev/` are intentionally ignored and are not part of the committed ClientStack source.

A working copy or ZIP created from a developer machine can still contain such temporary directories if untracked files were included when the archive was created. They must not be treated as committed ClientStack content.

## Does ClientStack download libraries when the application is running?

No. The acquisition scripts are development and build tools.

`clone-all.sh` checks out repositories configured in `local/libs.json`. `build-assets.php` copies files from those repositories or downloads configured release files into `assets/`. The deployed application uses the resulting local files.

Running these scripts is an explicit maintenance action. It is not part of normal request processing.

## Where is the acquisition configuration stored?

Libraries managed by the generic acquisition process are described in:

```text
local/libs.json
```

The file can define a Git repository, branch or tag, a source subdirectory, direct download URLs and the target directory under `assets/`.

Not every asset bundle in the repository must be managed through this file. Some ClientStack-owned browser clients have dedicated deployment scripts, and other bundles may be maintained separately.

## What is `assets/versions.json`?

`assets/versions.json` records build provenance for libraries processed by `build-assets.php`.

For repository-based inputs it can contain the selected tag or branch, Git commit and commit timestamp. For direct downloads it records that the asset was obtained through the download path and the build timestamp.

It is build metadata, not runtime user data.

## How are logical assets represented?

A logical asset is represented by `ClientStack\Dto\LogicalAsset`. It has:

- a stable logical name,
- one or more `AssetFile` entries,
- an optional default flag,
- an optional version.

Each `AssetFile` contains the file path, asset type and optional version information.

## Which logical assets are registered by ClientStack itself?

`DefaultAssetService` currently registers these built-in logical assets:

- `assetloader`
- `jquery`
- `jqueryui`
- `dbdesigner`
- `jquerydatatable`
- `chart`

`assetloader` and `jquery` are currently marked as default assets by that service.

Additional assets can be registered dynamically from plugin-level `local/assets.json` files.

## How can another plugin register browser assets?

A plugin can provide:

```text
<Plugin>/local/assets.json
```

`DefaultAssetService` scans plugin directories for these files and registers the contained logical assets.

A definition specifies the logical name, one or more files, their type and whether the asset is a default asset.

## Does ClientStack hardcode public asset URLs?

The intended runtime pattern is to use the BASE3 asset resolver. Logical or plugin-internal paths are resolved to the public URL required by the current deployment.

This keeps the client assets independent of one fixed web-root layout.

## What reusable displays does ClientStack provide?

The current component includes displays for areas such as:

- rich text editing,
- classic and modular chatbot clients,
- Mermaid rendering,
- tab and composite display composition,
- configuration administration,
- jobs administration,
- log inspection,
- state-store administration,
- service diagnostics,
- user and permission diagnostics,
- agent-flow inspection.

These are ClientStack integration surfaces. The data shown or modified by a display comes from the service passed to that display by the surrounding BASE3 runtime.

## Does ClientStack own application configuration, logs or state?

No. ClientStack contains administration displays that can inspect or modify those areas through the corresponding BASE3 services, but the underlying configuration, logger, database and state-store implementations remain responsible for their own storage semantics.

For example, `ConfigurationAdminDisplay` works through `IConfiguration`, while `LogAdminDisplay` reads from `ILogger`. `StateStoreAdminDisplay` operates on the BASE3 state-store table used by the active database-backed setup.

## What is the Agent Flow administration display?

`AgentFlowAdminDisplay` is a viewer. It scans plugin `local/` subdirectories for JSON files whose filename contains `flow`, then renders the selected flow metadata, nodes, resources and connections.

It does not define or execute the flow format itself.

## How are the chatbot browser clients maintained?

ClientStack contains deployed browser files for the classic and modular chatbot clients under `assets/`.

The corresponding developer repositories are expected temporarily under `dev/ClassicChatbot/` and `dev/ModularChatbot/` when their deployment scripts are used. The scripts copy the relevant source files into the checked-in asset directories and then run the client repository's deployment verification.

The source repositories under `dev/` are not committed as part of ClientStack.

## Where should library-specific questions be documented?

Questions about an individual library belong to that library's own documentation. This includes topics such as:

- library APIs,
- browser storage used by that library,
- remote endpoints used by that library,
- security advisories,
- supported browsers,
- library-specific accessibility behavior,
- library-specific licenses.

ClientStack documents the integration and local asset-management boundary only.

## What license applies?

ClientStack itself is distributed under GPL-3.0 as stated in the repository `LICENSE` file.

Bundled client libraries can use different licenses. Their applicable license terms must be evaluated and preserved independently of the ClientStack license. `local/libs.json`, the checked-in asset tree and the respective upstream projects are the relevant references for that inventory.

## Where can I find privacy information?

See [PRIVACY.md](../PRIVACY.md) for the ClientStack-specific privacy and data-processing notes.
