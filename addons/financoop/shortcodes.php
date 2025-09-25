<?php

namespace FORMS_BRIDGE;

use Error;
use Exception;

if (!defined('ABSPATH')) {
    exit();
}

add_shortcode('financoop_campaign', function ($atts) {
    $addon_dir = dirname(__FILE__);
    require_once $addon_dir . '/class-financoop-addon.php';

    if (!isset($atts['id'])) {
        return forms_bridge_financoop_shortcode_error(
            'Missing "id" param',
            $atts
        );
    }

    if (!isset($atts['backend'])) {
        return forms_bridge_financoop_shortcode_error(
            'Missing "backend" param',
            $atts
        );
    }

    if (!isset($atts['sources'])) {
        $atts['sources'] = ['global'];
    } else {
        $valid_sources = ['global', 'subscription', 'donation', 'loan'];
        $sources = array_map(
            'strtolower',
            array_map('trim', explode(',', $atts['sources']))
        );

        $atts['sources'] = [];
        foreach ($sources as $source) {
            if (in_array($source, $valid_sources, true)) {
                $atts['sources'][] = $source;
            }
        }
    }

    $atts['currency'] = $atts['currency'] ?? '€';

    try {
        $bridge = new Finan_Coop_Form_Bridge(
            [
                'name' => '__financoop-' . time(),
                'endpoint' => "/api/campaign/{$atts['id']}",
                'backend' => $atts['backend'],
                'method' => 'GET',
            ],
            'financoop'
        );

        $response = $bridge->submit();
        if (is_wp_error($response)) {
            $message = $response->get_error_message();
            return forms_bridge_financoop_shortcode_error(
                "Fetch campaign error message: {$message}",
                $atts
            );
        }

        $campaign = $response['data'];
    } catch (Error | Exception $e) {
        return forms_bridge_financoop_shortcode_error(
            "Fetch campaign error message: {$e->getMessage()}",
            $atts
        );
    }

    ob_start();
    ?><article class="financoop-campaign wp-block-group">
    <div class="financoop-campaign-header">
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
        <?php echo financoop_render_campaign_progress($campaign, $atts); ?>
        <?php echo financoop_render_campaign_dates($campaign); ?>
    </div>
</article><?php
$output = shortcode_unautop(ob_get_clean());

return apply_filters(
    'forms_bridge_financoop_campaign_html',
    $output,
    $campaign,
    $atts
);
});

function financoop_render_campaign_progress($campaign, $atts)
{
    $output = '';

    if (in_array('global', $atts['sources'], true)) {
        $output = financoop_render_source_progress(
            $campaign,
            'global',
            $campaign['global_objective'],
            $campaign['progress'],
            $atts['currency']
        );
    }

    foreach ($atts['sources'] as $source) {
        if ($campaign["has_{$source}_source"]) {
            $output .= financoop_render_source_progress(
                $campaign,
                $source,
                $campaign["source_objective_{$source}"],
                $campaign["progress_{$source}"],
                $atts['currency']
            );
        }
    }

    return apply_filters(
        'forms_bridge_financoop_progress_html',
        $output,
        $campaign,
        $atts
    );
}

