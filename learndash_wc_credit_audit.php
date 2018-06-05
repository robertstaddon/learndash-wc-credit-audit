<?php
/*
Plugin Name: LearnDash WooCommerce Credit/Audit Purchase
Plugin URI: http://www.learndash.com
Description: Add two buttons to LearnDash courses to add credit/audit products to cart
Version: 1.0
Author: Abundant Designs
Author URI: http://www.abundantdesigns.com
Text Domain: learndash_wc_credit_audit
*/

class learndash_wc_credit_audit {
	public function __construct() {
        
        // Enqueue WordPress admin JavaScript
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        
        // Filter from class-ld-lms.php to filter $post_args used to create the custom post types and everything associated with them.
        add_filter( 'learndash_post_args', array( $this, 'learndash_post_args' ) );
        
        // Filter from boss-learndash-functions.php to filter a closed course payment button
        add_filter( 'learndash_payment_closed_button', array( $this, 'learndash_payment_closed_button' ), 10, 2);
        
	}
    
    /*
     * Change payment buttons to Credit and Audit 
     * Customized from boss_edu_payment_buttons() function
     */
    function learndash_payment_closed_button( $custom_button, $payment_params ) {
        if ( class_exists( 'WooCommerce' ) ) {
            $course = $payment_params['post'];
            $course_id = $course->ID;
            
            
            $meta = get_post_meta( $course_id, '_sfwd-courses', true );
            $audit_button_product_id = @$meta['sfwd-courses_audit_button_product_id'];
            $credit_button_product_id = @$meta['sfwd-courses_credit_button_product_id'];
            
            // Replace "Take this Course" button text with "$10 Audit Course" and "$10 Credit Course"
            $new_button = '';
            if ( $audit_product = wc_get_product( $audit_button_product_id ) ) {
                $button_text = sprintf( __( '%1$s%2$s - Audit Course', 'learndash_wc_credit_audit' ), get_woocommerce_currency_symbol(), $audit_product->get_price() );
                $button_url = get_permalink( $audit_product->get_id() ) . "?add-to-cart=" . $audit_product->get_id();
                $new_button .= '<a class="btn-join" href="'.$button_url.'" id="btn-join">'. $button_text .'</a> ';
            }
            
            if ( $credit_product = wc_get_product( $credit_button_product_id ) ) {
                $button_text = sprintf( __( '%1$s%2$s - Credit Course', 'learndash_wc_credit_audit' ), get_woocommerce_currency_symbol(), $credit_product->get_price() );
                $button_url = get_permalink( $credit_product->get_id() ) . "?add-to-cart=" . $credit_product->get_id();
                $new_button .= ' <a class="btn-join" href="'.$button_url.'" id="btn-join">'. $button_text .'</a>';
            }
            
            if ( !empty( $new_button) ) {
                return $new_button;
            }            
        }
      
        return $custom_button;
    }


    /*
     * Enqueue WordPress admin JavaScript
     */
    public function admin_enqueue_scripts( $hook ) {
        if ( class_exists( 'WooCommerce' ) ) {
            // Add custom script for LearnDash course admin show/hide
            if ('post.php' !== $hook) {
                return;
            }
            wp_enqueue_script('custom_sfwd_module.', plugin_dir_url(__FILE__) . '/js/ldwcca_sfwd_module.js');
        }
    }


    /*
     * Add extra arguments to LearnDash
     */
    public function learndash_post_args( $post_args ) {
        if ( class_exists( 'WooCommerce' ) ) {
            // Add 'Audit Button Product ID'
            $insert_args = array(
                'audit_button_product_id' => array(
                    'name' => esc_html__( 'Audit Button Product ID', 'learndash_wc_credit_audit' ),
                    'type' => 'number',
                    'help_text' => __( 'Enter the WooCommerce Product ID for the product that will need to be purchased to Audit this course', 'learndash_wc_credit_audit' ),
                    'show_in_rest' => true
                ),
                'credit_button_product_id' => array(
                    'name' => esc_html__( 'Credit Button Product ID', 'learndash_wc_credit_audit' ),
                    'type' => 'number',
                    'help_text' => __( 'Enter the WooCommerce Product ID for the product that will need to be purchased to Credit this course', 'learndash_wc_credit_audit' ),
                    'show_in_rest' => true,
                )
            );
            
            $offset = 3;
            $post_args['sfwd-courses']['fields'] = array_slice( $post_args['sfwd-courses']['fields'], 0, $offset, true) + $insert_args + array_slice( $post_args['sfwd-courses']['fields'], $offset, NULL, true);
        }
        
        return $post_args;
    }
}
new learndash_wc_credit_audit();