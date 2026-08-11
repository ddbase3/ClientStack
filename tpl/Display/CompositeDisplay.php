<?php
	$compositeId = (string) $this->_['compositeId'];
	$columns = (int) $this->_['columns'];
	$items = (array) $this->_['items'];
	$emptyMessage = (string) $this->_['emptyMessage'];
?>
<div
	id="<?php echo htmlspecialchars($compositeId); ?>"
	class="base3-composite-display"
	style="--base3-composite-columns: <?php echo $columns; ?>;"
>
	<?php if(count($items) === 0): ?>
		<div class="base3-composite-display-empty">
			<?php echo htmlspecialchars($emptyMessage); ?>
		</div>
	<?php else: ?>
		<?php foreach($items as $item): ?>
			<section
				class="base3-composite-display-item"
				data-base3-composite-item="<?php echo htmlspecialchars((string) $item['id']); ?>"
				style="--base3-composite-span: <?php echo (int) $item['span']; ?>;"
			>
				<?php if(trim((string) ($item['title'] ?? '')) !== ''): ?>
					<h2 class="base3-composite-display-item-title"><?php echo htmlspecialchars((string) $item['title']); ?></h2>
				<?php endif; ?>
				<?php echo (string) $item['content']; ?>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

<style>
#<?php echo $compositeId; ?>.base3-composite-display {
	display: grid;
	grid-template-columns: repeat(var(--base3-composite-columns), minmax(0, 1fr));
	gap: 18px;
	width: 100%;
	max-width: 100%;
}

#<?php echo $compositeId; ?> .base3-composite-display-item {
	grid-column: span var(--base3-composite-span);
	min-width: 0;
}

#<?php echo $compositeId; ?> .base3-composite-display-item-title {
	margin: 0 0 10px;
	font-size: 18px;
	font-weight: 600;
}

#<?php echo $compositeId; ?> .base3-composite-display-empty {
	grid-column: 1 / -1;
	padding: 18px;
	border: 1px solid #d6d6d6;
	border-radius: 6px;
	background: #f4f5f6;
	color: #666666;
}

@media (max-width: 760px) {
	#<?php echo $compositeId; ?>.base3-composite-display {
		grid-template-columns: minmax(0, 1fr);
		gap: 14px;
	}

	#<?php echo $compositeId; ?> .base3-composite-display-item {
		grid-column: 1 / -1;
	}
}
</style>
