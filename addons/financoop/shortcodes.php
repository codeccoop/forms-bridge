<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

add_shortcode('financoop_campaign', function ($atts) {
    $addon_dir = dirname(__FILE__, 2);
    require_once $addon_dir . '/financoop.php';

    if (!isset($atts['id'])) {
        return "[financoop_campaign id='null']Missing 'id' param[/financoop_campaign]";
    }

    if (!isset($atts['backend'])) {
        return "[financoop_campaign id='{$atts['id']}' backend='null']Missing 'backend' param[/financoop_campaign]";
    }

    $currency = $atts['currency'] ?? '€';

    $campaign = Finan_Coop_Addon::fetch_campaign($atts['id'], [
        'name' => $atts['backend'],
    ]);

    if (is_wp_error($campaign)) {
        $message = $campaign->get_error_message();
        return "[financoop_campaign id='{$atts['id']}']Fetch campaign error message: {$message}[/financoop_campaign]";
    }

    ob_start();
    ?><style>.financoop-campaign-state{background:var(--wp-admin-theme-color);color:white;font-size:0.8em;padding:0 0.6em;width:fit-content;border-radius:1em}
.financoop-campaign-dates{padding-left:0;list-style:none;}
.financoop-progress>p{margin:0}</style>
<article class="financoop-campaign wp-block-group">
    <div class="finanacoop-campaign-header wp-block-group">
        <div class="financoop-campaign-state"><?php echo esc_html(
            $campaign['state']
        ); ?></div>
        <h3 class="wp-block-heading"><?php echo esc_html(
            $campaign['name']
        ); ?></h3>
        <?php if ($campaign['description']): ?>
        <p><?php echo wp_kses_post($campaign['description']); ?></p>
        <?php endif; ?>
    </div>
    <div class="financoop-campaign-content">
        <?php echo financoop_render_campaign_dates($campaign); ?>
        <?php echo financoop_render_campaign_progress($campaign, $currency); ?>
    </div>
</article><?php
$output = ob_get_clean();
return apply_filters(
    'forms_bridge_financoop_campaign_html',
    $output,
    $campaign
);
});

function financoop_render_campaign_progress($campaign, $currency)
{
    $output = financoop_render_source_progress(
        'global',
        $campaign['global_objective'],
        $campaign['progress'],
        $currency
    );

    $sources = ['subscription', 'loan', 'donation'];
    foreach ($sources as $source) {
        if ($campaign["has_{$source}_source"]) {
            $output .= financoop_render_source_progress(
                $source,
                $campaign["source_objective_{$source}"],
                $campaign["progress_{$source}"],
                $currency
            );
        }
    }

    return $output;
}

function financoop_render_source_progress($source, $goal, $value, $currency)
{
    $label = _x($source, 'source progress label', 'forms-bridge');
    $value = round(min(100, (int) $value), 2);
    $goal = (int) $goal;

    if ($goal === 0) {
        return '';
    }

    ob_start();
    ?><div class='financoop-progress' data-source="<?php echo esc_attr($source); ?>">
    <h4 class='wp-block-heading'><?php echo esc_html($label); ?></h4>
    <p><label><?php echo esc_html(
        __('Goal', 'forms-bridge')
    ); ?>: </label><span><?php echo intval($goal); ?> <?php echo esc_html($currency); ?></span></p>
    <div class="financoop-progress-bar">
        <label for='<?php echo esc_attr(
            $source
        ); ?>-progress'><?php echo esc_html(__('Progress', 'forms-bridge')); ?>: <span><?php echo $value; ?> %</span></label></br>
        <progress id='<?php echo esc_attr(
            $source
        ); ?>-progress' value='<?php echo intval($value); ?>' max='100'><?php echo $value; ?> %</progress>
    </div>
</div><?php return ob_get_clean();
}

function financoop_render_campaign_dates($campaign)
{
    if ($campaign['is_permanent']) {
        return '';
    }

    $start = $campaign['start_date'];
    $end = $campaign['end_date'];
    $days_to_start = null;
    $days_to_end = null;

    $start_date = strtotime($start);
    $end_date = null;

    if ($start_date > time()) {
        $days_to_start = ($start_date - time()) / (60 * 60 * 24);
    }

    if ($end) {
        $end_date = strtotime($end);
        $days_to_end = (time() - $end_date) / (60 * 60 * 24);
    }

    if (!$start) {
        return '';
    }

    $output = '<ul class="financoop-campaign-dates">';

    if ($days_to_start) {
        $output .= '<li class="financoop-campaign-date days-to-start">';
        $output .= esc_html(
            __(sprintf('Starts in %s days', (int) $days_to_start))
        );
        $output .= '</li>';
    } else {
        $output .= sprintf(
            '<li class="financoop-campaign-date start-date">%s: %s</li>',
            esc_html(__('Start date', 'forms-bridge')),
            esc_html($start)
        );
    }

    if ($end) {
        $output .= sprintf(
            '<li class="financoop-campaign-date end-date">%s: %s</li>',
            esc_html(__('End date', 'forms-bridge')),
            esc_html($end)
        );

        if (!$days_to_start) {
            $output .= sprintf(
                '<li class="financoop-campaign-date days-to-end">%s: %s</li>',
                esc_html(__('Days to end', 'forms-bridge')),
                (int) $days_to_end
            );
        }
    }

    return $output . '</ul>';
}
