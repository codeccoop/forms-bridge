<?php

/**
 * Define menu page.
 */
add_action('admin_menu', 'wpct_erp_forms_add_admin_menu');
function wpct_erp_forms_add_admin_menu()
{
    add_options_page(
        'WPCT ERP Forms',
        'WPCT ERP Forms',
        'manage_options',
        'wpct_erp_forms',
        'wpct_erp_forms_render'
    );
}

/**
 * Paint the settings page
 */
function wpct_erp_forms_render()
{
    echo '<div class="wrap">';
    echo '<h1>WPCT ERP Forms</h1>';
    echo '<form action="options.php" method="post">';
    settings_fields('wpct_erp_forms');
    do_settings_sections('wpct_erp_forms');
    submit_button();
    echo '</form>';
    echo '</div>';
}

/**
 * Define settings.
 */
add_action('admin_init', 'wpct_erp_forms_init_settings', 50);
function wpct_erp_forms_init_settings()
{
    register_setting(
        'wpct_erp_forms',
        'wpct_erp_forms_general',
        array(
            'type' => 'array',
            'description' => __('Configuració global dels formularis', 'wpct-erp-forms'),
            'show_in_rest' => false,
            'default' => array(
                'coord_id' => 0,
                'notification_receiver' => 'admin@example.com'
            )
        )
    );

    // Secció general
    add_settings_section(
        'wpct_erp_forms_general_section',
        __('Global', 'wpct-erp-forms'),
        'wpct_erp_forms_general_section_render',
        'wpct_erp_forms'
    );
    add_settings_field(
        'notification_receiver',
        __('Error notification receiver', 'wpct-erp-forms'),
        fn () => wpct_erp_forms_option_render('notification_receiver'),
        'wpct_erp_forms',
        'wpct_erp_forms_general_section'
    );
    add_settings_field(
        'coord_id',
        __('ID de la coordinadora', 'wpct-erp-forms'),
        fn () => wpct_erp_forms_option_render('coord_id'),
        'wpct_erp_forms',
        'wpct_erp_forms_general_section'
    );
}

function wpct_erp_forms_option_getter($group, $option)
{
    $options = get_option($group) ? get_option($group) : [];
    return key_exists($option, $options) ? $options[$option] : '';
}

function wpct_erp_forms_option_render($option)
{
    echo '<input type="text" name="wpct_erp_forms_general[' . $option . ']" value="' . wpct_erp_forms_option_getter('wpct_erp_forms_general', $option) . '">';
}

/**
 * Callbacks for the settings sections
 */
function wpct_erp_forms_general_section_render()
{
    echo __('General settings', 'wpct-erp-forms');
}
