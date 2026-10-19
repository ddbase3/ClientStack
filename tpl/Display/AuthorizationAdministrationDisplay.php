<?php
$e = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$rows = (array)($this->_['rows'] ?? []);
$permissions = (array)($this->_['permissions'] ?? []);
$capabilities = $this->_['capabilities'] ?? null;
?>
<section class="base3-native-rights" data-base3-display="authorizationadministrationdisplay">
	<h3>Native resource permissions</h3>
	<p>The selected host authority remains responsible for role inheritance, effective grants, and permission updates.</p>
	<form action="<?php echo $e($this->_['endpoint']); ?>" method="get" data-base3-ajax-form>
		<label>Authority instance <input name="authority" value="<?php echo $e($this->_['authority']); ?>" required></label>
		<label>Resource type <input name="resource_type" value="<?php echo $e($this->_['resource_type']); ?>" required></label>
		<label>Resource ID <input name="resource_id" value="<?php echo $e($this->_['resource_id']); ?>" required></label>
		<button type="submit" class="btn btn-default">Inspect</button>
	</form>
	<?php if ((string)$this->_['error'] !== ''): ?><p role="alert"><?php echo $e($this->_['error']); ?></p><?php endif; ?>
	<?php if ((string)$this->_['message'] !== ''): ?><p role="status"><?php echo $e($this->_['message']); ?></p><?php endif; ?>
	<p data-base3-error role="alert" hidden></p>
	<?php if ($capabilities !== null && $capabilities->canRead): ?>
		<?php if (!empty($capabilities->limitations)): ?><p><?php echo $e(implode(' ', $capabilities->limitations)); ?></p><?php endif; ?>
		<div style="overflow-x:auto"><table class="table table-striped">
			<thead><tr><th scope="col">Native subject</th><th scope="col">Direct rights</th><th scope="col">Inherited rights</th><th scope="col">Edit</th></tr></thead>
			<tbody>
			<?php foreach ($rows as $row): ?>
				<?php $subject = $row['subject']; ?>
				<tr>
					<th scope="row"><?php echo $e($subject->label); ?> <small>(<?php echo $e($subject->reference->kind . ':' . $subject->reference->id); ?>)</small></th>
					<td><?php echo $e(implode(', ', $row['direct']) ?: '—'); ?></td>
					<td><?php echo $e(implode(', ', $row['inherited']) ?: '—'); ?></td>
					<td>
						<?php if ($capabilities->canWrite && $subject->editable): ?>
							<form action="<?php echo $e($this->_['endpoint']); ?>" method="post" data-base3-ajax-form>
								<input type="hidden" name="authority" value="<?php echo $e($this->_['authority']); ?>">
								<input type="hidden" name="resource_type" value="<?php echo $e($this->_['resource_type']); ?>">
								<input type="hidden" name="resource_id" value="<?php echo $e($this->_['resource_id']); ?>">
								<input type="hidden" name="subject_kind" value="<?php echo $e($subject->reference->kind); ?>">
								<input type="hidden" name="subject_id" value="<?php echo $e($subject->reference->id); ?>">
								<input type="hidden" name="base3_rights_action" value="replace">
								<input type="hidden" name="base3_rights_csrf" value="<?php echo $e($this->_['csrf']); ?>">
								<fieldset><legend>Direct rights: <?php echo $e($subject->label); ?></legend>
								<?php foreach ($permissions as $permission): ?>
									<?php $key = $permission->scope . '/' . $permission->permission; ?>
									<label style="display:block"><input type="checkbox" name="base3_permissions[]" value="<?php echo $e($key); ?>"<?php echo in_array($key, $row['direct'], true) ? ' checked' : ''; ?>> <?php echo $e($permission->label ?: $key); ?></label>
								<?php endforeach; ?></fieldset>
								<button type="submit" class="btn btn-default">Save direct rights</button>
							</form>
						<?php else: ?>Read-only<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
	<?php endif; ?>
</section>
<script>
(() => {
	const selector = '[data-base3-display="authorizationadministrationdisplay"]';
	function bind(root) {
		root.querySelectorAll('[data-base3-ajax-form]').forEach(form => {
			if (form.dataset.bound) return;
			form.dataset.bound = 'true';
			form.addEventListener('submit', async event => {
				event.preventDefault();
				const btn = form.querySelector('button[type="submit"]');
				const error = root.querySelector('[data-base3-error]');
				btn.disabled = true; error.hidden = true;
				try {
					const isGet = form.method.toLowerCase() === 'get';
					const data = new FormData(form);
					const url = isGet ? form.action + (form.action.includes('?') ? '&' : '?') + new URLSearchParams(data) : form.action;
					const response = await fetch(url, {method: form.method, body: isGet ? undefined : data, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}});
					if (!response.ok) throw new Error('Native rights request failed (' + response.status + ')');
					const next = new DOMParser().parseFromString(await response.text(), 'text/html').querySelector(selector);
					if (!next) throw new Error('Invalid native rights response.');
					root.replaceWith(next); bind(next);
				} catch(e) { error.textContent = e instanceof Error ? e.message : 'Rights request failed.'; error.hidden = false; btn.disabled = false; }
			});
		});
	}
	document.querySelectorAll(selector).forEach(bind);
})();
</script>
