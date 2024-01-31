<?php
// Script to be buffered from settings class
$default_value = $defaults[$field][0];
$is_array = is_array($default_value);
$table_id = $setting . '__' . str_replace('][', '_', $field);
?>

<script>
    (function() {
        function renderRowContent(index) {
            <?php if ($is_array) : ?>
                return `<table id="<?= $table_id ?>_${index}">
          <?php foreach (array_keys($default_value) as $key) : ?>
            <tr>
                <th><?= $field ?></th>
                <td><?= $this->input_render($setting, $field . '][${index}][' . $key, $default_value[$key]); ?></td>
            </tr>
          <?php endforeach; ?>
        </table>`;
            <?php else : ?>
                return `<?= $this->input_render($setting, $field . '][${index}', $default_value); ?>`;
            <?php endif; ?>
        }

        function addItem(ev) {
            ev.preventDefault();
            const table = document.getElementById("<?= $table_id ?>")
                .children[0];
            const tr = document.createElement("tr");
            tr.innerHTML =
                "<td>" + renderRowContent(table.children.length) + "</td>";
            table.appendChild(tr);
        }

        function removeItem(ev) {
            ev.preventDefault();
            const table = document.getElementById("<?= $table_id ?>")
                .children[0];
            const rows = table.children;
            table.removeChild(rows[rows.length - 1]);
        }

        const buttons = document.currentScript.previousElementSibling.querySelectorAll("button");
        buttons.forEach((btn) => {
            const callback = btn.dataset.action === "add" ? addItem : removeItem;
            btn.addEventListener("click", callback);
        });
    })();
</script>
