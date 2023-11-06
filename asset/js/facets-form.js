$(document).ready(function() {

let selectedFacet;
const sidebarFacet = $('<div class="sidebar" id="facets-sidebar"></div>');
sidebarFacet.appendTo('#content');

/**
 * Reset facet name input.
 */
const resetFacetNameInput = function(formElement) {
    const facetNameInput = formElement.find('.facets-facet-name');
    const facetAddButton = formElement.find('.facets-facet-add-button');
    facetAddButton.prop('disabled', true);
    facetNameInput.val('');
};

/**
 * Open the facet edit sidebar.
 */
const openSidebarFacet = function(formElement, facet) {
    $.get(formElement.data('facetEditSidebarUrl'), {
        'facet_data': facet.data('facetData')
    }, function(data) {
        sidebarFacet.html(data);
        Omeka.openSidebar(sidebarFacet);
    });
};

// Initiate the facets elements on load.
$('.facets-form-element').each(function() {
    const thisFormElement = $(this);
    const facets = thisFormElement.find('.facets-facets');
    // Add configured facets to list.
    $.get(thisFormElement.data('facetListUrl'), {}, function(data) {
        thisFormElement.find('.facets-facets').html(data);
        resetFacetNameInput(thisFormElement);
    });
});

// Handle facet name input.
$('.facets-facet-name').on('input', function(e) {
    const facetAddButton = $(this).closest('.facets-form-element').find('.facets-facet-add-button');
    facetAddButton.prop('disabled', ('' === $(this).val()) ? true : false);
});
$('.facets-facet-name').on('keydown', function(e) {
    if (e.code === 'Enter') {
        e.preventDefault();
        const facetAddButton = $(this).closest('.facets-form-element').find('.facets-facet-add-button');
        facetAddButton.trigger('click');
    }
});

// Handle facet add button.
$('.facets-facet-add-button').on('click', function(e) {
    const thisButton = $(this);
    const formElement = thisButton.closest('.facets-form-element');
    const facetNameInput = formElement.find('.facets-facet-name');
    $.get(formElement.data('facetRowUrl'), {
        'facet_data': {
            'name': facetNameInput.val()
        }
    }, function(data) {
        const facet = $($.parseHTML(data.trim()));
        formElement.find('.facets-facets').append(facet);
        selectedFacet = facet;
        openSidebarFacet(formElement, facet);
        resetFacetNameInput(formElement);
    });
});

// Handle facet edit button.
$(document).on('click', '.facets-facet-edit-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const facet = thisButton.closest('.facets-facet');
    const formElement = thisButton.closest('.facets-form-element');
    selectedFacet = facet;
    openSidebarFacet(formElement, facet);
});

// Handle facet remove button.
$(document).on('click', '.facets-facet-remove-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const facet = thisButton.closest('.facets-facet');
    facet.addClass('delete');
    facet.find('.facets-facet-label, .facets-facet-remove-button, .facets-facet-edit-button').hide();
    facet.find('.facets-facet-restore-button, .facets-facet-restore').show();
});

// Handle facet restore button.
$(document).on('click', '.facets-facet-restore-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const facet = thisButton.closest('.facets-facet');
    facet.removeClass('delete');
    facet.find('.facets-facet-label, .facets-facet-remove-button, .facets-facet-edit-button').show();
    facet.find('.facets-facet-restore-button, .facets-facet-restore').hide();
});

// Handle facet set button.
$(document).on('click', '#facets-facet-set-button', function(e) {
    const facetForm = $('#facets-facet-form');
    const formElement = selectedFacet.closest('.facets-form-element');
    const facetData = selectedFacet.data('facetData');
    let requiredFieldIncomplete = false;
    // Note that we set the value of the input's "data-facet-data-key" attribute
    // as the facetData key and the input's value as its value.
    facetForm.find(':input[data-facet-data-key]').each(function() {
        const thisInput = $(this);
        if (thisInput.prop('required') && '' === thisInput.val()) {
            alert(Omeka.jsTranslate('Required field must be completed'));
            requiredFieldIncomplete = true;
            return false;
        }
        facetData[thisInput.data('facetDataKey')] = thisInput.val();
    });
    if (requiredFieldIncomplete) {
        return;
    }
    selectedFacet.data(facetData);
    $.get(formElement.data('facetRowUrl'), {
        'facet_data': facetData
    }, function(data) {
        selectedFacet.replaceWith(data);
        Omeka.closeSidebar(sidebarFacet);
    });
});

// Handle form submission.
$(document).on('submit', 'form', function(e) {
    $('.facets-form-element').each(function() {
        const thisFormElement = $(this);
        const facets = thisFormElement.find('.facets-facet:not(.delete)');
        const facetsDataInput = thisFormElement.find('.facets-facets-data');
        const facetsData = [];
        facets.each(function() {
            const thisFacet = $(this);
            facetsData.push(thisFacet.data('facetData'));
        });
        facetsDataInput.val(JSON.stringify(facetsData));
    });
});

});
