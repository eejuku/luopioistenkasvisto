<?php

defined( 'ABSPATH' ) || exit;
if ( !class_exists( 'WPSE_Custom_Post_Types_Teaser' ) ) {
    class WPSE_Custom_Post_Types_Teaser {
        private static $instance = false;

        public $post_types = array();

        public $allowed_columns = array();

        private function __construct() {
        }

        function init() {
            $this->allowed_columns = array(
                'ID',
                'post_title',
                'post_content',
                'view_post',
                'open_wp_editor',
                'post_status',
                'post_modified',
                'post_date',
                'menu_order',
                'category'
            );
            add_filter( 'vg_sheet_editor/allowed_post_types', array($this, 'allow_all_post_types'), 99 );
            add_action( 'vg_sheet_editor/editor/register_columns', array($this, 'filter_columns_settings'), 99 );
        }

        /**
         * Modify spreadsheet columns settings.
         * 
         * It changes the names and settings of some columns.
         * @param array $spreadsheet_columns
         * @param string $post_type
         * @param bool $exclude_formatted_settings
         * @return array
         */
        function filter_columns_settings( $editor ) {
            // We will allow the post types very late to allow other wpse plugins to register their own post types
            update_option( 'vgse_can_edit_cpt_free', 1 );
            $post_type = $editor->args['provider'];
            if ( !in_array( $post_type, $this->post_types, true ) ) {
                return;
            }
            $spreadsheet_columns = $editor->get_provider_items( $post_type );
            // Increase column width for disabled columns, so the "premium" message fits
            foreach ( $spreadsheet_columns as $key => $column ) {
                if ( !in_array( $key, $this->allowed_columns ) ) {
                    $editor->args['columns']->register_item(
                        $key,
                        $post_type,
                        array(
                            'column_width'      => $column['column_width'] + 80,
                            'is_locked'         => true,
                            'lock_template_key' => 'lock_cell_template_pro',
                        ),
                        true
                    );
                }
            }
        }

        /**
         * Allow all custom post types
         * @param array $allowed_post_types
         * @return array
         */
        function allow_all_post_types( $allowed_post_types ) {
            $current_post_types = ( isset( VGSE()->options['be_post_types'] ) ? VGSE()->options['be_post_types'] : array() );
            if ( empty( $current_post_types ) || !is_array( $current_post_types ) ) {
                $current_post_types = array();
            }
            $new_current_post_types = array();
            foreach ( $current_post_types as $current_post_type ) {
                $new_current_post_types[$current_post_type] = $current_post_type;
            }
            $all_post_types = apply_filters( 'vg_sheet_editor/custom_post_types/get_all_post_types', VGSE()->helpers->get_all_post_types() );
            // We used to exclude post types with own sheet here but we stopped
            // because the bundle already has the list of post types without own sheet
            $allowed = VGSE()->bundles['custom_post_types']['post_types'];
            $count = 1;
            foreach ( $all_post_types as $post_type ) {
                if ( !in_array( $post_type->name, $allowed, true ) || isset( $allowed_post_types[$post_type->name] ) || $count > 5 ) {
                    continue;
                }
                $allowed_post_types[$post_type->name] = $post_type->label;
                $this->post_types[$post_type->name] = $post_type->name;
                $count++;
            }
            $allowed_post_types = wp_parse_args( $allowed_post_types, $new_current_post_types );
            return $allowed_post_types;
        }

        /**
         * Creates or returns an instance of this class.
         */
        static function get_instance() {
            if ( null == WPSE_Custom_Post_Types_Teaser::$instance ) {
                WPSE_Custom_Post_Types_Teaser::$instance = new WPSE_Custom_Post_Types_Teaser();
                WPSE_Custom_Post_Types_Teaser::$instance->init();
            }
            return WPSE_Custom_Post_Types_Teaser::$instance;
        }

        function __set( $name, $value ) {
            $this->{$name} = $value;
        }

        function __get( $name ) {
            return $this->{$name};
        }

    }

}
if ( !function_exists( 'WPSE_Custom_Post_Types_Teaser_Obj' ) ) {
    function WPSE_Custom_Post_Types_Teaser_Obj() {
        return WPSE_Custom_Post_Types_Teaser::get_instance();
    }

}
add_action( 'vg_sheet_editor/initialized', 'WPSE_Custom_Post_Types_Teaser_Obj' );