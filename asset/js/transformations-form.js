$(document).ready(function() {

let selectedTransformation;
const sidebarTransformation = $('<div class="sidebar" id="transformations-sidebar"></div>');
sidebarTransformation.appendTo('#content');

/**
 * Reset transformation name select.
 */
const resetTransformationNameSelect = function(formElement) {
    const transformationNameSelect = formElement.find('.transformations-transformation-name-select');
    const transformationAddButton = formElement.find('.transformations-transformation-add-button');
    transformationAddButton.prop('disabled', true);
    transformationNameSelect.val('');
};

/**
 * Open the transformation edit sidebar.
 */
const openSidebarTransformation = function(formElement, transformation) {
    $.get(formElement.data('transformationEditSidebarUrl'), {
        'transformation_data': transformation.data('transformationData')
    }, function(data) {
        sidebarTransformation.html(data);
        Omeka.openSidebar(sidebarTransformation);
    });
};

// Initiate the transformations elements on load.
$('.transformations-form-element').each(function() {
    const thisFormElement = $(this);
    const transformations = thisFormElement.find('.transformations-transformations');
    // Enable transformation sorting.
    new Sortable(transformations[0], {draggable: '.transformations-transformation', handle: '.sortable-handle'});
    // Add configured transformations to list.
    $.get(thisFormElement.data('transformationListUrl'), function(data) {
        thisFormElement.find('.transformations-transformations').html(data);
        resetTransformationNameSelect(thisFormElement);
    });
});

// Handle transformation name select.
$('.transformations-transformation-name-select').on('change', function(e) {
    const thisSelect = $(this);
    const transformationAddButton = thisSelect.closest('.transformations-form-element').find('.transformations-transformation-add-button');
    transformationAddButton.prop('disabled', ('' === thisSelect.val()) ? true : false);
});

// Handle transformation add button.
$('.transformations-transformation-add-button').on('click', function(e) {
    const thisButton = $(this);
    const formElement = thisButton.closest('.transformations-form-element');
    const transformationNameSelect = formElement.find('.transformations-transformation-name-select');
    $.get(formElement.data('transformationRowUrl'), {
        'transformation_data': {
            'name': transformationNameSelect.val()
        }
    }, function(data) {
        const transformation = $($.parseHTML(data.trim()));
        formElement.find('.transformations-transformations').append(transformation);
        selectedTransformation = transformation;
        openSidebarTransformation(formElement, transformation);
        resetTransformationNameSelect(formElement);
    });
});

// Handle transformation edit button.
$(document).on('click', '.transformations-transformation-edit-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const transformation = thisButton.closest('.transformations-transformation');
    const formElement = thisButton.closest('.transformations-form-element');
    selectedTransformation = transformation;
    openSidebarTransformation(formElement, transformation);
});

// Handle transformation remove button.
$(document).on('click', '.transformations-transformation-remove-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const transformation = thisButton.closest('.transformations-transformation');
    transformation.addClass('delete');
    transformation.find('.sortable-handle, .transformations-transformation-label, .transformations-transformation-remove-button, .transformations-transformation-edit-button').hide();
    transformation.find('.transformations-transformation-restore-button, .transformations-transformation-restore').show();
});

// Handle transformation restore button.
$(document).on('click', '.transformations-transformation-restore-button', function(e) {
    e.preventDefault();
    const thisButton = $(this);
    const transformation = thisButton.closest('.transformations-transformation');
    transformation.removeClass('delete');
    transformation.find('.sortable-handle, .transformations-transformation-label, .transformations-transformation-remove-button, .transformations-transformation-edit-button').show();
    transformation.find('.transformations-transformation-restore-button, .transformations-transformation-restore').hide();
});

// Handle transformation set button.
$(document).on('click', '#transformations-transformation-set-button', function(e) {
    const transformationForm = $('#transformations-transformation-form');
    const formElement = selectedTransformation.closest('.transformations-form-element');
    const transformationData = selectedTransformation.data('transformationData');
    let requiredTransformationIncomplete = false;
    // Note that we set the value of the input's "data-transformation-data-key" attribute
    // as the transformationData key and the input's value as its value.
    transformationForm.find(':input[data-transformation-data-key]').each(function() {
        const thisInput = $(this);
        if (thisInput.prop('required') && '' === thisInput.val()) {
            alert(Omeka.jsTranslate('Required transformation must be completed'));
            requiredTransformationIncomplete = true;
            return false;
        }
        transformationData[thisInput.data('transformationDataKey')] = thisInput.val();
    });
    if (requiredTransformationIncomplete) {
        return;
    }
    selectedTransformation.data(transformationData);
    $.get(formElement.data('transformationRowUrl'), {
        'transformation_data': transformationData
    }, function(data) {
        selectedTransformation.replaceWith(data);
        Omeka.closeSidebar(sidebarTransformation);
    });
});

// Handle form submission.
$(document).on('submit', 'form', function(e) {
    $('.transformations-form-element').each(function() {
        const thisFormElement = $(this);
        const transformations = thisFormElement.find('.transformations-transformation:not(.delete)');
        const transformationsDataInput = thisFormElement.find('.transformations-transformations-data');
        const transformationsData = [];
        transformations.each(function() {
            const thisTransformation = $(this);
            transformationsData.push(thisTransformation.data('transformationData'));
        });
        transformationsDataInput.val(JSON.stringify(transformationsData));
    });
});

});
