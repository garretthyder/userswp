<?php
/**
 * Fired during plugin activation
 *
 * @link       http://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Users_WP
 * @subpackage Users_WP/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Users_WP
 * @subpackage Users_WP/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Users_WP_Activator {

    /**
     * @since    1.0.0
     */
    public static function activate() {
        self::generate_pages();
        self::add_default_options();
        self::uwp_create_tables();
        self::uwp_create_default_fields();
    }

    public static function generate_pages() {
        self::uwp_create_page(esc_sql(_x('register', 'page_slug', 'users-wp')), 'uwp_register_page', __('Register', 'users-wp'), '[uwp_register]');
        self::uwp_create_page(esc_sql(_x('login', 'page_slug', 'users-wp')), 'uwp_login_page', __('Login', 'users-wp'), '[uwp_login]');
        self::uwp_create_page(esc_sql(_x('account', 'page_slug', 'users-wp')), 'uwp_account_page', __('Account', 'users-wp'), '[uwp_account]');
        self::uwp_create_page(esc_sql(_x('forgot', 'page_slug', 'users-wp')), 'uwp_forgot_pass_page', __('Forgot Password?', 'users-wp'), '[uwp_forgot]');
        self::uwp_create_page(esc_sql(_x('profile', 'page_slug', 'users-wp')), 'uwp_user_profile_page', __('Profile', 'users-wp'), '[uwp_profile]');
        self::uwp_create_page(esc_sql(_x('users', 'page_slug', 'users-wp')), 'uwp_users_list_page', __('Users', 'users-wp'), '[uwp_users]');
    }

    public static function add_default_options() {
        $forgot_password_subject = __('[#site_name#] - Your new password', 'users-wp');
        $forgot_password_content = __("<p>Dear [#client_name#],<p><p>You requested a new password for [#site_name_url#]</p><p>[#login_details#]</p><p>You can login here: [#login_url#]</p><p>Thank you,<br /><br />[#site_name_url#].</p>",'users-wp');

        $register_success_subject = __('Your Log In Details', 'users-wp');
        $register_success_content = __("<p>Dear [#client_name#],</p><p>You can log in  with the following information:</p><p>[#login_details#]</p><p>You can login here: [#login_url#]</p><p>Thank you,<br /><br />[#site_name_url#].</p>",'users-wp');

        update_option('uwp_forgot_password_subject', $forgot_password_subject);
        update_option('uwp_forgot_password_content', $forgot_password_content);
        update_option('uwp_register_success_subject', $register_success_subject);
        update_option('uwp_register_success_content', $register_success_content);
    }

    public static function uwp_create_page($slug, $option, $page_title = '', $page_content = '', $post_parent = 0, $status = 'publish') {
        global $wpdb, $current_user;

        $option_value = get_option($option);

        if ($option_value > 0) :
            if (get_post($option_value)) :
                // Page exists
                return;
            endif;
        endif;

        $page_found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s LIMIT 1;",
                array($slug)
            )
        );

        if ($page_found) :
            // Page exists
            if (!$option_value) update_option($option, $page_found);
            return;
        endif;

        $page_data = array(
            'post_status' => $status,
            'post_type' => 'page',
            'post_author' => $current_user->ID,
            'post_name' => $slug,
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_parent' => $post_parent,
            'comment_status' => 'closed'
        );
        $page_id = wp_insert_post($page_data);

        add_option($option, $page_id);

    }

    public static function uwp_create_tables()
    {

        global $wpdb;

        $table_name = $wpdb->prefix . 'uwp_custom_fields';

        $wpdb->hide_errors();

        $collate = '';
        if ($wpdb->has_cap('collation')) {
            if (!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if (!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
        }

        /**
         * Include any functions needed for upgrades.
         *
         * @since 1.0.0
         */
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $custom_fields = "CREATE TABLE " . $table_name . " (
							  id int(11) NOT NULL AUTO_INCREMENT,
							  form_type varchar(100) NULL,
							  field_type varchar(255) NOT NULL COMMENT 'text,checkbox,radio,select,textarea',
							  field_type_key varchar(255) NOT NULL,
							  site_title varchar(255) NULL DEFAULT NULL,
							  htmlvar_name varchar(255) NULL DEFAULT NULL,
							  default_value text NULL DEFAULT NULL,
							  sort_order int(11) NOT NULL,
							  option_values text NULL DEFAULT NULL,
							  is_active enum( '0', '1' ) NOT NULL DEFAULT '1',
							  is_default enum( '0', '1' ) NOT NULL DEFAULT '0',
							  is_required enum( '0', '1' ) NOT NULL DEFAULT '0',
							  required_msg varchar(255) NULL DEFAULT NULL,
							  show_in text NULL DEFAULT NULL,
							  extra_fields text NULL DEFAULT NULL,
							  field_icon varchar(255) NULL DEFAULT NULL,
							  css_class varchar(255) NULL DEFAULT NULL,
							  decimal_point varchar( 10 ) NOT NULL,
							  validation_pattern varchar( 255 ) NOT NULL,
							  validation_msg text NULL DEFAULT NULL,
							  PRIMARY KEY  (id)
							  ) $collate";

        $custom_fields = apply_filters('uwp_before_custom_field_table_create', $custom_fields);

        dbDelta($custom_fields);

    }

    public static function uwp_create_default_fields()
    {

        $fields = self::uwp_default_custom_fields();

        $fields = apply_filters('uwp_before_default_custom_fields_saved', $fields);

        foreach ($fields as $field_index => $field) {
            self::uwp_custom_field_save($field);
        }
    }

    public static function uwp_default_custom_fields(){

        $register = self::uwp_default_custom_fields_register();
        $login = self::uwp_default_custom_fields_login();
        $forgot = self::uwp_default_custom_fields_forgot();
        $account = self::uwp_default_custom_fields_account();

        $fields = array_merge($register, $login, $forgot, $account);

        $fields = apply_filters('uwp_default_custom_fields', $fields);

        return $fields;

    }

    public static function uwp_default_custom_fields_register(){

        $fields = array();

        $fields[] = array(
            'form_type' => 'register',
            'field_type' => 'text',
            'site_title' => __('Username', 'users-wp'),
            'htmlvar_name' => 'username',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );

        $fields[] = array(
            'form_type' => 'register',
            'field_type' => 'text',
            'site_title' => __('First Name', 'users-wp'),
            'htmlvar_name' => 'first_name',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1',
            'css_class' => 'uwp-half uwp-half-left',
        );

        $fields[] = array(
            'form_type' => 'register',
            'field_type' => 'text',
            'site_title' => __('Last Name', 'users-wp'),
            'htmlvar_name' => 'last_name',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1',
            'css_class' => 'uwp-half uwp-half-right',
        );

        $fields[] = array(
            'form_type' => 'register',
            'field_type' => 'email',
            'site_title' => __('Email', 'users-wp'),
            'htmlvar_name' => 'email',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );

        $fields[] = array(
            'form_type' => 'register',
            'field_type' => 'password',
            'site_title' => __('Password', 'users-wp'),
            'htmlvar_name' => 'password',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );

        $fields[] = array(
            'form_type' => 'register',
            'field_type' => 'password',
            'site_title' => __('Confirm Password', 'users-wp'),
            'htmlvar_name' => 'confirm_password',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );


        $fields = apply_filters('uwp_default_custom_fields_register', $fields);

        return  $fields;
    }

    public static function uwp_default_custom_fields_login(){

        $fields = array();

        $fields[] = array(
            'form_type' => 'login',
            'field_type' => 'text',
            'site_title' => __('Username', 'users-wp'),
            'htmlvar_name' => 'username',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );

        $fields[] = array(
            'form_type' => 'login',
            'field_type' => 'password',
            'site_title' => __('Password', 'users-wp'),
            'htmlvar_name' => 'password',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );

        $fields = apply_filters('uwp_default_custom_fields_login', $fields);

        return  $fields;
    }

    public static function uwp_default_custom_fields_forgot(){

        $fields = array();

        $fields[] = array(
            'form_type' => 'forgot',
            'field_type' => 'email',
            'site_title' => __('Email', 'users-wp'),
            'htmlvar_name' => 'email',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );

        $fields = apply_filters('uwp_default_custom_fields_forgot', $fields);

        return  $fields;
    }

    public static function uwp_default_custom_fields_account(){

        $fields = array();

        $fields[] = array(
            'form_type' => 'account',
            'field_type' => 'text',
            'site_title' => __('First Name', 'users-wp'),
            'htmlvar_name' => 'first_name',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1',
            'css_class' => 'uwp-half uwp-half-left',
        );

        $fields[] = array(
            'form_type' => 'account',
            'field_type' => 'text',
            'site_title' => __('Last Name', 'users-wp'),
            'htmlvar_name' => 'last_name',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1',
            'css_class' => 'uwp-half uwp-half-right',
        );

        $fields[] = array(
            'form_type' => 'account',
            'field_type' => 'email',
            'site_title' => __('Email', 'users-wp'),
            'htmlvar_name' => 'email',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );

        $fields[] = array(
            'form_type' => 'account',
            'field_type' => 'text',
            'site_title' => __('Password', 'users-wp'),
            'htmlvar_name' => 'password',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );

        $fields[] = array(
            'form_type' => 'account',
            'field_type' => 'text',
            'site_title' => __('Confirm Password', 'users-wp'),
            'htmlvar_name' => 'confirm_password',
            'default_value' => '',
            'option_values' => '',
            'is_default' => '1',
            'is_required' => '1'
        );

        $fields = apply_filters('uwp_default_custom_fields_account', $fields);

        return  $fields;
    }


    public static function uwp_user_field_save($user_id = 0, $key, $value) {

        if (empty($user_id) || empty($key) || empty($value)) {
            return false;
        }

        update_user_meta( $user_id, $key, $value );

        return true;
    }

}