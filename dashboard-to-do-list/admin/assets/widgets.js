(function ($) {
	'use strict';

	$(function () {

		// Color picker
		if ($.fn.wpColorPicker) {
			$('.ardtdw-color-field').wpColorPicker();
		}

		var $form = $('#ardtdw-form');
		if (!$form.length) return;

		var $checklist       = $('#ardtdw-checklist');
		var $hiddenTA        = $('#ardtdw-textarea');
		var $rawTA           = $('#ardtdw-raw-textarea');
		var $rawWrap         = $('#ardtdw-raw-wrap');
		var $rawToggle       = $('#ardtdw-raw-toggle');
		var $newInput        = $('#ardtdw-new-item');
		var $addBtn          = $('#ardtdw-add-btn');
		var $settingsToggle  = $('#ardtdw-settings-toggle');
		var $settingsPanel   = $('#ardtdw-settings-panel');
		var $settingsArrow   = $settingsToggle.find('.ardtdw-settings-arrow');
		var rawOpen          = false;

		// Parse / build
		function parseLines(content) {
			return (content || '').split('\n').reduce(function (acc, line) {
				line = line.trim();
				if (!line) return acc;
				var done = /\s+done\s*$/i.test(line);
				var text = done ? line.replace(/\s+done\s*$/i, '').trim() : line;
				acc.push({ text: text, done: done });
				return acc;
			}, []);
		}

		function buildContent() {
			var lines = [];
			$checklist.find('.ardtdw-item').each(function () {
				var text = $(this).find('.ardtdw-item-text').val().trim();
				var done = $(this).find('.ardtdw-done-cb').prop('checked');
				if (text) lines.push(done ? text + ' DONE' : text);
			});
			return lines.join('\n');
		}

		function makeRow(item) {
			var $li   = $('<li class="ardtdw-item">');
			var $done = $('<input type="checkbox" class="ardtdw-done-cb" title="Mark as done">').prop('checked', item.done);
			var $text = $('<input type="text" class="ardtdw-item-text">').val(item.text);
			var $del  = $('<button type="button" class="ardtdw-delete-btn" title="Remove item">&#10005;</button>');
			return $li.append($done).append($text).append($del);
		}

		function renderList(content) {
			$checklist.empty();
			var items = parseLines(content);
			if (!items.length) {
				$checklist.append('<li class="ardtdw-empty">' + 'No items yet. Add one below.' + '</li>');
				return;
			}
			$.each(items, function (i, item) {
				$checklist.append(makeRow(item));
			});
		}

		renderList($hiddenTA.val());

		// AJAX: silently save items only

		function ajaxSaveItems() {
			var content = buildContent();
			$hiddenTA.val(content);
			$.post(ajaxurl, {
				action:            'ardtdw_update_items',
				'ardtdw-textarea': content,
				nonce:             ardtdwAdmin.nonce
			});
		}

		// Add item

		function addItem() {
			var val = $newInput.val().trim();
			if (!val) return;
			$checklist.find('.ardtdw-empty').remove();
			$checklist.append(makeRow({ text: val, done: false }));
			$newInput.val('');
			ajaxSaveItems();
		}

		$addBtn.on('click', addItem);
		$newInput.on('keydown', function (e) {
			if (e.key === 'Enter') { e.preventDefault(); addItem(); }
		});

		// Delete item

		$checklist.on('click', '.ardtdw-delete-btn', function () {
			if (!window.confirm('Are you sure you want to delete this item?')) return;
			$(this).closest('.ardtdw-item').remove();
			if (!$checklist.find('.ardtdw-item').length) {
				$checklist.append('<li class="ardtdw-empty">' + 'No items yet. Add one below.' + '</li>');
			}
		});

		// Settings toggle

		if ($settingsToggle.length) {
			var settingsOpen = true;
			try { settingsOpen = localStorage.getItem('ardtdw_settings') !== '0'; } catch (e) {}

			function setSettings(open) {
				settingsOpen = open;
				$settingsPanel.toggle(open);
				$settingsArrow.css('transform', open ? '' : 'rotate(180deg)');
				try { localStorage.setItem('ardtdw_settings', open ? '1' : '0'); } catch (e) {}
			}

			setSettings(settingsOpen);

			$settingsToggle.on('click', function () {
				setSettings(!settingsOpen);
			});
		}

		// Bulk edit toggle

		$rawToggle.on('click', function () {
			rawOpen = !rawOpen;
			if (rawOpen) {
				$rawTA.val(buildContent());
				$rawWrap.slideDown(150);
				$rawToggle.text('Apply & close');
			} else {
				renderList($rawTA.val());
				$rawWrap.slideUp(150);
				$rawToggle.text('Bulk Edit');
				ajaxSaveItems();
			}
		});

		// Form submit

		$form.on('submit', function () {
			if (rawOpen) renderList($rawTA.val());
			$hiddenTA.val(buildContent());
		});

	});

})(jQuery);
