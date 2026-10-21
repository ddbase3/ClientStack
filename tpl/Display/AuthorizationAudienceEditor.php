<?php
/** @var string $editorPrefix */
/** @var array $editorRows */
/** @var bool $allowGroups */
/** @var bool $allowRoles */
/** @var array $availableGroups */
/** @var array $criterionOptions */
$roleOptions = [];
foreach ((array)($this->_['roleOptions'] ?? []) as $role) {
	if (!is_array($role)) continue;
	$id = trim((string)($role['id'] ?? ''));
	if ($id === '') continue;
	$label = trim((string)($role['label'] ?? ''));
	$roleOptions[$id] = $label !== '' ? $label : $t('assignment_role', 'Role') . ' ' . $id;
}
$hasRoleCatalog = $allowRoles && $roleOptions !== [];
$schemaCatalog = [];
foreach ($criterionOptions as $criterion) $schemaCatalog[$criterion['name']] = $criterion['schema'];
$esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="base3-audience-editor" data-audience-editor data-counter="<?php echo count($editorRows); ?>"
	data-schemas="<?php echo $esc(json_encode($schemaCatalog, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)); ?>"
	data-audience-search-endpoint="<?php echo $esc((string)($this->_['userSearchEndpoint'] ?? '')); ?>"
	data-audience-search-scope="<?php echo $esc((string)($this->_['userSearchScope'] ?? '')); ?>"
	data-audience-search-csrf="<?php echo $esc((string)($this->_['csrf'] ?? '')); ?>"
	data-audience-select-module="<?php echo $esc((string)($this->_['modularSelectModuleUrl'] ?? '')); ?>"
	data-user-select-label="<?php echo $esc($t('user_select_label', 'Select user')); ?>"
	data-user-search-placeholder="<?php echo $esc($t('user_search_placeholder', 'Search name, login or ID...')); ?>"
	data-user-search-loading="<?php echo $esc($t('user_search_loading', 'Searching users...')); ?>"
	data-user-search-empty="<?php echo $esc($t('user_search_empty', 'No matching users.')); ?>"
	data-user-search-error="<?php echo $esc($t('user_search_error', 'User search unavailable.')); ?>"
	data-user-search-options="<?php echo $esc($t('user_search_options', 'Users')); ?>"
	data-user-search-show-options="<?php echo $esc($t('user_search_show_options', 'Show users')); ?>"
	data-user-search-hide-options="<?php echo $esc($t('user_search_hide_options', 'Hide users')); ?>"
	data-user-search-remove="<?php echo $esc($t('user_search_remove', 'Remove {label}')); ?>"
	data-user-search-load-more="<?php echo $esc($t('user_search_load_more', 'Load more')); ?>"
	data-user-search-min-chars="<?php echo $esc($t('user_search_min_chars', 'Enter at least {count} characters.')); ?>"
	data-user-search-results="<?php echo $esc($t('user_search_results', '{count} users available.')); ?>"
	data-user-select-required="<?php echo $esc($t('user_select_required', 'Please select a user.')); ?>"
	data-user-label="<?php echo $esc($t('user_id', 'User ID')); ?>"
	data-role-label="<?php echo $esc($t('role_id', 'Role ID')); ?>"
	data-config-label="<?php echo $esc($t('criterion_config', 'Criterion configuration (JSON)')); ?>"
	data-expression-label="<?php echo $esc($t('expression_json', 'Expression (JSON)')); ?>"
	data-array-placeholder="<?php echo $esc($t('array_placeholder', 'Comma-separated values')); ?>"
	data-schema-fields-hint="<?php echo $esc($t('schema_fields_hint', 'Fields derived from the criterion schema.')); ?>"
	data-schema-no-fields="<?php echo $esc($t('schema_no_fields', 'This criterion has no parameters.')); ?>"
	data-schema-json-hint="<?php echo $esc($t('schema_json_hint', 'Enter configuration according to the criterion schema.')); ?>">
	<div data-audience-list>
		<?php foreach ($editorRows as $rowIndex => $rule): ?>
			<?php require __DIR__ . '/AuthorizationAudienceRow.php'; ?>
		<?php endforeach; ?>
	</div>
	<template data-audience-template>
		<?php $rowIndex = '__INDEX__'; $rule = []; require __DIR__ . '/AuthorizationAudienceRow.php'; ?>
	</template>
	<button class="btn btn-default" type="button" data-audience-add><?php echo $esc($t('add_assignment', 'Add assignment')); ?></button>
	<p class="base3-audience-hint"><?php echo $esc($t('assignment_hint', 'Assignments are combined with OR. Advanced expressions support AND, OR and NOT.')); ?></p>
</div>
