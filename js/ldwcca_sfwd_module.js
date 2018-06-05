jQuery(document).ready( function() {
	if ( jQuery(".sfwd-courses_settings").length )
		custom_learndash_course_edit_page_javascript();
    
});

function custom_learndash_course_edit_page_javascript() {
    var timeout;

    jQuery( "select[name=sfwd-courses_course_price_type]").change( function() {
        
        var price_type = jQuery("select[name=sfwd-courses_course_price_type]").val();

        if( price_type == "closed") {
            jQuery( "#sfwd-courses_audit_button_product_id" ).show();
            jQuery( "#sfwd-courses_credit_button_product_id" ).show();

            // Don't display regular fields (custom button URL and course price) because we're going to use our new product ID fields and grab the price from the products
            // Use a short timeout because the default sfwd_module is triggering a show() event on these fields
            clearTimeout(timeout);
            wto = setTimeout(function() {
                jQuery( "#sfwd-courses_custom_button_url" ).hide();
                jQuery( "#sfwd-courses_course_price" ).hide();
            }, 10);
            
        } else {
            jQuery( "#sfwd-courses_audit_button_product_id" ).hide();
            jQuery( "#sfwd-courses_credit_button_product_id" ).hide();
        }
        
	});
}