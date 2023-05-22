<?php

/**
 * Define menu page.
 */

function wpct_forms_ce_add_admin_menu()
{

    add_options_page(
        'wpct_forms_ce',
        'WPCT Forms CE',
        'manage_options',
        'wpct_forms_ce',
        'wpct_forms_ce_options_page'
    );
}
add_action('admin_menu', 'wpct_forms_ce_add_admin_menu');

/**
 * Define settings.
 * 
 * 
 */

function wpct_forms_ce_register_init()
{
    register_setting('formsCeSettingsPage', 'odoo_forms_settings');
    register_setting('formsCeSettingsPage', 'accions_energetiques_mapping_settings', array(
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
    ));
}
add_action('init', 'wpct_forms_ce_register_init', 50);

function wpct_forms_ce_settings_init()
{

    register_setting('formsCeSettingsPage', 'wpct_forms_ce_settings');
    add_settings_section(
        'wpct_forms_ce_general_section',
        __('General'),
        'wpct_ce_forms_general_section_callback',
        'formsCeSettingsPage'
    );
    /* add_settings_field( */
    /*     'wpct_odoo_connect_notification_receiver', */
    /*     __('Error notification receiver'), */
    /*     'wpct_odoo_connect_notification_receiver_render', */
    /*     'formsCeSettingsPage', */
    /*     'wpct_forms_ce_general_section' */
    /* ); */
    add_settings_field(
        'wpct_odoo_connect_coord_id',
        __('Coord Company ID'),
        'wpct_odoo_connect_coord_id_render',
        'formsCeSettingsPage',
        'wpct_forms_ce_general_section'
    );


    /* add_settings_section( */
    /*     'Dropdown_formsCeSettingsPage_section', */
    /*     __('Map existing forms with the ERP endpoints'), */
    /*     'Odoo_forms_settings_section_callback', */
    /*     'formsCeSettingsPage' */
    /* ); */
    /* add_settings_field( */
    /*     'ce_source_creation_ce_proposal', */
    /*     __('Form - New Energy Community (ce_source_creation_ce_proposal)'), */
    /*     'New_community_select_field_render', */
    /*     'formsCeSettingsPage', */
    /*     'Dropdown_formsCeSettingsPage_section' */
    /* ); */
    /* add_settings_field( */
    /*     'ce_source_future_location_ce_info', */
    /*     __('Form - Interest in zone (ce_source_future_location_ce_info)'), */
    /*     'Zone_interest_select_field_render', */
    /*     'formsCeSettingsPage', */
    /*     'Dropdown_formsCeSettingsPage_section' */
    /* ); */
    /* add_settings_field( */
    /*     'ce_source_general_info', */
    /*     __('Form - General Newsletter (ce_source_general_info)'), */
    /*     'General_newsletter_select_field_render', */
    /*     'formsCeSettingsPage', */
    /*     'Dropdown_formsCeSettingsPage_section' */
    /* ); */
    /* add_settings_field( */
    /*     'ce_source_existing_ce_info', */
    /*     __('Form - Single CE Newsletter (ce_source_existing_ce_info)'), */
    /*     'Single_newsletter_select_field_render', */
    /*     'formsCeSettingsPage', */
    /*     'Dropdown_formsCeSettingsPage_section' */
    /* ); */
    /* add_settings_field( */
    /*     'ce_source_existing_ce_contact', */
    /*     __('Form - Single CE Contact (ce_source_existing_ce_contact)'), */
    /*     'Single_contact_select_field_render', */
    /*     'formsCeSettingsPage', */
    /*     'Dropdown_formsCeSettingsPage_section' */
    /* ); */


    add_settings_section(
        'accionsEnergetiquesMapping_section',
        'Accións Energétiques mapping',
        'accionsEnergetiquesMapping_callback',
        'formsCeSettingsPage'
    );
    add_settings_field(
        'generacio',
        __('Generació renovable comunitaria id', 'text'),
        'generacioRenovableComunitariaMapping_render',
        'formsCeSettingsPage',
        'accionsEnergetiquesMapping_section',

    );
    add_settings_field(
        'eficiencia',
        __('Eficiencia energètica id', 'text'),
        'eficienciaEnergeticaMapping_render',
        'formsCeSettingsPage',
        'accionsEnergetiquesMapping_section'
    );
    add_settings_field(
        'mobilitat',
        __('Mobilitat sostenible id', 'text'),
        'mobilitatSostenibleMapping_render',
        'formsCeSettingsPage',
        'accionsEnergetiquesMapping_section'
    );
    add_settings_field(
        'formacio',
        __('Formació ciutadana id', 'text'),
        'formacioCiutadanaMapping_render',
        'formsCeSettingsPage',
        'accionsEnergetiquesMapping_section'
    );
    add_settings_field(
        'termica',
        __('Energia tèrmica i climatització id', 'text'),
        'energiaTermicaIClimatitzacioMapping_render',
        'formsCeSettingsPage',
        'accionsEnergetiquesMapping_section'
    );
    add_settings_field(
        'compres',
        __('Compres col·lectives id', 'text'),
        'compresCollectivesMapping_render',
        'formsCeSettingsPage',
        'accionsEnergetiquesMapping_section'
    );
    add_settings_field(
        'subministrament',
        __('Subministrament d\'energia 100% renovable id', 'text'),
        'subministramentEnergiaRenovableMapping_render',
        'formsCeSettingsPage',
        'accionsEnergetiquesMapping_section'
    );
    add_settings_field(
        'agregacio',
        __('Agregació i flexibilitat de la demanda id', 'text'),
        'agregacioIFlexibilitatDemandMapping_render',
        'formsCeSettingsPage',
        'accionsEnergetiquesMapping_section'
    );
}

