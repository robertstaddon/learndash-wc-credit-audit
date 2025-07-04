<?php
/*
 * Plugin Name: LearnDash WooCommerce Credit/Audit Purchase
 * Plugin URI: http://www.learndash.com
 * Description: Add two buttons to LearnDash courses to add credit/audit products to cart
 * Version: 2.9
 * Author: Abundant Designs
 * Author URI: http://www.abundantdesigns.com
 * Text Domain: learndash_wc_credit_audit
 * Update URI: https://manage.abundantdesigns.com/wp-json/update-server/learndash_wc_credit_audit/
 * 
 * WC tested up to: 3.8
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

        // Filter learndash-course-grid to allow HTML in ribbon text
        add_filter( 'learndash_course_grid_ribbon_text_allow_html', '__return_true' );

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

        if ( $settings_metabox_key === 'learndash-course-enrollment' ) {

            if (
                class_exists( 'LearnDash_Settings_Metabox' ) &&
                !empty( $settings_instance = LearnDash_Settings_Metabox::get_metabox_instance( 'LearnDash_Settings_Metabox_Course_Enrollment' ) )
            ) {
                
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

                // Set up Product Select options
                $select_product_options_default = array(
                    '' => esc_html__( 'Select Product', 'learndash' ),
                );

                $select_product_options = $this->select_a_product();

                if (
                    is_array( $select_product_options )
                    && ! empty( $select_product_options )
                ) {
                    $select_product_options = $select_product_options_default + $select_product_options;
                } else {
                    $select_product_options = $select_product_options_default;
                }

                $select_product_options_default = '';

                // Load the sub_option_fields
                $settings_sub_option_fields['course_price_type_wcca_fields'] = array(
                    'course_price_type_wcca_audit_button_product_id' => array(
                        'name'        => 'course_price_type_wcca_audit_button_product_id',
                        'type'        => 'select',
                        'label'       => esc_html__( 'Audit Button Product', 'learndash_wc_credit_audit' ),
                        'default'     => '',
                        'value'       => $setting_option_values['course_price_type_wcca_audit_button_product_id'],
                        'options'     => $select_product_options,
                        'placeholder' => $select_product_options_default,
                        'help_text'   => sprintf(
                            esc_html_x( 'Select the WooCommerce Product that will need to be purchased to Audit this %s.', 'placeholder: course', 'learndash_wc_credit_audit' ),
                            learndash_get_custom_label( 'course' )
                        ),
                        'rest'        => array(
                            'show_in_rest' => LearnDash_REST_API::enabled(),
                            'rest_args'    => array(
                                'schema' => array(
                                    'type'    => 'integer',
                                    'default' => 0,
                                ),
                            ),
                        ),
                    ),
                    'course_price_type_wcca_credit_button_product_id' => array(
                        'name'        => 'course_price_type_wcca_credit_button_product_id',
                        'type'        => 'select',
                        'label'       => esc_html__( 'Credit Button Product', 'learndash_wc_credit_audit' ),
                        'default'     => '',
                        'value'       => $setting_option_values['course_price_type_wcca_credit_button_product_id'],
                        'options'     => $select_product_options,
                        'placeholder' => $select_product_options_default,
                        'help_text'   => sprintf(
                            esc_html_x( 'Select the WooCommerce Product that will need to be purchased to Credit this %s.', 'placeholder: course', 'learndash_wc_credit_audit' ),
                            learndash_get_custom_label( 'course' )
                        ),
                        'rest'        => array(
                            'show_in_rest' => LearnDash_REST_API::enabled(),
                            'rest_args'    => array(
                                'schema' => array(
                                    'type'    => 'integer',
                                    'default' => 0,
                                ),
                            ),
                        ),
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
     * Hooked from class-ld-settings-metabox-course-enrollment-settings.php
     */
    public function get_post_settings_field_updates( $settings_field_updates, $settings_metabox_key, $settings_screen_id ) {

        if ( $settings_metabox_key === 'learndash-course-enrollment' ) {
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
     * Hooked from class-ld-settings-metabox-course-enrollment-settings.php
     */
    public function filter_saved_fields( $settings_values, $settings_metabox_key ) {

        if ( $settings_metabox_key === 'learndash-course-enrollment' ) {
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
     * Adjust pricing display in LD3 Course infobar price cell when no price is set
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

        if ( class_exists( 'WooCommerce' ) ) {
            $course = $payment_params['post'];
            $course_id = $course->ID;

            $meta                = get_post_meta( $course_id, '_sfwd-courses', true );
            $course_price_type   = @$meta['sfwd-courses_course_price_type'];

            if ( 'wcca' == $course_price_type ) {
                $buttons = $this->get_payment_buttons( $course_id );
                
                if ( !empty( $buttons ) ) {
                    return $buttons;
                } else return $buttons;
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
        
        // Replace "Take this Course" button text with "$10 Audit Course" and "$10 Credit Course"
        $buttons = '';
        if ( $audit_product = wc_get_product( $audit_button_product_id ) ) {
            $button_text = $audit_product->get_price_html() . __('&nbsp;- Audit Course', 'learndash_wc_credit_audit' );
            $button_url = wc_get_cart_url() . "?add-to-cart=" . $audit_product->get_id();
            $buttons .= '<a class="btn-join" href="' . $button_url . '" id="btn-join">'. $button_text .'</a> ';
        }
        
        if ( $credit_product = wc_get_product( $credit_button_product_id ) ) {
            $button_text = $credit_product->get_price_html() . __('&nbsp;- Credit Course', 'learndash_wc_credit_audit' );
            $button_url = wc_get_cart_url() . "?add-to-cart=" . $credit_product->get_id();
            $buttons .= ' <a class="btn-join" href="' . $button_url . '" id="btn-join">'. $button_text .'</a>';
        }

        return $buttons;
    }


    /**
     * Helper function to return price string
     * This price string includes HTML (unlike the default LearnDash string) to support dynamic price and currency switching 
     */
    public function get_price_display( $course_id ) {
        $meta = get_post_meta( $course_id, '_sfwd-courses', true );
        $audit_button_product_id = @$meta['sfwd-courses_course_price_type_wcca_audit_button_product_id'];
        $credit_button_product_id = @$meta['sfwd-courses_course_price_type_wcca_credit_button_product_id'];

        $price_display = '';
        if ( $audit_product = wc_get_product( $audit_button_product_id ) ) {
            $price_display .= $audit_product->get_price_html();
        }
        
        if ( $credit_product = wc_get_product( $credit_button_product_id ) ) {
            if ( !empty( $price_display ) ) 
                $price_display .= " &ndash; ";
            $price_display .= $credit_product->get_price_html();
        }

        return $price_display;
    }

    /**
     * Helper function to return product list
     */
    public function select_a_product( $current_post_type = null ) {
        $opt = array(
            'post_type'   => 'product',
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
        );

        $posts      = get_posts( $opt );
        $post_array = array();

        if ( ! empty( $posts ) ) {
            foreach ( $posts as $p ) {
                $post_array[ $p->ID ] = $p->post_title;
            }
        }

        return $post_array;
    }
}
new learndash_wc_credit_audit();