function financoop_render_source_progress(
    $campaign,
    $source,
    $goal,
    $progress,
    $currency
) {
    $label = _x($source, 'financoop source progress label', 'forms-bridge');

    $amount = round($progress * 0.01 * $goal, 2);
    $progress = round(min(100, $progress), 2);
    $goal = (int) $goal;

    if ($goal === 0) {
        return '';
    }

    ob_start();
    ?><div class="financoop-campaign-progress" data-source="<?php echo esc_attr(
    $source
); ?>">
    <h4 class="wp-block-heading"><?php echo esc_html($label); ?></h4>

	<p class="financoop-campaign-progress-item fraction">
		<span class="label"><?php echo esc_html(
      _x('Achieved', 'financoop campaign widget', 'forms-bridge')
  ); ?>: </span><span class="value"><?php echo number_format(
    $amount,
    2,
    ',',
    '.'
); ?> / <span class="value"><?php echo number_format(
     $goal,
     2,
     ',',
     '.'
 ); ?> <span class="unit"><?php echo esc_html($currency); ?></span></span>
	</p>

	<p class="financoop-campaign-progress-item goal">
		<span class="label"><?php echo esc_html(
      _x('Goal', 'financoop campaign widget', 'forms-bridge')
  ); ?>: </span><span class="value"><?php echo number_format(
    $goal,
    2,
    ',',
    '.'
); ?> <span class="unit"><?php echo esc_html($currency); ?></span></span>
	</p>

	<p class="financoop-campaign-progress-item amount">
		<span class="label"><?php echo esc_html(
      _x('Received', 'financoop campaign widget', 'forms-bridge')
  ); ?>: </span><span class="value"><?php echo number_format(
    $amount,
    2,
    ',',
    '.'
); ?> <span class="unit"><?php echo esc_html($currency); ?></span></span>
	</p>

	<p class="financoop-campaign-progress-item percentage">
        <span class="label"><?php echo esc_html(
            _x('Progress', 'financoop campaign widget', 'forms-bridge')
        ); ?>: </span><span class="value"><?php echo number_format(
    $progress,
    2,
    ',',
    '.'
); ?> <span class="unit">%</span></span>
	</p>

    <div class="financoop-campaign-progress-item progress-bar">
        <progress value='<?php echo intval(
            $progress
        ); ?>' max='100'><?php echo $progress; ?> %</progress>
    </div>
</div>
	<?php
 $output = shortcode_unautop(ob_get_clean());

 return apply_filters(
     'forms_bridge_financoop_source_progress_html',
     $output,
     $campaign,
     [
         'source' => $source,
         'label' => $label,
         'progress' => $progress,
         'amount' => $amount,
         'goal' => $goal,
     ]
 );
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

    $now = time();

    if ($start_date > $now) {
        $days_to_start = ($start_date - $now) / (60 * 60 * 24);
    }

    if ($end) {
        $end_date = strtotime($end);
        $days_to_end = ($end_date - $now) / (60 * 60 * 24);
    }

    if (!$start) {
        return '';
    }

    $output = '<div class="financoop-campaign-dates">';

    if ($days_to_start) {
        $output .= sprintf(
            '<p class="financoop-campaign-date days-to-start"><span class="label">%s: </span><span class="value">%s</span></p>',
            esc_html(
                _x('Days to start', 'financoop campaign widget', 'forms-bridge')
            ),
            (int) $days_to_start
        );
    }

    if ($end) {
        $output .= sprintf(
            '<p class="financoop-campaign-date end-date"><span class="label">%s: </span><span class="value">%s</span></p>',
            esc_html(
                _x('End date', 'financoop campaign widget', 'forms-bridge')
            ),
            esc_html(strftime('%d/%m/%Y', $end_date))
        );

        if (!$days_to_start && $days_to_end > 0) {
            $output .= sprintf(
                '<p class="financoop-campaign-date days-to-end"><span class="label">%s: </span><span class="value">%s</span></p>',
                esc_html(
                    _x(
                        'Days to end',
                        'financoop campaign widget',
                        'forms-bridge'
                    )
                ),
                (int) $days_to_end
            );
        }
    }

    $output .= '</div>';

    return apply_filters(
        'forms_bridge_financoop_dates_html',
        $output,
        $campaign,
        [
            'start' => $start,
            'end' => $end,
            'days_to_start' => $days_to_start,
            'days_to_end' => $days_to_end,
        ]
    );
}

function forms_bridge_financoop_shortcode_error($message, $atts)
{
    $atts = implode(
        ' ',
        array_reduce(
            ['id', 'backend', 'sources', 'currency'],
            function ($handle, $attr) use ($atts) {
                if (isset($atts[$attr])) {
                    $value = implode(',', (array) $atts[$attr]);
                    $handle[] = "{$attr}='{$value}'";
                }

                return $handle;
            },
            []
        )
    );

    return "[financoop_campaign {$atts}]{$message}[/financoop_campaign]";
}
