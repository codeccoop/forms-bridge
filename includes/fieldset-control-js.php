// Script to be buffered from settings class
(function () {
	function renderRowContent(key) {
		return `<table id="<?= $setting ?>[<?= $field ?>][${key}]">
                <tr>
                    <th>form_id</th>
                    <td><input type="text" name="<?= $setting ?>[<?= $field ?>][${key}][form_id]" value="<?= $values['endpoints'][0]['form_id'] ?>"></td>
                </tr>
                <tr>
                    <th>endpoint</th>
                    <td><input type="text" name="<?= $setting ?>[<?= $field ?>][${key}][endpoint]" value="<?= $values['endpoints'][0]['endpoint'] ?>"></td>
                </tr>
            </table>`;
	}

	function addItem(ev) {
		ev.preventDefault();
		const table = document.getElementById("<?= $setting ?>[<?= $field ?>]")
			.children[0];
		const tr = document.createElement("tr");
		tr.innerHTML =
			"<td>" + renderRowContent(table.children.length) + "</td>";
		table.appendChild(tr);
	}

	function removeItem(ev) {
		ev.preventDefault();
		const table = document.getElementById("<?= $setting ?>[<?= $field ?>]")
			.children[0];
		const rows = table.children;
		table.removeChild(rows[rows.length - 1]);
	}

	const buttons =
		document.currentScript.previousElementSibling.querySelectorAll(
			"button"
		);
	buttons.forEach((btn) => {
		const callback = btn.dataset.action === "add" ? addItem : removeItem;
		btn.addEventListener("click", callback);
	});
})();
