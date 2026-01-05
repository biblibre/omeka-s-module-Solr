/**
 * Dependent selects for Omeka-S Glossaire block
 *
 * - #o:index_id : the select of search index id
 * - #search_field : the select of search field
 * - availableFields is injected from PHP as window.availableFields
 */

(function($) {

    // Registry to avoid initializing the same block twice
    const initializedBlocks = new WeakSet();

    function initializeGlossaireForm() {

        // Find ALL index selects (multiple blocks possible)
        const indexSelects = document.querySelectorAll('[name$="[o\\:data][o\\:index_id]"]');

        indexSelects.forEach(indexSelect => {

            const block = indexSelect.closest('.block'); // real Omeka block wrapper

            if (!block) return;

            // Skip already initialized blocks
            if (initializedBlocks.has(block)) return;
            initializedBlocks.add(block);

            // Find the field select in the same block
            const fieldSelect = block.querySelector('[name$="[o\\:data][search_field]"]');
            const resourceClassFieldSelect = block.querySelector('[name$="[o\\:data][resource_class_field]"]');
            const languageFieldSelect = block.querySelector('[name$="[o\\:data][language_field]"]');
            const dateFieldSelect = block.querySelector('[name$="[o\\:data][date_field]"]');

            if (!fieldSelect) {
                console.warn('Glossaire: field select not found, skipping block.');
                return;
            }

            // -------------------------------
            // Update field options
            // -------------------------------
            function updateFieldOptions(indexId) {
                const facetFields = window.availableFacetFields[indexId] || {};
                const searchFields = window.availableSearchFields[indexId] || {};
                const sortFields = window.availableSortFields[indexId] || {};

                fieldSelect.innerHTML = '';
                resourceClassFieldSelect.innerHTML = '';
                languageFieldSelect.innerHTML = '';
                dateFieldSelect.innerHTML = '';

                const opt2 = document.createElement('option');
                opt2.value = '';
                opt2.textContent = 'None';
                resourceClassFieldSelect.appendChild(opt2);

                const opt3 = document.createElement('option');
                opt3.value = '';
                opt3.textContent = 'None';
                languageFieldSelect.appendChild(opt3);

                const opt4 = document.createElement('option');
                opt4.value = '';
                opt4.textContent = 'None';
                dateFieldSelect.appendChild(opt4);

                Object.entries(facetFields).forEach(([value, label]) => {
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = label;
                    fieldSelect.appendChild(opt);

                    const opt2 = document.createElement('option');
                    opt2.value = value;
                    opt2.textContent = label;
                    resourceClassFieldSelect.appendChild(opt2);

                    const opt3 = document.createElement('option');
                    opt3.value = value;
                    opt3.textContent = label;
                    languageFieldSelect.appendChild(opt3);
                });

                Object.entries(sortFields).forEach(([value, label]) => {
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = label;
                    dateFieldSelect.appendChild(opt);
                });
            }

            // Bind change event ONCE
            indexSelect.addEventListener('change', function () {
                updateFieldOptions(this.value);
            });
        });
    }

    // ----------------------------------------
    // Called when a block is added
    // ----------------------------------------
    $(document).on('o:block-added', function (event) {

        console.log("block-added event");

        initializeGlossaireForm();
    });

    // ----------------------------------------
    // Called on page load (existing blocks)
    // ----------------------------------------
    $(document).ready(function () {

        console.log("document ready event");

        initializeGlossaireForm();
    });

})(jQuery);