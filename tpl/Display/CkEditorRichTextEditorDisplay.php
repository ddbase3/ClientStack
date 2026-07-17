<?php
	$id = (string) $this->_['id'];
	$name = (string) $this->_['name'];
	$value = (string) $this->_['value'];
	$className = (string) $this->_['className'];
	$rows = (int) $this->_['rows'];
	$minimumHeight = (int) $this->_['minimumHeight'];
	$placeholder = (string) $this->_['placeholder'];
	$spellcheck = (bool) $this->_['spellcheck'];
	$readonly = (bool) $this->_['readonly'];
	$disabled = (bool) $this->_['disabled'];
	$ariaLabel = (string) $this->_['ariaLabel'];
	$ckeditorModuleUrl = (string) $this->_['ckeditorModuleUrl'];
	$ckeditorCssUrl = (string) $this->_['ckeditorCssUrl'];
	$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($ckeditorCssUrl, ENT_QUOTES); ?>" />
<style>
	.base3-rich-text-editor-ckeditor-wrapper {
		box-sizing: border-box;
		width: 100%;
		max-width: 100%;
		min-width: 0;
	}
	.base3-rich-text-editor-ckeditor-wrapper .ck.ck-editor,
	.base3-rich-text-editor-ckeditor-wrapper .ck-editor__top,
	.base3-rich-text-editor-ckeditor-wrapper .ck-sticky-panel,
	.base3-rich-text-editor-ckeditor-wrapper .ck-sticky-panel__content,
	.base3-rich-text-editor-ckeditor-wrapper .ck.ck-toolbar,
	.base3-rich-text-editor-ckeditor-wrapper .ck-editor__main {
		box-sizing: border-box;
		width: 100%;
		max-width: 100%;
		min-width: 0;
	}
	.base3-rich-text-editor-ckeditor-wrapper .ck.ck-toolbar > .ck-toolbar__items {
		min-width: 0;
		flex-wrap: wrap;
	}
	.base3-rich-text-editor-ckeditor-wrapper .ck-editor__editable_inline {
		box-sizing: border-box;
		max-width: 100%;
		min-width: 0;
		min-height: var(--base3-rich-text-editor-min-height, 288px);
	}
	.base3-rich-text-editor-ckeditor-wrapper .ck-content img {
		max-width: 100%;
		height: auto;
	}
</style>
<div
	class="base3-rich-text-editor-ckeditor-wrapper"
	data-base3-rich-text-editor-wrapper="ckeditor"
	style="--base3-rich-text-editor-min-height: <?php echo $minimumHeight; ?>px;"
>
	<textarea
		id="<?php echo htmlspecialchars($id, ENT_QUOTES); ?>"
		class="<?php echo htmlspecialchars($className, ENT_QUOTES); ?>"
		rows="<?php echo $rows; ?>"
		spellcheck="<?php echo $spellcheck ? 'true' : 'false'; ?>"
		data-base3-rich-text-editor="ckeditor"
		data-base3-rich-text-editor-control
		<?php if($name !== ''): ?>name="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"<?php endif; ?>
		<?php if($placeholder !== ''): ?>placeholder="<?php echo htmlspecialchars($placeholder, ENT_QUOTES); ?>"<?php endif; ?>
		<?php if($ariaLabel !== ''): ?>aria-label="<?php echo htmlspecialchars($ariaLabel, ENT_QUOTES); ?>"<?php endif; ?>
		<?php if($readonly): ?>readonly<?php endif; ?>
		<?php if($disabled): ?>disabled<?php endif; ?>
	><?php echo htmlspecialchars($value, ENT_QUOTES); ?></textarea>
