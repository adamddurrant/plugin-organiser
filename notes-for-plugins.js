document.addEventListener('DOMContentLoaded', function () {

    // Toggle plugin notes visibility
    const togglePluginNotesButtons = document.querySelectorAll('.toggle-plugin-notes');
    togglePluginNotesButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const textarea = button.nextElementSibling;
            const saveButton = textarea.nextElementSibling;
            const deleteButton = saveButton.nextElementSibling;

            textarea.style.display = (textarea.style.display === 'none' || textarea.style.display === '') ? 'block' : 'none';
            saveButton.style.display = (saveButton.style.display === 'none' || saveButton.style.display === '') ? 'inline-block' : 'none';
            deleteButton.style.display = (deleteButton.style.display === 'none' || deleteButton.style.display === '') ? 'inline-block' : 'none';
        });
    });

    // Save plugin notes via AJAX
    const saveButtons = document.querySelectorAll('.save-plugin-notes');
    saveButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const textarea = button.previousElementSibling;
            const notes_value = textarea.value;
            const notes_key = textarea.dataset.pluginKey;

            fetch(pluginNotes.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_plugin_notes',
                    notes_key: notes_key,
                    notes_value: notes_value,
                    nonce: pluginNotes.nonce
                })
            })
            .then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });

    // Delete plugin notes via AJAX
    const deleteButtons = document.querySelectorAll('.delete-plugin-notes');
    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const pluginNoteContainer = button.closest('.plugin-note-container');
            const notes_key = pluginNoteContainer.querySelector('.plugin-notes').dataset.pluginKey;

            fetch(pluginNotes.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'delete_plugin_notes',
                    notes_key: notes_key,
                    nonce: pluginNotes.nonce
                })
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    console.log('Error deleting note');
                }
            });
        });
    });

    // Change plugin row color via AJAX
    const colorDropdowns = document.querySelectorAll('.plugin-row-color');
    colorDropdowns.forEach(function (dropdown) {
        dropdown.addEventListener('change', function () {
            const selectedColor = dropdown.value;
            const colorKey = dropdown.dataset.pluginKey;
            const parentRow = dropdown.closest('tr'); // Find the parent row

            fetch(pluginNotes.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_plugin_row_color',
                    color_key: colorKey,
                    selected_color: selectedColor,
                    nonce: pluginNotes.nonce
                })
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    // Remove old color classes
                    parentRow.classList.remove('red', 'green', 'purple');
                    // Add new color class if selected
                    if (selectedColor) {
                        parentRow.classList.add(selectedColor);
                    }
                } else {
                    console.log('Error updating row color');
                }
            });
        });
    });

    // Handle color selection
    const colorSelectors = document.querySelectorAll('.plugin-row-color');
    colorSelectors.forEach(function (select) {
        select.addEventListener('change', function () {
            const selectedColor = select.value;
            const colorKey = select.dataset.pluginKey;

            fetch(pluginNotes.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_plugin_row_color',
                    color_key: colorKey,
                    selected_color: selectedColor,
                    nonce: pluginNotes.nonce
                })
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    // Apply the selected color class to the row
                    const pluginRow = select.closest('tr');
                    if (selectedColor) {
                        pluginRow.classList.add(selectedColor);
                    }
                }
            });
        });
    });

});
