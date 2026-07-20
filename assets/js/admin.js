/**
 * ABill Invoice Generator admin interactions.
 */
(function () {
	'use strict';

	var config = window.ABIWIGAdmin || {};

	function ready(callback) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback);
			return;
		}
		callback();
	}

	function numberValue(input) {
		var value = input ? parseFloat(input.value) : 0;
		return Number.isFinite(value) ? value : 0;
	}

	function formatAmount(value) {
		return (Math.round((value + Number.EPSILON) * 100) / 100).toFixed(2);
	}

	function confirmForms() {
		document.addEventListener('submit', function (event) {
			var form = event.target;
			if (!(form instanceof HTMLFormElement)) {
				return;
			}

			if (form.querySelector('.abiwig-delete-button') && !window.confirm(config.confirmDelete || 'Move this invoice to Trash?')) {
				event.preventDefault();
				return;
			}

			if (form.querySelector('.abiwig-send-button') && !window.confirm(config.confirmSend || 'Send this invoice to the customer?')) {
				event.preventDefault();
			}

			var submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
			if (submitButton && !event.defaultPrevented) {
				submitButton.disabled = true;
				submitButton.setAttribute('aria-disabled', 'true');
			}
		});
	}

	function rowIndex(row) {
		var input = row.querySelector('input[name*="[items]"]');
		var match = input && input.name.match(/\[items\]\[(\d+)\]/);
		return match ? parseInt(match[1], 10) : -1;
	}

	function createInput(type, name, options) {
		var input = document.createElement('input');
		input.type = type;
		input.name = name;
		Object.keys(options || {}).forEach(function (key) {
			input.setAttribute(key, options[key]);
		});
		return input;
	}

	function createItemRow(index) {
		var row = document.createElement('tr');
		var base = 'invoice[items][' + index + ']';
		var cells = [
			createInput('text', base + '[name]', { value: '' }),
			createInput('text', base + '[sku]', { value: '' }),
			createInput('number', base + '[quantity]', { value: '1', min: '0', step: '0.001' }),
			createInput('number', base + '[subtotal]', { value: '0.00', step: '0.01' }),
			createInput('number', base + '[tax]', { value: '0.00', step: '0.01' }),
			createInput('number', base + '[total]', { value: '0.00', step: '0.01' }),
			createInput('checkbox', base + '[remove]', { value: '1', 'aria-label': config.removeItem || 'Remove item' })
		];

		cells.forEach(function (input) {
			var cell = document.createElement('td');
			cell.appendChild(input);
			row.appendChild(cell);
		});

		return row;
	}

	function recalculateInvoice(form) {
		var subtotal = 0;
		var tax = 0;
		var itemTotal = 0;

		form.querySelectorAll('.abiwig-items-table tbody tr').forEach(function (row) {
			var remove = row.querySelector('input[name$="[remove]"]');
			if (remove && remove.checked) {
				return;
			}

			var rowSubtotal = numberValue(row.querySelector('input[name$="[subtotal]"]'));
			var rowTax = numberValue(row.querySelector('input[name$="[tax]"]'));
			var rowTotalInput = row.querySelector('input[name$="[total]"]');
			var rowTotal = numberValue(rowTotalInput);

			if (rowTotalInput && rowTotal === 0 && rowSubtotal !== 0) {
				rowTotal = rowSubtotal;
				rowTotalInput.value = formatAmount(rowTotal);
			}

			subtotal += rowSubtotal;
			tax += rowTax;
			itemTotal += rowTotal;
		});

		var discountInput = form.querySelector('input[name="invoice[totals][discount]"]');
		var shippingInput = form.querySelector('input[name="invoice[totals][shipping]"]');
		var subtotalInput = form.querySelector('input[name="invoice[totals][subtotal]"]');
		var taxInput = form.querySelector('input[name="invoice[totals][tax]"]');
		var totalInput = form.querySelector('input[name="invoice[totals][total]"]');
		var discount = numberValue(discountInput);
		var shipping = numberValue(shippingInput);
		var total = itemTotal - discount + shipping + tax;

		if (subtotalInput) {
			subtotalInput.value = formatAmount(subtotal);
		}
		if (taxInput) {
			taxInput.value = formatAmount(tax);
		}
		if (totalInput) {
			totalInput.value = formatAmount(total);
		}
	}

	function itemEditor() {
		var form = document.querySelector('.abiwig-invoice-form');
		var table = form && form.querySelector('.abiwig-items-table');
		if (!form || !table || !table.tBodies.length) {
			return;
		}

		var rows = Array.prototype.slice.call(table.tBodies[0].rows);
		var nextIndex = rows.reduce(function (highest, row) {
			return Math.max(highest, rowIndex(row));
		}, -1) + 1;

		var tools = document.createElement('div');
		tools.className = 'abiwig-item-tools';

		var addButton = document.createElement('button');
		addButton.type = 'button';
		addButton.className = 'button';
		addButton.textContent = config.addItem || 'Add item';

		var recalculateButton = document.createElement('button');
		recalculateButton.type = 'button';
		recalculateButton.className = 'button';
		recalculateButton.textContent = config.recalculate || 'Recalculate totals';

		var message = document.createElement('span');
		message.className = 'abiwig-recalculated-message';
		message.setAttribute('aria-live', 'polite');

		tools.appendChild(addButton);
		tools.appendChild(recalculateButton);
		tools.appendChild(message);
		table.insertAdjacentElement('afterend', tools);

		addButton.addEventListener('click', function () {
			var row = createItemRow(nextIndex++);
			table.tBodies[0].appendChild(row);
			row.querySelector('input').focus();
		});

		recalculateButton.addEventListener('click', function () {
			recalculateInvoice(form);
			message.textContent = config.recalculated || 'Totals recalculated.';
			window.setTimeout(function () {
				message.textContent = '';
			}, 2500);
		});

		table.addEventListener('change', function (event) {
			var target = event.target;
			if (target && target.matches('input[name$="[remove]"]')) {
				target.closest('tr').classList.toggle('abiwig-row-removed', target.checked);
			}
		});
	}

	function logoSelector() {
		var selectButton = document.getElementById('abiwig-select-logo');
		var removeButton = document.getElementById('abiwig-remove-logo');
		var input = document.getElementById('abiwig-business-logo-id');
		var preview = document.getElementById('abiwig-logo-preview');

		if (!selectButton || !input || !preview || !window.wp || !window.wp.media) {
			return;
		}

		var frame;
		selectButton.addEventListener('click', function (event) {
			event.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = window.wp.media({
				title: config.selectLogo || 'Select business logo',
				button: { text: config.useLogo || 'Use this logo' },
				library: { type: 'image' },
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
				input.value = attachment.id;
				preview.innerHTML = '';
				preview.classList.remove('is-empty');
				var image = document.createElement('img');
				image.src = url;
				image.alt = '';
				preview.appendChild(image);
				if (removeButton) {
					removeButton.hidden = false;
				}
			});

			frame.open();
		});

		if (removeButton) {
			removeButton.addEventListener('click', function (event) {
				event.preventDefault();
				input.value = '0';
				preview.innerHTML = '';
				preview.classList.add('is-empty');
				removeButton.hidden = true;
			});
		}
	}

	function printPage() {
		if (!document.body.classList.contains('abiwig-print-page')) {
			return;
		}

		var toolbar = document.createElement('div');
		toolbar.className = 'abiwig-print-toolbar';
		var button = document.createElement('button');
		button.type = 'button';
		button.textContent = config.printNow || 'Print invoice';
		button.addEventListener('click', function () {
			window.print();
		});
		toolbar.appendChild(button);
		document.body.insertBefore(toolbar, document.body.firstChild);

		window.setTimeout(function () {
			window.print();
		}, 350);
	}

	ready(function () {
		confirmForms();
		itemEditor();
		logoSelector();
		printPage();
	});
}());
