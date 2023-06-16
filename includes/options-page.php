<?php

/**
 * Define menu page.
 */
add_action('admin_menu', 'wpct_forms_ce_add_admin_menu');
function wpct_forms_ce_add_admin_menu()
{
    add_options_page(
        'WPCT Forms CE',
        'WPCT Forms CE',
        'manage_options',
        'wpct_forms_ce',
        'wpct_forms_ce_render'
    );
}

/**
 * Paint the settings page
 */
function wpct_forms_ce_render()
{
    echo '<div class="wrap">';
    echo '<h1>WPCT CE Forms</h1>';
    echo '<form action="options.php" method="post">';
    settings_fields('wpct_forms_ce');
    do_settings_sections('wpct_forms_ce');
    submit_button();
    echo '</form>';
    echo '</div>';
}

/**
 * Define settings.
 */
add_action('admin_init', 'wpct_forms_ce_init_settings', 50);
function wpct_forms_ce_init_settings()
{
    register_setting(
        'wpct_forms_ce',
        'wpct_forms_ce_general',
        array(
            'type' => 'array',
            'description' => __('Configuració global dels formularis', 'wpct-forms-ce'),
            'show_in_rest' => false,
            'default' => array(
                'coord_id' => 0,
                'notification_receiver' => 'admin@example.com'
            )
        )
    );

    // Secció general
    add_settings_section(
        'wpct_forms_ce_general_section',
        __('Global', 'wpct-forms-ce'),
        'wpct_forms_ce_general_section_render',
        'wpct_forms_ce'
    );
    add_settings_field(
        'notification_receiver',
        __('Error notification receiver', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_option_render('notification_receiver'),
        'wpct_forms_ce',
        'wpct_forms_ce_general_section'
    );
    add_settings_field(
        'coord_id',
        __('ID de la coordinadora', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_option_render('coord_id'),
        'wpct_forms_ce',
        'wpct_forms_ce_general_section'
    );
}

function wpct_forms_ce_option_getter($group, $option)
{
    $options = get_option($group) ? get_option($group) : [];
    return key_exists($option, $options) ? $options[$option] : '';
}

function wpct_forms_ce_option_render($option)
{
    echo '<input type="text" name="wpct_forms_ce_general[' . $option . ']" value="' . wpct_forms_ce_option_getter('wpct_forms_ce_general', $option) . '">';
}

/**
 * Callbacks for the settings sections
 */
function wpct_forms_ce_general_section_render()
{
    echo __('General settings', 'wpct-forms-ce');
}
