(function () {
    'use strict';

    $(document).ready(function () {
        // Find the input
        const $input = $('#qf_text');

        // Build the select element dynamically (PHP can fill the options)
        const fieldNames = $input.attr('data-mapped-fields').split(' ');
        const $select = $('<select>');
        $select[0].add(new Option());
        fieldNames.forEach(name => $select[0].add(new Option(name)));

        // Insert it above the text input
        $input.before($select);

        // Activate Chosen
        $select.chosen({
            allow_single_deselect: true,
            width: '100%',
            search_contains: true,
            placeholder_text_single: Omeka.jsTranslate('Search and select...'),
        });

        // Disable options already present in the input
        function updateDisabledOptions() {
            const current = $input.val().trim().split(/\s+/).filter(Boolean);
            $select.find('option').each(function () {
                const val = $(this).val();
                $(this).prop('disabled', val && current.includes(val));
            });
            $select.trigger('chosen:updated');
        }

        // Initial state + live update when input is edited manually
        updateDisabledOptions();
        $input.on('input', updateDisabledOptions);

        // Handle selection
        $select.on('change', function (e, params) {
            if (!params || !params.selected) return;
            const val = params.selected.trim();
            const current = $input.val().trim();
            $input.val(current ? current + ' ' + val + ' ' : val + ' ');
            $(this).val('');
            updateDisabledOptions();
        });
    });
})();
