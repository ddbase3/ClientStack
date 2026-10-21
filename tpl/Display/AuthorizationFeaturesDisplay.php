<?php
$rows = (array)($this->_['rows'] ?? []);
$criterionOptions = (array)($this->_['criterionOptions'] ?? []);
$availableGroups = (array)($this->_['groups'] ?? []);
$translations = (array)($this->_['translations'] ?? []);
$t = static fn(string $key, string $fallback): string => (string)($translations[$key] ?? $fallback);
$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section data-base3-display="authorizationfeaturesdisplay" class="base3-authorization-editor">
	<h3><?php echo $esc($t('page_title', 'BASE3 features')); ?></h3>
	<p><?php echo $esc($t('page_description', 'Assign groups, native users, native roles or criteria to discoverable features; native host RBAC remains independent.')); ?></p>
	<?php if (($this->_['message'] ?? '') !== ''): ?><p role="status" tabindex="-1" data-base3-status><?php echo $esc((string)$this->_['message']); ?></p><?php endif; ?>
	<p role="alert" data-base3-error hidden></p>
	<form method="post" action="<?php echo $esc((string)$this->_['endpoint']); ?>" data-base3-ajax-form>
		<?php foreach ($rows as $feature): ?>
			<fieldset>
				<legend><?php echo $esc((string)$feature['label']); ?></legend>
				<p><?php echo $esc((string)$feature['description']); ?></p>
				<div class="base3-group-summary"><code><?php echo $esc((string)$feature['name']); ?></code> · Permissions:
					<?php foreach ($feature['permissions'] as $permission): ?><code><?php echo $esc((string)$permission); ?></code> <?php endforeach; ?>
				</div>
				<?php
				$editorPrefix = 'base3_feature_rules[' . $feature['name'] . ']';
				$editorRows = $feature['rules'];
				$allowGroups = true;
				$allowRoles = true;
				require __DIR__ . '/AuthorizationAudienceEditor.php';
				?>
			</fieldset>
		<?php endforeach; ?>
		<input type="hidden" name="base3_feature_action" value="save">
		<input type="hidden" name="base3_feature_csrf" value="<?php echo $esc((string)$this->_['csrf']); ?>">
		<button type="submit" class="btn btn-primary"><?php echo $esc($t('save', 'Save feature audiences')); ?></button>
	</form>
	<p class="base3-audience-hint"><?php echo $esc($t('roles_hint', 'Native role names come from the host; role identifiers remain unchanged when saved.')); ?></p>
</section>
<?php if (!empty($this->_['modularSelectCssUrl'])): ?>
<link rel="stylesheet" href="<?php echo $esc((string)$this->_['modularSelectCssUrl']); ?>">
<?php endif; ?>
<?php require __DIR__ . '/AuthorizationAudienceStyles.php'; ?>
<?php require __DIR__ . '/AuthorizationAudienceScript.php'; ?>
<script>
(() => {
	const selector = '[data-base3-display="authorizationfeaturesdisplay"]';
	function bind(root) {
		if (root.dataset.bound === 'true') return;
		root.dataset.bound = 'true';
		window.BASE3AudienceEditor.bindAll(root);
		const form = root.querySelector('[data-base3-ajax-form]');
		if (!form) return;
		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			const button = form.querySelector('button[type="submit"]');
			const error = root.querySelector('[data-base3-error]');
			button.disabled = true;
			error.hidden = true;
			try {
				const response = await fetch(form.action, {method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
				if (!response.ok) throw new Error('Saving feature audiences failed (HTTP ' + response.status + ').');
				const replacement = new DOMParser().parseFromString(await response.text(), 'text/html').querySelector(selector);
				if (!replacement) throw new Error('The updated feature administration response is invalid.');
				window.BASE3AudienceEditor.unbindAll(root);
				root.replaceWith(replacement);
				bind(replacement);
				const status = replacement.querySelector('[data-base3-status]');
				if (status) status.focus();
			} catch(e) {
				error.textContent = e instanceof Error ? e.message : 'Saving feature audiences failed.';
				error.hidden = false;
				button.disabled = false;
			}
		});
	}
	document.querySelectorAll(selector).forEach(bind);
})();
</script>
