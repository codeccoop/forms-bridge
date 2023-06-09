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
    register_setting(
        'wpct_forms_ce',
        'wpct_forms_ce_actions',
        array(
            'type' => 'array',
            'description' => __('Map values from Accions Energétiques select to backend settings', 'wpct-forms-ce'),
            'show_in_rest' => false,
            'default' => array(
                'generacio' => 0,
                'eficiencia' => 0,
                'mobilitat' => 0,
                'formacio' => 0,
                'termica' => 0,
                'compres' => 0,
                'subministrament' => 0,
                'agregacio' => 0
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

    // Accions energètiques
    add_settings_section(
        'wpct_forms_ce_actions_section',
        'Accións Energétiques mapping',
        'wpct_forms_ce_actions_section_render',
        'wpct_forms_ce'
    );
    add_settings_field(
        'generacio',
        __('Generació renovable comunitaria', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_action_render('generacio'),
        'wpct_forms_ce',
        'wpct_forms_ce_actions_section',
    );
    add_settings_field(
        'eficiencia',
        __('Eficiencia energètica', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_action_render('eficiencia'),
        'wpct_forms_ce',
        'wpct_forms_ce_actions_section'
    );
    add_settings_field(
        'mobilitat',
        __('Mobilitat sostenible', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_action_render('mobilitat'),
        'wpct_forms_ce',
        'wpct_forms_ce_actions_section'
    );
    add_settings_field(
        'formacio',
        __('Formació ciutadana', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_action_render('formacio'),
        'wpct_forms_ce',
        'wpct_forms_ce_actions_section'
    );
    add_settings_field(
        'termica',
        __('Energia tèrmica i climatització', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_action_render('termica'),
        'wpct_forms_ce',
        'wpct_forms_ce_actions_section'
    );
    add_settings_field(
        'compres',
        __('Compres col·lectives', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_action_render('compres'),
        'wpct_forms_ce',
        'wpct_forms_ce_actions_section'
    );
    add_settings_field(
        'subministrament',
        __('Subministrament d\'energia 100% renovable', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_action_render('subministrament'),
        'wpct_forms_ce',
        'wpct_forms_ce_actions_section'
    );
    add_settings_field(
        'agregacio',
        __('Agregació i flexibilitat de la demanda', 'wpct-forms-ce'),
        fn () => wpct_forms_ce_action_render('agregacio'),
        'wpct_forms_ce',
        'wpct_forms_ce_actions_section'
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

function wpct_forms_ce_action_render($action)
{
    echo '<input type="text" name="wpct_forms_ce_actions[' . $action . ']" value="' . wpct_forms_ce_option_getter('wpct_forms_ce_actions', $action) . '">';
}

/**
 * Callbacks for the settings sections
 */
function wpct_forms_ce_general_section_render()
{
    echo __('General settings', 'wpct-forms-ce');
}

function wpct_forms_ce_actions_section_render()
{
    echo __('Map values from Accions Energétiques select to backend settings', 'wpct-forms-ce');
}
