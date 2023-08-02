$(document).ready(function() {

    var highlighting = $('input[name="o:settings[highlighting]"]');
    var highlightingInputsSettings = $('input[name^="o:settings[highlighting_settings_"]');
        
    highlighting.on("change", function() {
        if ($(this).prop('checked')) {
            highlightingInputsSettings.each(function() {
                $(this).closest('.field').show();
            });
        } else {
            highlightingInputsSettings.each(function() {
                $(this).closest('.field').hide();
            });
        }
    });

    highlighting.trigger("change"); 
});