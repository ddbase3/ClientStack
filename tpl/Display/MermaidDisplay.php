<?php
	$mermaid = (string) $this->_['mermaid'];
	$mermaidUrl = (string) $this->_['mermaidUrl'];
	$widgetId = 'b3-mermaid-' . md5(uniqid('', true));
	$renderId = $widgetId . '-svg';
	$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;
?>
<div id="<?php echo htmlspecialchars($widgetId, ENT_QUOTES, 'UTF-8'); ?>"></div>

<script>
(function() {
	const hostId = <?php echo json_encode($widgetId, $jsonFlags); ?>;
	const renderId = <?php echo json_encode($renderId, $jsonFlags); ?>;
	const source = <?php echo json_encode($mermaid, $jsonFlags); ?>;
	const src = <?php echo json_encode($mermaidUrl, $jsonFlags); ?>;

	async function initMermaidWidget() {
		const host = document.getElementById(hostId);
		if(!host || host.dataset.b3MermaidState === 'loading' || host.dataset.b3MermaidState === 'rendered') {
			return;
		}

		host.dataset.b3MermaidState = 'loading';

		try {
			if(!window.__mermaid_load_promise__) {
				window.__mermaid_load_promise__ = AssetLoader.loadScriptAsync(src).then(function() {
					if(!window.mermaid) {
						throw new Error('Mermaid was not found after loading');
					}

					return window.mermaid;
				});
			}

			const mermaidApi = await window.__mermaid_load_promise__;

			if(!window.__mermaid_initialize_promise__) {
				window.__mermaid_initialize_promise__ = Promise.resolve().then(function() {
					mermaidApi.initialize({
						startOnLoad: false
					});

					return mermaidApi;
				});
			}

			await window.__mermaid_initialize_promise__;

			const result = await mermaidApi.render(renderId, source);
			host.innerHTML = result.svg || result;

			if(result.bindFunctions) {
				result.bindFunctions(host);
			}

			host.dataset.b3MermaidState = 'rendered';
		} catch(error) {
			host.dataset.b3MermaidState = 'error';
			host.textContent = 'Mermaid error: ' + (error && error.message ? error.message : error);
			console.error('Mermaid rendering failed:', error);
		}
	}

	if(document.readyState !== 'loading') {
		initMermaidWidget();
	} else {
		document.addEventListener('DOMContentLoaded', initMermaidWidget, { once: true });
	}

	window.addEventListener('mermaid:init', initMermaidWidget, { once: true });
})();
</script>