add_action('admin_init', 'wpct_forms_ce_settings_init');


/**
 * Iterate Gravity Forms and extract the form IDs and names
 */
/* function iterate_forms($option_name) */
/* { */
/*     $options = get_option('odoo_forms_settings') ? get_option('odoo_forms_settings') : []; */
/*     $selected = 'disabled '; */
/*     if (!key_exists($option_name, $options) || !$options) { */
/*         $selected .= 'selected'; */
/*         $options[$option_name] = ''; */
/*     } */
/*     $result = GFAPI::get_forms(); */
/*     echo "<select name='odoo_forms_settings[" . $option_name . "]'>"; */
/*     echo '<option value="null" ' . $selected . '>Select a form</option>'; */
/*     foreach ($result as $key => $form) { */
/*         echo '<option value="' . $result[$key]['id'] . '" ' . (($options[$option_name] ? $options[$option_name] : 'null') == $result[$key]['id']  ? 'selected' : '') . '>' . $form['title'] . '</option>'; */
/*     } */
/*     echo "</select>"; */
/* } */

/**
 * Render the forms
 */

/* function New_community_select_field_render() */
/* { */
/*     $option_name = 'ce_source_creation_ce_proposal'; */
/*     iterate_forms($option_name); */
/* } */

/* function Zone_interest_select_field_render() */
/* { */
/*     $option_name = 'ce_source_future_location_ce_info'; */
/*     iterate_forms($option_name); */
/* } */

/* function General_newsletter_select_field_render() */
/* { */
/*     $option_name = 'ce_source_general_info'; */
/*     iterate_forms($option_name); */
/* } */

/* function Single_newsletter_select_field_render() */
/* { */
/*     $option_name = 'ce_source_existing_ce_info'; */
/*     iterate_forms($option_name); */
/* } */

/* function Single_contact_select_field_render() */
/* { */
/*     $option_name = 'ce_source_existing_ce_contact'; */
/*     iterate_forms($option_name); */
/* } */


function wpct_odoo_connect_coord_id_render()
{

    $options = get_option('wpct_forms_ce_settings') ? get_option('wpct_forms_ce_settings') : [];
    key_exists('wpct_odoo_connect_coord_id', $options) ? $coord_id = $options['wpct_odoo_connect_coord_id'] : $coord_id = '-1';
    echo "<input type='text' name='wpct_forms_ce_settings[wpct_odoo_connect_coord_id]' value='" . $coord_id . "'>";
}

function wpct_odoo_connect_notification_receiver_render()
{

    $options = get_option('wpct_forms_ce_settings') ? get_option('wpct_forms_ce_settings') : [];
    key_exists('wpct_odoo_connect_notification_receiver', $options) ? $email_rec = $options['wpct_odoo_connect_notification_receiver'] : $email_rec = '';
    echo "<input type='text' name='wpct_forms_ce_settings[wpct_odoo_connect_notification_receiver]' value='" . $email_rec . "'>";
}

function generacioRenovableComunitariaMapping_render()
{

    $options = get_option('accions_energetiques_mapping_settings') ? get_option('accions_energetiques_mapping_settings') : [];
    key_exists('generacio', $options) ? $generacioRenovableComunitariaMapping = $options['generacio'] : $generacioRenovableComunitariaMapping = '';
    echo "<input type='text' name='accions_energetiques_mapping_settings[generacio]' value='" . $generacioRenovableComunitariaMapping . "'>";
}

