<?php
/**
 * Plugin Name: WandTech Order Manager
 * Description: Limited WooCommerce role for managing orders.
 * Version: 1.0
 * Author: Hamxa
 * Text Domain: wandtech-order-manager
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;


// Load text domain for translations
function wandtech_load_textdomain() {
    load_plugin_textdomain('wandtech-order-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'wandtech_load_textdomain');


// Add custom role on plugin activation
function wandtech_add_order_manager_role() {
    add_role(
        'order_manager',
        __('Order Manager', 'wandtech-order-manager'),
        [
            'read' => true,
            'manage_woocommerce' => true,
            'publish_shop_orders' => true,
            'edit_published_shop_orders' => true,
            'read_shop_order' => true,
            'edit_shop_order' => true,
            'edit_shop_orders' => true,
            'edit_others_shop_orders' => true,
            // 'edit_products' => true,
            'delete_shop_order' => false,
            'delete_shop_orders' => false,
            'delete_others_shop_orders' => false,
            'delete_private_shop_orders' => false,
            'delete_published_shop_orders' => false,
        ]
    );
}
register_activation_hook(__FILE__, 'wandtech_add_order_manager_role');


// Remove custom role on plugin deactivation
function wandtech_remove_order_manager_role() {
    remove_role('order_manager');
}
register_deactivation_hook(__FILE__, 'wandtech_remove_order_manager_role');


// Redirect users with 'order_manager' role to WooCommerce orders page after login
add_filter('login_redirect', 'wandtech_custom_order_manager_login_redirect', 10, 3);

function wandtech_custom_order_manager_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('order_manager', $user->roles)) {
            return admin_url('admin.php?page=wc-orders'); // or 'edit.php?post_type=shop_order'
        }
    }
    return $redirect_to;
}


// Redirect 'order_manager' role from dashboard to Orders page
add_action('admin_init', 'wandtech_custom_order_manager_admin_redirect');

function wandtech_custom_order_manager_admin_redirect() {
    if (current_user_can('order_manager') && is_admin()) {
        $current_url = $_SERVER['REQUEST_URI'];
        if (strpos($current_url, '/wp-admin/index.php') !== false || rtrim($current_url, '/') === '/wp-admin') {
            wp_redirect(admin_url('edit.php?post_type=shop_order')); // or 'admin.php?page=wc-orders'
            exit;
        }
    }
}


// Remove unnecessary top-level and submenu pages for 'order_manager' role
add_action('admin_menu', 'wandtech_custom_order_manager_clean_admin_menu', 99);

function wandtech_custom_order_manager_clean_admin_menu() {
    if (!current_user_can('order_manager')) {
        return;
    }

    // Remove main admin menus
    // remove_menu_page('index.php');                // Dashboard
    // remove_menu_page('edit.php');                 // Posts
    // remove_menu_page('upload.php');               // Media
    // remove_menu_page('edit-comments.php');        // Comments
    // remove_menu_page('tools.php');                // Tools
    // remove_menu_page('options-general.php');      // Settings
    // remove_menu_page('users.php');                // Users

    // Remove WooCommerce top-level menu
    remove_menu_page('woocommerce');

    // Remove WooCommerce submenus (everything except orders/products)
    remove_submenu_page('woocommerce', 'wc-admin');                       // Dashboard
    // remove_submenu_page('woocommerce', 'wc-settings');                    // Settings
    // remove_submenu_page('woocommerce', 'coupons-moved');                 // Coupons
    // remove_submenu_page('woocommerce', 'wc-status');                     // Status
    // remove_submenu_page('woocommerce', 'wc-admin&path=/customers');      // Customers
    // remove_submenu_page('woocommerce', 'wc-reports');                    // Reports
    // remove_submenu_page('woocommerce', 'wc-addons');                     // Extensions
    // remove_submenu_page('woocommerce', 'wc-admin&path=/extensions');     // Extensions (again)

    // WooCommerce Marketing menu
    remove_menu_page('woocommerce-marketing');
    remove_submenu_page('woocommerce-marketing', 'admin.php?page=wc-admin&path=/marketing');

    // Remove payment-related menus if any exist
    remove_menu_page('admin.php?page=wc-settings&tab=checkout');
    remove_menu_page('admin.php?page=wc-admin&task=payments');
    remove_menu_page('admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM');

    // Prevent access to Product/s
    // remove_menu_page('edit.php?post_type=product');
    // remove_submenu_page('edit.php?post_type=product', 'post-new.php?post_type=product');
}


// Add custom menu link to Orders page for 'order_manager' role
add_action('admin_menu', 'wandtech_custom_order_manager_add_orders_menu', 9);

function wandtech_custom_order_manager_add_orders_menu() {
    if (!current_user_can('order_manager')) {
        return;
    }

    // Add top-level menu for Orders
    add_menu_page(
        __('Orders', 'woocommerce'),
        __('Orders', 'woocommerce'),
        'manage_woocommerce',
        'admin.php?page=wc-orders',
        '',
        'dashicons-cart',
        6
    );

    // Add Orders submenu under Dashboard
    // add_submenu_page(
    //     'index.php',
    //     __('Orders', 'woocommerce'),
    //     __('Orders', 'woocommerce'),
    //     'manage_woocommerce',
    //     'admin.php?page=wc-orders'
    // );
}


// Remove 'Add New' button on WooCommerce Orders page for 'order_manager' role and etc...
add_action('admin_head', 'wandtech_remove_add_new_order_button_for_order_manager');
function wandtech_remove_add_new_order_button_for_order_manager() {
    $user = wp_get_current_user();

    if (in_array('order_manager', (array) $user->roles)) {
        global $typenow;

        echo '<style>
            .post-type-shop_order .page-title-action {
                display: none !important;
            }
            select[name="action"] option[value="trash"],
            select[name="action2"] option[value="trash"] {
                display: none !important;
            }
            .subsubsub li.trash {
                display: none !important;
            }
            .woocommerce-layout__header {
                display: none !important;
            }
            .order_data_column .wc-customer-user {
                display: none !important;
            }
        </style>';
    }
}


add_action('admin_bar_menu', 'wandtech_customize_admin_bar_for_order_manager', 999);
function wandtech_customize_admin_bar_for_order_manager($wp_admin_bar) {
    if (!current_user_can('order_manager')) {
        return;
    }

    $keep = [
        'site-name',
        'top-secondary',
    ];

    foreach ($wp_admin_bar->get_nodes() as $node) {
        if (!in_array($node->parent, $keep) && !in_array($node->id, $keep)) {
            $wp_admin_bar->remove_node($node->id);
        }
    }
}


add_action('admin_head', 'wandtech_remove_help_tabs_for_order_manager');
function wandtech_remove_help_tabs_for_order_manager() {
    if (!current_user_can('order_manager')) {
        return;
    }

    $screen = get_current_screen();
    if ($screen) {
        $screen->remove_help_tabs();
        $screen->set_help_sidebar('');
    }
}


// Remove "Duplicate" action link from product list for users with 'order_manager' role
function wandtech_remove_duplicate_action_for_order_manager($actions, $post) {
    // Apply only to 'product' post type
    if ($post->post_type === 'product') {
        // Check if current user has 'order_manager' role
        if (current_user_can('order_manager')) {
            // Loop through all actions and remove those related to duplicate
            foreach ($actions as $key => $value) {
                if (stripos($key, 'duplicate') !== false || stripos($value, 'Duplicate') !== false) {
                    unset($actions[$key]);
                }
            }
        }
    }
    return $actions;
}
// add_filter('post_row_actions', 'wandtech_remove_duplicate_action_for_order_manager', 99, 2);
