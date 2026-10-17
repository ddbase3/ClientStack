<?php
	$this->loadBricks('GlobalChatbotLauncherConfigDisplay');
	$bricks = is_array($this->_['bricks']['globalchatbotlauncherconfigdisplay'] ?? null)
		? $this->_['bricks']['globalchatbotlauncherconfigdisplay'] : [];
	$t = static fn(string $key, string $fallback): string => (string)($bricks[$key] ?? $fallback);
	$e = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	$settings = $this->_['settings'];
	$fields = [
		'mode_overlay_enabled' => ['overlay', 'Floating window', 'Floating chat window in the lower right corner.'],
		'mode_modal_enabled' => ['modal', 'Dialog', 'Centred dialog with backdrop.'],
		'mode_docked_enabled' => ['docked', 'Sidebar', 'Side panel that can shift the main content.'],
		'mode_cover_enabled' => ['cover', 'Full window', 'Chatbot fills the browser window.'],
		'mode_fullscreen_enabled' => ['fullscreen', 'Fullscreen', 'Browser fullscreen, subject to browser permissions.'],
		'resizable_overlay' => ['resizable_overlay', 'Resize floating window', 'Users may resize and retain the window size.'],
		'resizable_modal' => ['resizable_modal', 'Resize dialog', 'Users may resize and retain the dialog size.'],
		'resizable_docked' => ['resizable_docked', 'Resize sidebar', 'Users may change and retain the sidebar width.'],
		'remember_last_mode' => ['remember_last_mode', 'Remember selected mode', 'Store the last mode in browser local storage.'],
		'reopen_overlay' => ['reopen_overlay', 'Reopen floating window on navigation', 'Restore an open floating window across page changes within the tab.'],
		'reopen_docked' => ['reopen_docked', 'Reopen sidebar on navigation', 'Restore an open sidebar across page changes within the tab.']
	];
?>
<section class="base3-global-launcher-config" data-global-launcher-admin>
	<h2><?php echo $e($this->_['title']); ?></h2>
	<p><?php echo $e($this->_['description']); ?></p>
	<p class="base3-global-launcher-config-note"><?php echo $e($t('identity_note', 'Only launcher presentation is configured here. The global chatbot instance and its backend are configured in the preceding Chatbot Configuration tab.')); ?></p>
	<form data-launcher-settings-form data-save-url="<?php echo $e($this->_['save_url']); ?>">
		<?php foreach (['modes' => ['mode_overlay_enabled','mode_modal_enabled','mode_docked_enabled','mode_cover_enabled','mode_fullscreen_enabled'], 'resize' => ['resizable_overlay','resizable_modal','resizable_docked'], 'behaviour' => ['remember_last_mode','reopen_overlay','reopen_docked']] as $section => $keys): ?>
		<fieldset>
			<legend><?php echo $e($t($section, ucfirst($section))); ?></legend>
			<?php foreach ($keys as $key): $info = $fields[$key]; ?>
			<label class="base3-global-launcher-config-row">
				<input type="checkbox" name="<?php echo $e($key); ?>" value="1" <?php echo !empty($settings[$key]) ? 'checked' : ''; ?> />
				<span><strong><?php echo $e($t($info[0], $info[1])); ?></strong><small><?php echo $e($t($info[0] . '_info', $info[2])); ?></small></span>
			</label>
			<?php endforeach; ?>
		</fieldset>
		<?php endforeach; ?>
		<div role="status" aria-live="polite" data-launcher-settings-status></div>
		<button type="submit" class="btn btn-primary"><?php echo $e($t('save', 'Save launcher settings')); ?></button>
	</form>
</section>
<style>
.base3-global-launcher-config{max-width:850px;color:inherit}
.base3-global-launcher-config h2{margin:0 0 12px}
.base3-global-launcher-config-note{padding:12px;border-left:3px solid #5676ae;background:#f4f6fb}
.base3-global-launcher-config fieldset{margin:14px 0;padding:14px 18px;border:1px solid #d4d9e2;border-radius:6px}
.base3-global-launcher-config legend{font-size:1.05em;font-weight:600;width:auto;padding:0 8px}
.base3-global-launcher-config-row{display:flex;gap:10px;padding:8px 4px;align-items:flex-start;cursor:pointer}
.base3-global-launcher-config-row span{display:flex;flex-direction:column;gap:3px}
.base3-global-launcher-config-row small{color:#525d6b}
.base3-global-launcher-config-row input{margin-top:4px}
.base3-global-launcher-config [data-launcher-settings-status]{min-height:24px;margin:8px 0}
</style>
<script>
(function() {
	var roots = document.querySelectorAll('[data-global-launcher-admin]');
	var root = roots[roots.length - 1];
	if (!root || root.dataset.initialized === '1') return;
	root.dataset.initialized = '1';
	var form = root.querySelector('[data-launcher-settings-form]');
	var status = root.querySelector('[data-launcher-settings-status]');
	var button = form.querySelector('button[type="submit"]');
	form.addEventListener('submit', async function(event) {
		event.preventDefault();
		button.disabled = true;
		status.textContent = '';
		try {
			var response = await fetch(form.dataset.saveUrl, {
				method: 'POST', credentials: 'same-origin',
				headers: {'X-Requested-With': 'XMLHttpRequest'},
				body: new FormData(form)
			});
			var result = await response.json();
			if (!response.ok || !result.success) throw new Error(result.message || 'Save failed');
			status.textContent = <?php echo json_encode($t('saved', 'Launcher settings saved.'), JSON_HEX_TAG | JSON_HEX_AMP); ?>;
		} catch (error) {
			status.textContent = String(error.message || error);
		} finally {
			button.disabled = false;
		}
	});
})();
</script>
