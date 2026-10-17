# GlobalChatbotLauncher

The single BASE3 global chatbot window implementation for ILIAS, WordPress,
Moodle and other hosts. This is an ES module. Do not copy or fork its JavaScript
or CSS into host integrations.

Source: `src/`; published files: `ClientStack/assets/globalchatbotlauncher/`.
Run `../../deploy-global-chatbot-launcher.sh` after editing the source. The
published tree is an exact copy of the source tree.

Embed a hidden element with `data-base3-global-chatbot-launcher`, the render URL,
configuration identity, translated labels and window options. Then load
`index.js` as a module and the CSS using the host's `IAssetResolver`.

The host alone decides visibility and builds its own BASE3 chatbot render URL.
The module does not interact with authorization, chatbot services or storage
other than browser session/local storage for window state and dimensions.

When a window mode enables resizing, the resize grip supports both pointer dragging
and keyboard arrow keys. Hold Shift for larger keyboard increments.

The host may supply formatted branding through `data-header-title`. Only
`strong`, `b`, `em`, `i` and `br` are inserted into the live title element;
all attributes are removed, and scripts/styles are discarded.
