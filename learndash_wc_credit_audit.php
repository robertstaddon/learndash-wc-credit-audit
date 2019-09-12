<?php
/*
Plugin Name: LearnDash WooCommerce Credit/Audit Purchase
Plugin URI: http://www.learndash.com
Description: Add two buttons to LearnDash courses to add credit/audit products to cart
Version: 2.0
Author: Abundant Designs
Author URI: http://www.abundantdesigns.com
Text Domain: learndash_wc_credit_audit
*/

class learndash_wc_credit_audit {

	public function __construct() {
        
        // Filter LearnDash settings fields
        add_filter( 'learndash_settings_fields', array( $this, 'learndash_settings_fields' ), 10, 2 );

        // Save new LearnDash settings fields
        add_filter( 'learndash_metabox_save_fields', array( $this, 'get_post_settings_field_updates' ), 10, 3 );

        // Filter final save values
        add_filter( 'learndash_settings_save_values', array( $this, 'filter_saved_fields' ), 10, 2 );
        
        // Adjust LearnDash 3.0 theme course infobar 
        add_action( 'learndash-course-infobar-action-cell-before', array( $this, 'learndash_course_infobar_action_cell_before'), 10, 3 );
        add_filter( 'learndash_no_price_price_label', array( $this, 'learndash_no_price_price_label') );

        // Filter from boss-learndash-functions.php to filter payment button
        add_filter( 'learndash_payment_button', array( $this, 'learndash_payment_button' ), 10, 2);

        $this->settings_fields = array(
            'course_price_type_wcca_audit_button_product_id',
            'course_price_type_wcca_credit_button_product_id',
        );
    }
    
    /**
     * LearnDash settings fields
     */
    public function learndash_settings_fields( $setting_option_fields, $settings_metabox_key ) {

        if ( $settings_metabox_key === 'learndash-course-access-settings' ) {

            if ( class_exists( 'LearnDash_Settings_Metabox' ) ) {
                $settings_instance = LearnDash_Settings_Metabox::get_metabox_instance( 'LearnDash_Settings_Metabox_Course_Access_Settings' );
                
                // Retrieve existing values
                global $post;
                $existing_option_values = learndash_get_setting( $post->ID );

                foreach ( $this->settings_fields as $setting_field ) {
                    if ( isset( $existing_option_values[ $setting_field ] ) ) {
                        $setting_option_values[ $setting_field ] = $existing_option_values[ $setting_field ];
                    } else {
                        $setting_option_values[ $setting_field ] = '';
                    }
                }

                // Load the sub_option_fields
                $settings_sub_option_fields['course_price_type_wcca_fields'] = array(
                    'course_price_type_wcca_audit_button_product_id' => array(
                        'name'    => 'course_price_type_wcca_audit_button_product_id',
                        'label'     => esc_html__( 'Audit Button Product ID', 'learndash_wc_credit_audit' ),
                        'type'    => 'text',
                        'class'   => '-medium',
                        'value'   => $setting_option_values['course_price_type_wcca_audit_button_product_id'],
                        'help_text' => sprintf(
                            esc_html_x( 'Enter the WooCommerce Product ID for the product that will need to be purchased to Audit this %s.', 'placeholder: course', 'learndash_wc_credit_audit' ),
                            learndash_get_custom_label_lower( 'course' )
                        ),
                        'default' => '',
                    ),
                    'course_price_type_wcca_credit_button_product_id' => array(
                        'name'      => 'course_price_type_wcca_credit_button_product_id',
                        'label'     => esc_html__( 'Credit Button Product ID', 'learndash_wc_credit_audit' ),
                        'type'      => 'text',
                        'class'   => '-medium',
                        'value'     => $setting_option_values['course_price_type_wcca_credit_button_product_id'],
                        'help_text' => sprintf(
                            esc_html_x( 'Enter the WooCommerce Product ID for the product that will need to be purchased to Credit this %s.', 'placeholder: course', 'learndash_wc_credit_audit' ),
                            learndash_get_custom_label_lower( 'course' )
                        ),
                        'default'   => '',
                    ),
                );
                
                foreach ( $settings_sub_option_fields['course_price_type_wcca_fields'] as $setting_option_key => &$settings_sub_option_field ) {
                    $settings_sub_option_field = $settings_instance->load_settings_field( $settings_sub_option_field );
                }

                // Set up the option_fields
                $setting_option_fields['course_price_type']['options']['wcca'] = array(
                    'label'               => esc_html__( 'WooCommerce Credit/Audit', 'learndash' ),
                    'description'         => sprintf(
                        // translators: placeholder: course.
                        esc_html_x( 'The %s will be closed unlessed purchased or manually enrolled. Enrollment buttons will be displayed linking to WooCommerce products for the student to either credit or audit.', 'placeholder: course', 'learndash' ),
                        learndash_get_custom_label_lower( 'course' )
                    ),
                    'inline_fields'       => array(
                        'course_price_type_wcca' => $settings_sub_option_fields['course_price_type_wcca_fields'],
                    ),
                    'inner_section_state' => ( 'wcca' === $existing_option_values['course_price_type'] ) ? 'open' : 'closed',
                );
            }
        }

        return $setting_option_fields;
    }
    
    /**
     * LearnDash save settings fields
     * Hooked from class-ld-settings-metabox-course-access-settings.php
     */
    public function get_post_settings_field_updates( $settings_field_updates, $settings_metabox_key, $settings_screen_id ) {

        if ( $settings_metabox_key === 'learndash-course-access-settings' ) {
            $post_values = $_POST[ $settings_metabox_key ];

            foreach( $this->settings_fields as $setting_field ) {
                if ( isset( $post_values[ $setting_field ] ) ) {
                    $post_value = $post_values[ $setting_field ];
                } else {
                    $post_value = '';
                }
                $settings_field_updates[ $setting_field ] = $post_value;
            }
                
        }

        return $settings_field_updates;
    }