</div>
<script type="module">
	(async () => {
		const editorId = <?php echo json_encode($id, $jsonFlags); ?>;
		const moduleUrl = <?php echo json_encode($ckeditorModuleUrl, $jsonFlags); ?>;
		const placeholder = <?php echo json_encode($placeholder, $jsonFlags); ?>;
		const ariaLabel = <?php echo json_encode($ariaLabel, $jsonFlags); ?>;
		const readOnly = <?php echo ($readonly || $disabled) ? 'true' : 'false'; ?>;
		const textarea = document.getElementById(editorId);

		if(!textarea || textarea.base3RichTextEditor) {
			return;
		}

		let editor = null;
		let destroyed = false;
		let currentValue = String(textarea.value || '');

		function updateTextarea(value, notify = false) {
			currentValue = value === null || value === undefined ? '' : String(value);
			textarea.value = currentValue;

			if(notify) {
				textarea.dispatchEvent(new Event('input', { bubbles: true }));
			}
		}

		const adapter = {
			getValue() {
				if(editor) {
					updateTextarea(editor.getData());
				}

				return currentValue;
			},
			setValue(value) {
				updateTextarea(value);

				if(editor && editor.getData() !== currentValue) {
					editor.setData(currentValue);
				}
			},
			focus() {
				if(editor) {
					editor.editing.view.focus();
					return;
				}

				textarea.focus();
			},
			async destroy() {
				destroyed = true;
				textarea.base3RichTextEditor = null;

				if(!editor) {
					return;
				}

				const activeEditor = editor;
				editor = null;
				await activeEditor.destroy();
			}
		};

		textarea.base3RichTextEditor = adapter;

		try {
			const {
				Alignment,
				AutoLink,
				Autoformat,
				BlockQuote,
				Bold,
				ClassicEditor,
				Code,
				Essentials,
				FindAndReplace,
				FontBackgroundColor,
				FontColor,
				FontFamily,
				FontSize,
				GeneralHtmlSupport,
				Heading,
				HorizontalLine,
				Image,
				ImageCaption,
				ImageInsert,
				ImageInsertViaUrl,
				ImageResize,
				ImageStyle,
				ImageToolbar,
				Indent,
				IndentBlock,
				Italic,
				Link,
				List,
				Paragraph,
				PasteFromOffice,
				RemoveFormat,
				SourceEditing,
				SpecialCharacters,
				SpecialCharactersEssentials,
				Strikethrough,
				Table,
				TableCellProperties,
				TableProperties,
				TableToolbar,
				Underline
			} = await import(moduleUrl);

			const createdEditor = await ClassicEditor.create(textarea, {
				licenseKey: 'GPL',
				plugins: [
					Essentials,
					Autoformat,
					Paragraph,
					Heading,
					Bold,
					Italic,
					Underline,
					Strikethrough,
					Code,
					RemoveFormat,
					FontFamily,
					FontSize,
					FontColor,
					FontBackgroundColor,
					Link,
					AutoLink,
					List,
					Alignment,
					Indent,
					IndentBlock,
					BlockQuote,
					HorizontalLine,
					Table,
					TableToolbar,
					TableProperties,
					TableCellProperties,
					Image,
					ImageToolbar,
					ImageCaption,
					ImageStyle,
					ImageResize,
					ImageInsert,
					ImageInsertViaUrl,
					GeneralHtmlSupport,
					SourceEditing,
					PasteFromOffice,
					FindAndReplace,
					SpecialCharacters,
					SpecialCharactersEssentials
				],
				toolbar: {
					items: [
						'undo',
						'redo',
						'|',
						'heading',
						'|',
						'fontFamily',
						'fontSize',
						'fontColor',
						'fontBackgroundColor',
						'|',
						'bold',
						'italic',
						'underline',
						'strikethrough',
						'code',
						'removeFormat',
						'-',
						'link',
						'insertImage',
						'insertTable',
						'blockQuote',
						'horizontalLine',
						'specialCharacters',
						'|',
						'bulletedList',
						'numberedList',
						'outdent',
						'indent',
						'alignment',
						'|',
						'findAndReplace',
						'sourceEditing'
					],
					shouldNotGroupWhenFull: true
				},
				htmlSupport: {
					allow: [
						{
							name: /.*/,
							attributes: true,
							classes: true,
							styles: true
						}
					]
				},
				link: {
					addTargetToExternalLinks: true,
					defaultProtocol: 'https://'
				},
				image: {
					toolbar: [
						'imageTextAlternative',
						'toggleImageCaption',
						'imageStyle:inline',
						'imageStyle:wrapText',
						'imageStyle:breakText',
						'resizeImage'
					]
				},
				table: {
					contentToolbar: [
						'tableColumn',
						'tableRow',
						'mergeTableCells',
						'tableProperties',
						'tableCellProperties'
					]
				},
				placeholder
			});

			if(destroyed || !textarea.isConnected) {
				await createdEditor.destroy();
				return;
			}

			editor = createdEditor;

			if(editor.getData() !== currentValue) {
				editor.setData(currentValue);
			}
			updateTextarea(editor.getData());

			if(readOnly) {
				editor.enableReadOnlyMode('base3-rich-text-editor');
			}

			const editableElement = editor.ui.view.editable.element;
			if(editableElement && ariaLabel !== '') {
				editableElement.setAttribute('aria-label', ariaLabel);
			}

			if(editableElement) {
				editableElement.addEventListener('keydown', (event) => {
					const isSubmitShortcut = (event.ctrlKey || event.metaKey) && event.key === 'Enter';
					if(!isSubmitShortcut && event.key !== 'Escape') {
						return;
					}

					const forwardedEvent = new KeyboardEvent('keydown', {
						key: event.key,
						code: event.code,
						ctrlKey: event.ctrlKey,
						metaKey: event.metaKey,
						shiftKey: event.shiftKey,
						altKey: event.altKey,
						bubbles: true,
						cancelable: true
					});

					if(!textarea.dispatchEvent(forwardedEvent)) {
						event.preventDefault();
					}
				});
			}

			editor.model.document.on('change:data', () => {
				updateTextarea(editor.getData(), true);
			});
		}
		catch(error) {
			textarea.base3RichTextEditor = adapter;
			textarea.hidden = false;
			textarea.style.display = '';
			console.error('Unable to initialize CKEditor rich text editor.', error);
		}
	})();
</script>
