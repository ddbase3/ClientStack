<?php
	$id = (string) $this->_['id'];
	$name = (string) $this->_['name'];
	$value = (string) $this->_['value'];
	$className = (string) $this->_['className'];
	$rows = (int) $this->_['rows'];
	$placeholder = (string) $this->_['placeholder'];
	$spellcheck = (bool) $this->_['spellcheck'];
	$readonly = (bool) $this->_['readonly'];
	$disabled = (bool) $this->_['disabled'];
	$ariaLabel = (string) $this->_['ariaLabel'];
?>
<textarea
	id="<?php echo htmlspecialchars($id, ENT_QUOTES); ?>"
	class="<?php echo htmlspecialchars($className, ENT_QUOTES); ?>"
	rows="<?php echo $rows; ?>"
	spellcheck="<?php echo $spellcheck ? 'true' : 'false'; ?>"
	data-base3-rich-text-editor="textarea"
	data-base3-rich-text-editor-control
	<?php if($name !== ''): ?>name="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"<?php endif; ?>
	<?php if($placeholder !== ''): ?>placeholder="<?php echo htmlspecialchars($placeholder, ENT_QUOTES); ?>"<?php endif; ?>
	<?php if($ariaLabel !== ''): ?>aria-label="<?php echo htmlspecialchars($ariaLabel, ENT_QUOTES); ?>"<?php endif; ?>
	<?php if($readonly): ?>readonly<?php endif; ?>
	<?php if($disabled): ?>disabled<?php endif; ?>
><?php echo htmlspecialchars($value, ENT_QUOTES); ?></textarea>
