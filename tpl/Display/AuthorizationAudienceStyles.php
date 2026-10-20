<style>
.base3-authorization-editor {background:#fff;border:1px solid #d6d6d6;border-radius:6px;padding:18px;color:#333;max-width:100%;}
.base3-authorization-editor h3 {margin:0 0 10px;font-size:1.2em;}
.base3-authorization-editor fieldset {border:1px solid #dedede;border-radius:6px;padding:16px;margin:16px 0;min-width:0;}
.base3-authorization-editor legend {font-size:1em;font-weight:600;padding:0 6px;margin-bottom:0;}
.base3-authorization-editor label {font-weight:normal;}
/* Host CSS must not override the visibility of inactive assignment fields. */
.base3-authorization-editor [hidden] {display:none !important;}
.base3-audience-editor {container-type:inline-size;}
.base3-audience-row {
	display:grid;
	grid-template-columns:minmax(175px,220px) minmax(0,1fr) max-content;
	grid-template-areas:"kind target remove" ". config remove";
	column-gap:18px;
	row-gap:10px;
	align-items:start;
	padding:16px 0;
	border-bottom:1px solid #e5e5e5;
	min-width:0;
}
.base3-audience-row:last-child {border-bottom:0;}
/* Expressions have no target input: place their JSON editor beside the assignment type. */
.base3-audience-row[data-kind="expression"] {grid-template-areas:"kind target remove";}
.base3-audience-row[data-kind="expression"] > .base3-audience-config {grid-area:target;}
.base3-audience-row > .base3-audience-kind {grid-area:kind;max-width:220px;}
.base3-audience-row > .base3-audience-target {grid-area:target;max-width:440px;}
.base3-audience-row > .base3-audience-number-target {max-width:240px;}
.base3-audience-row > .base3-audience-role-target {max-width:320px;}
.base3-audience-field {display:flex;flex-direction:column;gap:5px;min-width:0;margin:0;}
.base3-audience-field input:not([type="checkbox"]),
.base3-audience-field select,
.base3-audience-field textarea {
	display:block;
	width:100%;
	max-width:100%;
	min-width:0;
	box-sizing:border-box;
	padding:7px 9px;
	margin:0;
	border:1px solid #a7a7a7;
	border-radius:4px;
	background-color:#fff;
	color:inherit;
	font:inherit;
	min-height:36px;
}
.base3-audience-field textarea {resize:vertical;min-height:82px;line-height:1.45;}
.base3-audience-field input[type="checkbox"] {width:18px;height:18px;align-self:start;margin:5px 0;}
.base3-audience-config {grid-area:config;min-width:0;max-width:900px;}
.base3-audience-properties {display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,210px),260px));gap:12px 16px;align-items:start;}
.base3-audience-expression {max-width:760px;margin-top:4px;}
.base3-audience-config > .base3-audience-expression:first-child {margin-top:0;}
.base3-audience-row > [data-audience-remove] {grid-area:remove;justify-self:end;align-self:start;margin-top:25px;white-space:nowrap;}
.base3-audience-schema,.base3-audience-hint {font-size:12px;color:#555;margin:7px 0 0;line-height:1.45;overflow-wrap:anywhere;}
.base3-audience-schema:empty {display:none;}
.base3-authorization-editor [data-audience-error] {border:1px solid #b44;padding:8px;color:#822;}
.base3-authorization-editor [aria-busy="true"] {opacity:.75;}
.base3-authorization-editor button {white-space:nowrap;}
.base3-authorization-editor .base3-group-heading {display:flex;align-items:start;justify-content:space-between;gap:12px;margin:8px 0 14px;}
.base3-authorization-editor .base3-group-heading .base3-group-summary {margin:0;flex:1;min-width:0;}
.base3-authorization-editor .base3-group-delete {
	flex:none;
	background-color:#fff !important;
	background-image:none !important;
	color:#852525 !important;
	border:1px solid #b85a5a !important;
}
.base3-authorization-editor .base3-group-delete:hover,
.base3-authorization-editor .base3-group-delete:focus-visible {
	background-color:#fff1f1 !important;
	background-image:none !important;
	color:#721717 !important;
	border-color:#852525 !important;
}
.base3-authorization-editor .base3-group-delete:focus-visible {outline:2px solid #225cb0;outline-offset:2px;}
.base3-authorization-editor .base3-group-summary {font-size:13px;color:#555;margin:6px 0 12px;overflow-wrap:anywhere;}
.base3-authorization-editor .base3-group-summary code,.base3-authorization-editor .base3-audience-hint code {overflow-wrap:anywhere;white-space:normal;}
.base3-authorization-editor .base3-action-row {display:grid;grid-template-columns:repeat(2,minmax(0,320px)) max-content;gap:12px 16px;align-items:end;margin:14px 0;}
.base3-authorization-editor .base3-action-row label {display:flex;flex-direction:column;gap:5px;min-width:0;}
.base3-authorization-editor .base3-action-row input {width:100%;max-width:100%;min-width:0;min-height:36px;box-sizing:border-box;padding:7px 9px;}
.base3-authorization-editor .base3-action-row > button {justify-self:start;}
.base3-authorization-editor .base3-section {border-top:1px solid #eee;margin-top:18px;padding-top:16px;}
.base3-authorization-editor .base3-section > select {display:block;max-width:360px;width:100%;margin-top:6px;}
.base3-authorization-editor [role="status"] {padding:6px;}
/* React to the tab's actual width, not only the browser viewport. */
@container (max-width:760px) {
	.base3-audience-row {grid-template-columns:minmax(0,200px) minmax(0,1fr);grid-template-areas:"kind target" ". config" ". remove";}
	.base3-audience-row > [data-audience-remove] {justify-self:start;margin-top:2px;}
	.base3-audience-row[data-kind="expression"] {grid-template-areas:"kind target" ". remove";}
}
@container (max-width:500px) {
	.base3-audience-row {grid-template-columns:minmax(0,1fr);grid-template-areas:"kind" "target" "config" "remove";}
	.base3-audience-row > .base3-audience-kind,.base3-audience-row > .base3-audience-target {max-width:100%;}
	.base3-audience-properties {grid-template-columns:minmax(0,1fr);}
	.base3-audience-row[data-kind="expression"] {grid-template-areas:"kind" "target" "remove";}
}
@media(max-width:800px) {
	.base3-audience-row {
		grid-template-columns:minmax(0,200px) minmax(0,1fr);
		grid-template-areas:"kind target" ". config" ". remove";
	}
	.base3-audience-row > [data-audience-remove] {justify-self:start;margin-top:2px;}
	.base3-authorization-editor .base3-action-row {grid-template-columns:repeat(2,minmax(0,1fr));}
	.base3-audience-row[data-kind="expression"] {grid-template-areas:"kind target" ". remove";}
}
@media(max-width:540px) {
	.base3-authorization-editor {padding:12px;}
	.base3-authorization-editor fieldset {padding:12px;}
	.base3-audience-row {grid-template-columns:minmax(0,1fr);grid-template-areas:"kind" "target" "config" "remove";row-gap:10px;}
	.base3-audience-row > .base3-audience-kind,.base3-audience-row > .base3-audience-target {max-width:100%;}
	.base3-audience-properties {grid-template-columns:minmax(0,1fr);}
	.base3-authorization-editor .base3-action-row {grid-template-columns:minmax(0,1fr);}
	.base3-authorization-editor .base3-group-heading {flex-direction:column;align-items:start;}
	.base3-audience-row[data-kind="expression"] {grid-template-areas:"kind" "target" "remove";}
}
</style>
