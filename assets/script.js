jQuery(document).ready(function () {
    let $color_inputs = jQuery('input.popup-colorpicker');
    $color_inputs.each(function () {
        let $input = jQuery(this);
        let $pickerId = "#" + jQuery(this).attr('id') + "picker";
        jQuery($pickerId).hide();

        jQuery($pickerId).farbtastic($input);


        jQuery($input).click(function () {
            jQuery($pickerId).slideToggle();
        });
    });
});