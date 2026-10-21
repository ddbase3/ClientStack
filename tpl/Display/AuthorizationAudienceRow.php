<?php
/** @var string $editorPrefix */
/** @var string|int $rowIndex */
/** @var array $rule */
/** @var bool $allowGroups */
/** @var bool $allowRoles */
/** @var array $availableGroups */
/** @var array $criterionOptions */
$kind = (string)($rule['kind'] ?? ($allowGroups ? 'group' : 'user'));
$numberActive = $kind === 'user' || ($kind === 'role' && !$hasRoleCatalog);
$criterionActive = $kind === 'criterion';
$configActive = in_array($kind, ['criterion', 'expression'], true);
$value = (string)($rule['value'] ?? '');
$config = (string)($rule['config'] ?? '{}');
$name = $editorPrefix . '[' . $rowIndex . ']';
$esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="base3-audience-row" data-audience-row data-kind="<?php echo $esc($kind); ?>">
	<label class="base3-audience-field base3-audience-kind"><?php echo $esc($t('assignment_type', 'Type')); ?>
		<select name="<?php echo $esc($name); ?>[kind]" data-audience-kind>
			<option value="user"<?php echo $kind === 'user' ? ' selected' : ''; ?>><?php echo $esc($t('assignment_user', 'User')); ?></option>
			<?php if ($allowRoles): ?><option value="role"<?php echo $kind === 'role' ? ' selected' : ''; ?>><?php echo $esc($t('assignment_role', 'Role')); ?></option><?php endif; ?>
			<?php if ($allowGroups): ?><option value="group"<?php echo $kind === 'group' ? ' selected' : ''; ?>><?php echo $esc($t('assignment_group', 'Group')); ?></option><?php endif; ?>
			<option value="criterion"<?php echo $kind === 'criterion' ? ' selected' : ''; ?>><?php echo $esc($t('assignment_criterion', 'Criterion')); ?></option>
			<option value="expression"<?php echo $kind === 'expression' ? ' selected' : ''; ?>><?php echo $esc($t('assignment_expression', 'Advanced expression')); ?></option>
		</select>
	</label>
	<label class="base3-audience-field base3-audience-target base3-audience-number-target" data-audience-number-wrap<?php echo $numberActive ? '' : ' hidden'; ?>>
		<span data-audience-number-label><?php echo $esc($t('user_id', 'User ID')); ?></span>
		<input type="number" inputmode="numeric" min="1" step="1" name="<?php echo $esc($name); ?>[value]" value="<?php echo $esc($numberActive ? $value : ''); ?>" data-audience-number<?php echo $numberActive ? ' required' : ' disabled'; ?>>
	</label>
	<div class="base3-audience-field base3-audience-target base3-audience-user-target" data-audience-user-wrap hidden>
		<span><?php echo $esc($t('user_select_label', 'Select user')); ?></span>
		<div data-audience-user-select></div>
	</div>
	<?php if ($hasRoleCatalog): ?>
		<label class="base3-audience-field base3-audience-target base3-audience-role-target" data-audience-role-wrap<?php echo $kind === 'role' ? '' : ' hidden'; ?>><?php echo $esc($t('assignment_role', 'Role')); ?>
			<select name="<?php echo $esc($name); ?>[value]" data-audience-role<?php echo $kind === 'role' ? ' required' : ' disabled'; ?>>
				<option value="" disabled<?php echo $kind !== 'role' || $value === '' ? ' selected' : ''; ?>><?php echo $esc($t('role_placeholder', 'Select a role')); ?></option>
				<?php foreach ($roleOptions as $roleId => $roleLabel): ?>
					<option value="<?php echo $esc((string)$roleId); ?>"<?php echo $kind === 'role' && $value === (string)$roleId ? ' selected' : ''; ?>><?php echo $esc($roleLabel); ?></option>
				<?php endforeach; ?>
				<?php if ($kind === 'role' && $value !== '' && !array_key_exists($value, $roleOptions)): ?>
					<option value="<?php echo $esc($value); ?>" selected><?php echo $esc(sprintf($t('role_unlisted', 'Role not in current catalog (ID %s)'), $value)); ?></option>
				<?php endif; ?>
			</select>
		</label>
	<?php endif; ?>
	<?php if ($allowGroups): ?>
		<label class="base3-audience-field base3-audience-target" data-audience-group-wrap<?php echo $kind === 'group' ? '' : ' hidden'; ?>><?php echo $esc($t('assignment_group', 'Group')); ?>
			<select name="<?php echo $esc($name); ?>[value]" data-audience-group<?php echo $kind === 'group' ? ' required' : ' disabled'; ?>>
				<option value="" disabled<?php echo $kind !== 'group' || $value === '' || !in_array($value, $availableGroups, true) ? ' selected' : ''; ?>><?php echo $esc($t('group_placeholder', 'Select a group')); ?></option>
				<?php foreach ($availableGroups as $groupName): ?>
					<?php $groupLabel = match ($groupName) {
						'development' => $t('group_development', 'Development'),
						'administration' => $t('group_administration', 'Administration'),
						'authenticated' => $t('group_authenticated', 'Authenticated'),
						default => ucwords(str_replace(['_', '-'], ' ', (string)$groupName))
					}; ?>
					<option value="<?php echo $esc($groupName); ?>"<?php echo $kind === 'group' && $value === $groupName ? ' selected' : ''; ?>><?php echo $esc($groupLabel); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	<?php endif; ?>
	<label class="base3-audience-field base3-audience-target" data-audience-criterion-wrap<?php echo $criterionActive ? '' : ' hidden'; ?>><?php echo $esc($t('assignment_criterion', 'Criterion')); ?>
		<select name="<?php echo $esc($name); ?>[value]" data-audience-criterion<?php echo $criterionActive ? ' required' : ' disabled'; ?>>
			<option value="" disabled<?php echo !$criterionActive || $value === '' || !in_array($value, array_column($criterionOptions, 'name'), true) ? ' selected' : ''; ?>><?php echo $esc($t('criterion_placeholder', 'Select a criterion')); ?></option>
			<?php foreach ($criterionOptions as $criterion): ?>
				<?php
				$criterionName = (string)$criterion['name'];
				$criterionLabel = trim((string)($criterion['schema']['title'] ?? ''));
				if ($criterionLabel === '') $criterionLabel = $criterionName;
				?>
				<option value="<?php echo $esc($criterionName); ?>"<?php echo $criterionActive && $value === $criterionName ? ' selected' : ''; ?>><?php echo $esc($criterionLabel); ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<div class="base3-audience-config" data-audience-config-wrap<?php echo $configActive ? '' : ' hidden'; ?>>
		<div class="base3-audience-properties" data-audience-properties></div>
		<label class="base3-audience-field base3-audience-expression" data-audience-json-wrap<?php echo $configActive ? '' : ' hidden'; ?>><span data-audience-json-label><?php echo $esc($t('criterion_config', 'Configuration (JSON)')); ?></span>
			<textarea rows="2" spellcheck="false" name="<?php echo $esc($name); ?>[config]" data-audience-json<?php echo $configActive ? '' : ' disabled'; ?>><?php echo $esc($config); ?></textarea>
		</label>
		<div class="base3-audience-schema" data-audience-schema aria-live="polite"></div>
	</div>
	<button class="btn btn-default" type="button" data-audience-remove aria-label="<?php echo $esc($t('remove_assignment', 'Remove assignment')); ?>"><?php echo $esc($t('remove_assignment', 'Remove assignment')); ?></button>
</div>