    /**
     * Final filter for LearnDash save values
     * Hooked from class-ld-settings-metabox-course-access-settings.php
     */
    public function filter_saved_fields( $settings_values, $settings_metabox_key ) {

        if ( $settings_metabox_key === 'learndash-course-access-settings' ) {
            // Overwrite "course_price" if using "wcca" price type - important for LearnDash Course Grid "course_list_template.php" ribbon display    
            if ( 'wcca' === $settings_values['course_price_type'] ) {
                $settings_values['course_price'] = $this->get_price_display( $_POST['post_ID'] );
            }
        }

        return $settings_values;
    }


    /**
     * Insert buttons into LD3 Course infobar action cell
     */
    public function learndash_course_infobar_action_cell_before( $post_type, $course_id, $user_id ) {
        $course_pricing = learndash_get_course_price( $course_id );

        if ( class_exists( 'WooCommerce' ) && 'wcca' == $course_pricing['type']  ) {
            $buttons = $this->get_payment_buttons( $course_id );
            
            if ( empty( $buttons ) ) {
                echo '<span class="ld-text">' . __( 'This course is currently closed', 'learndash' ) . '</span>';
            } else {
                echo $buttons;
            }

            // Output CSS to hide currency symbol in course price display
            echo '<style>.ld-course-status .ld-course-status-price .ld-currency { display: none; }</style>';
        }   
    }

    /**
     * Adjust pricing display in LD3 Course infobar price cell
     * This is not really needed any more since we're using the 'filter_saved_fields' function to save the course_price 
     */
    public function learndash_no_price_price_label( $default_price_display ) {
        global $post;
        $course_id = $post->ID;
        $course_pricing = learndash_get_course_price( $course_id );

        if ( $course_pricing['type'] == 'wcca' ) {
            $price_display = $this->get_price_display( $course_id );
            
            if ( empty( $price_display ) ) {
                return $default_price_display;
            } else {
                return $price_display;
            }
        }

        return $default_price_display;
    }

    /*
     * Boss Theme: Change payment buttons to Credit and Audit 
     * Customized from boss_edu_payment_buttons() function
     */
    public function learndash_payment_button( $payment_button, $payment_params ) {

        if ( class_exists( 'WooCommerce' ) && 'wcca' == $payment_params['course_price_type'] ) {
            $course = $payment_params['post'];
            $course_id = $course->ID;
            
            $buttons = $this->get_payment_buttons( $course_id );
            
            if ( !empty( $buttons ) ) {
                return $buttons;
            }            
        }
      
        return $payment_button;
    }


    /**
     * Helper function to return payment buttons
     */
    public function get_payment_buttons( $course_id ) {
        $meta = get_post_meta( $course_id, '_sfwd-courses', true );
        $audit_button_product_id = @$meta['sfwd-courses_course_price_type_wcca_audit_button_product_id'];
        $credit_button_product_id = @$meta['sfwd-courses_course_price_type_wcca_credit_button_product_id'];
        
        $price_format = apply_filters( 'learndash_wc_credit_audit_price_display_format', '{currency}{price}' );

        // Replace "Take this Course" button text with "$10 Audit Course" and "$10 Credit Course"
        $buttons = '';
        if ( $audit_product = wc_get_product( $audit_button_product_id ) ) {
            $button_text = str_replace(array( '{currency}', '{price}' ), array( get_woocommerce_currency_symbol(), $audit_product->get_price() ), $price_format );
            $button_text .= __( ' - Audit Course', 'learndash_wc_credit_audit' );
            $button_url = get_permalink( $audit_product->get_id() ) . "?add-to-cart=" . $audit_product->get_id();
            $buttons .= '<a class="btn-join" href="'.$button_url.'" id="btn-join">'. $button_text .'</a> ';
        }
        
        if ( $credit_product = wc_get_product( $credit_button_product_id ) ) {
            $button_text = str_replace(array( '{currency}', '{price}' ), array( get_woocommerce_currency_symbol(), $credit_product->get_price() ), $price_format );
            $button_text .= __( ' - Credit Course', 'learndash_wc_credit_audit' );
            $button_url = get_permalink( $credit_product->get_id() ) . "?add-to-cart=" . $credit_product->get_id();
            $buttons .= ' <a class="btn-join" href="'.$button_url.'" id="btn-join">'. $button_text .'</a>';
        }

        return $buttons;
    }

    /**
     * Helper function to return price string
     */
    public function get_price_display( $course_id ) {
        $meta = get_post_meta( $course_id, '_sfwd-courses', true );
        $audit_button_product_id = @$meta['sfwd-courses_course_price_type_wcca_audit_button_product_id'];
        $credit_button_product_id = @$meta['sfwd-courses_course_price_type_wcca_credit_button_product_id'];

        $price_format = apply_filters( 'learndash_wc_credit_audit_price_display_format', '{currency}{price}' );

        $price_display = '';
        if ( $audit_product = wc_get_product( $audit_button_product_id ) ) {
            $price_display .= str_replace(array( '{currency}', '{price}' ), array( get_woocommerce_currency_symbol(), $audit_product->get_price() ), $price_format );
        }
        
        if ( $credit_product = wc_get_product( $credit_button_product_id ) ) {
            if ( !empty( $price_display ) ) 
                $price_display .= " &ndash; ";
            $price_display .= str_replace(array( '{currency}', '{price}' ), array( get_woocommerce_currency_symbol(), $credit_product->get_price() ), $price_format );
        }

        return $price_display;
    }

}
new learndash_wc_credit_audit();