function eficienciaEnergeticaMapping_render()
{

    $options = get_option('accions_energetiques_mapping_settings') ? get_option('accions_energetiques_mapping_settings') : [];
    key_exists('eficiencia', $options) ? $eficienciaEnergeticaMapping = $options['eficiencia'] : $eficienciaEnergeticaMapping = '';
    echo "<input type='text' name='accions_energetiques_mapping_settings[eficiencia]' value='" . $eficienciaEnergeticaMapping . "'>";
}

function mobilitatSostenibleMapping_render()
{

    $options = get_option('accions_energetiques_mapping_settings') ? get_option('accions_energetiques_mapping_settings') : [];
    key_exists('mobilitat', $options) ? $mobilitatSostenibleMapping = $options['mobilitat'] : $mobilitatSostenibleMapping = '';
    echo "<input type='text' name='accions_energetiques_mapping_settings[mobilitat]' value='" . $mobilitatSostenibleMapping . "'>";
}

function formacioCiutadanaMapping_render()
{

    $options = get_option('accions_energetiques_mapping_settings') ? get_option('accions_energetiques_mapping_settings') : [];
    key_exists('formacio', $options) ? $formacioCiutadanaMapping = $options['formacio'] : $formacioCiutadanaMapping = '';
    echo "<input type='text' name='accions_energetiques_mapping_settings[formacio]' value='" . $formacioCiutadanaMapping . "'>";
}

function energiaTermicaIClimatitzacioMapping_render()
{

    $options = get_option('accions_energetiques_mapping_settings') ? get_option('accions_energetiques_mapping_settings') : [];
    key_exists('termica', $options) ? $energiaTermicaIClimatitzacioMapping = $options['termica'] : $energiaTermicaIClimatitzacioMapping = '';
    echo "<input type='text' name='accions_energetiques_mapping_settings[termica]' value='" . $energiaTermicaIClimatitzacioMapping . "'>";
}

function compresCollectivesMapping_render()
{

    $options = get_option('accions_energetiques_mapping_settings') ? get_option('accions_energetiques_mapping_settings') : [];
    key_exists('compres', $options) ? $compresCollectivesMapping = $options['compres'] : $compresCollectivesMapping = '';
    echo "<input type='text' name='accions_energetiques_mapping_settings[compres]' value='" . $compresCollectivesMapping . "'>";
}

function subministramentEnergiaRenovableMapping_render()
{

    $options = get_option('accions_energetiques_mapping_settings') ? get_option('accions_energetiques_mapping_settings') : [];
    key_exists('subministrament', $options) ? $subministramentEnergiaRenovableMapping = $options['subministrament'] : $subministramentEnergiaRenovableMapping = '';
    echo "<input type='text' name='accions_energetiques_mapping_settings[subministrament]' value='" . $subministramentEnergiaRenovableMapping . "'>";
}

function agregacioIFlexibilitatDemandMapping_render()
{

    $options = get_option('accions_energetiques_mapping_settings') ? get_option('accions_energetiques_mapping_settings') : [];
    key_exists('agregacio', $options) ? $agregacioIFlexibilitatDemandMapping = $options['agregacio'] : $agregacioIFlexibilitatDemandMapping = '';
    echo "<input type='text' name='accions_energetiques_mapping_settings[agregacio]' value='" . $agregacioIFlexibilitatDemandMapping . "'>";
}

/**
 * Callbacks for the settings sections
 */
function wpct_ce_forms_general_section_callback()
{
    echo __('General settings');
}

function Odoo_forms_settings_section_callback()
{
    echo __('Asign the utm.source field to each form');
}

function accionsEnergetiquesMapping_callback()
{
    echo __('Map values from Accions Energétiques select to backend settings', 'accionsEnergetiquesMapping');
}

/**
 * Paint the settings page
 */
function wpct_forms_ce_options_page()
{
    echo "<form action='options.php' method='post'>";
    echo "<h2>WPCT CE Forms</h2>";
    settings_fields('formsCeSettingsPage');
    do_settings_sections('formsCeSettingsPage');
    submit_button();
    echo "</form>";
}
/**
 * Set default values fot the options 
 */
// register_setting('formsCeSettingsPage', 'wpct_forms_ce_settings', array(
//     'type' => 'array',
//     'description' => 'Map existing forms with the ERP endpoints',
//     'default' => array(
//         'ce_source_creation_ce_proposal' => 0,
//         'ce_source_future_location_ce_info' => 0,
//         'ce_source_general_info' => 0,
//         'ce_source_existing_ce_info' => 0,
//         'ce_source_existing_ce_contact' => 0
//     )
// ));
