<?php
/**
 * Plugin Name: Berlin Sleep Apnea Questionnaire
 * Description: Multi-step Berlin Questionnaire with scoring, results, and GoHighLevel webhook integration.
 * Version:     1.0.0
 * Author:      Riverwalk Dentistry
 * GitHub Plugin URI: adelsherif8/berlin-sleep-questionnaire
 */

defined('ABSPATH') || exit;

define('BSQ_VERSION', '1.0.0');
define('BSQ_DIR',     plugin_dir_path(__FILE__));
define('BSQ_URL',     plugin_dir_url(__FILE__));

/* ─── Admin Settings ──────────────────────────────────── */

add_action('admin_menu', function () {
    add_options_page(
        'Sleep Questionnaire',
        'Sleep Questionnaire',
        'manage_options',
        'bsq-settings',
        'bsq_render_settings'
    );
});

function bsq_render_settings() {
    if (isset($_POST['bsq_nonce']) && wp_verify_nonce($_POST['bsq_nonce'], 'bsq_save')) {
        update_option('bsq_ghl_webhook', esc_url_raw($_POST['bsq_ghl_webhook'] ?? ''));
        update_option('bsq_booking_url', sanitize_text_field($_POST['bsq_booking_url'] ?? '/booking-method'));
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    $webhook     = get_option('bsq_ghl_webhook', '');
    $booking_url = get_option('bsq_booking_url', '/booking-method');
    ?>
    <div class="wrap">
        <h1>Sleep Questionnaire Settings</h1>
        <form method="post">
            <?php wp_nonce_field('bsq_save', 'bsq_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="bsq_ghl_webhook">GHL Webhook URL</label></th>
                    <td>
                        <input type="url" id="bsq_ghl_webhook" name="bsq_ghl_webhook"
                               value="<?php echo esc_attr($webhook); ?>" class="regular-text"
                               placeholder="https://services.leadconnectorhq.com/hooks/..." />
                        <p class="description">GoHighLevel workflow webhook — receives all answers + score.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="bsq_booking_url">Booking Page URL</label></th>
                    <td>
                        <input type="text" id="bsq_booking_url" name="bsq_booking_url"
                               value="<?php echo esc_attr($booking_url); ?>" class="regular-text"
                               placeholder="/booking-method" />
                        <p class="description">URL the "Book Appointment" button links to on the results screen.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

/* ─── Shortcode ───────────────────────────────────────── */

add_shortcode('berlin_questionnaire', function () {
    wp_enqueue_style('bsq',  BSQ_URL . 'assets/bsq-style.css',  [], BSQ_VERSION);
    wp_enqueue_script('bsq', BSQ_URL . 'assets/bsq-script.js', [], BSQ_VERSION, true);
    wp_localize_script('bsq', 'BSQ', [
        'ajax_url'    => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce('bsq_submit'),
        'booking_url' => get_option('bsq_booking_url', '/booking-method'),
    ]);
    ob_start();
    include BSQ_DIR . 'templates/questionnaire.php';
    return ob_get_clean();
});

/* ─── AJAX ────────────────────────────────────────────── */

add_action('wp_ajax_bsq_submit',        'bsq_handle_submit');
add_action('wp_ajax_nopriv_bsq_submit', 'bsq_handle_submit');

function bsq_handle_submit() {
    check_ajax_referer('bsq_submit', 'nonce');

    $raw = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
    $d   = array_map('sanitize_text_field', $raw);

    $score = bsq_score($d);

    $webhook = get_option('bsq_ghl_webhook', '');
    if ($webhook) {
        bsq_send_ghl($webhook, $d, $score);
    }

    wp_send_json_success($score);
}

/* ─── Scoring ─────────────────────────────────────────── */

function bsq_score(array $d): array {
    // Category 1 — Snoring
    $c1 = 0;
    if (($d['q2'] ?? '') === 'yes')                                                $c1 += 1;
    if (in_array($d['q3'] ?? '', ['louder_than_talking', 'very_loud'], true))      $c1 += 1;
    if (in_array($d['q4'] ?? '', ['nearly_every_day',    '3_4_times'], true))      $c1 += 1;
    if (($d['q5'] ?? '') === 'yes')                                                $c1 += 1;
    if (in_array($d['q6'] ?? '', ['nearly_every_day',    '3_4_times'], true))      $c1 += 2;

    // Category 2 — Fatigue
    $c2 = 0;
    if (in_array($d['q7'] ?? '', ['nearly_every_day', '3_4_times'], true))         $c2 += 1;
    if (in_array($d['q8'] ?? '', ['nearly_every_day', '3_4_times'], true))         $c2 += 1;
    if (($d['q9'] ?? '') === 'yes')                                                $c2 += 1;

    // BMI (imperial: lbs / in²  × 703)
    $bmi = 0.0;
    $wt  = floatval($d['weight_lbs'] ?? 0);
    $ht  = floatval($d['height_in']  ?? 0);
    if ($wt > 0 && $ht > 0) {
        $bmi = ($wt / ($ht * $ht)) * 703;
    }

    // Category 3 — BP / BMI
    $c3_pos = (($d['q10'] ?? '') === 'yes' || $bmi > 30);

    $pos = ($c1 >= 2 ? 1 : 0) + ($c2 >= 2 ? 1 : 0) + ($c3_pos ? 1 : 0);

    return [
        'cat1_score'    => $c1,
        'cat1_positive' => $c1 >= 2,
        'cat2_score'    => $c2,
        'cat2_positive' => $c2 >= 2,
        'cat3_positive' => $c3_pos,
        'bmi'           => round($bmi, 1),
        'pos_categories'=> $pos,
        'risk_level'    => $pos >= 2 ? 'High Risk' : 'Low Risk',
    ];
}

/* ─── GHL Webhook ─────────────────────────────────────── */

function bsq_send_ghl(string $webhook, array $d, array $score): void {
    $parts = explode(' ', trim($d['full_name'] ?? ''), 2);

    $payload = [
        // Contact
        'firstName'                  => $parts[0] ?? '',
        'lastName'                   => $parts[1] ?? '',
        'email'                      => $d['email']    ?? '',
        'phone'                      => $d['phone']    ?? '',
        // Demographics
        'age'                        => $d['age']      ?? '',
        'gender'                     => $d['gender']   ?? '',
        'height'                     => $d['height_display'] ?? '',
        'weight_lbs'                 => $d['weight_lbs'] ?? '',
        'bmi'                        => $score['bmi'],
        // Berlin answers
        'berlin_q2_snore'            => $d['q2'] ?? '',
        'berlin_q3_snore_volume'     => $d['q3'] ?? '',
        'berlin_q4_snore_frequency'  => $d['q4'] ?? '',
        'berlin_q5_bothers_others'   => $d['q5'] ?? '',
        'berlin_q6_stop_breathing'   => $d['q6'] ?? '',
        'berlin_q7_tired_after_sleep'=> $d['q7'] ?? '',
        'berlin_q8_tired_during_day' => $d['q8'] ?? '',
        'berlin_q9_fall_asleep_driving' => $d['q9'] ?? '',
        'berlin_q10_blood_pressure'  => $d['q10'] ?? '',
        // Score
        'berlin_cat1_score'          => $score['cat1_score'],
        'berlin_cat1_positive'       => $score['cat1_positive'] ? 'Yes' : 'No',
        'berlin_cat2_score'          => $score['cat2_score'],
        'berlin_cat2_positive'       => $score['cat2_positive'] ? 'Yes' : 'No',
        'berlin_cat3_positive'       => $score['cat3_positive'] ? 'Yes' : 'No',
        'berlin_risk_level'          => $score['risk_level'],
        'berlin_positive_categories' => $score['pos_categories'],
        'source'                     => 'Berlin Sleep Questionnaire',
        'tags'                       => [
            'sleep-apnea-screening',
            $score['risk_level'] === 'High Risk' ? 'berlin-high-risk' : 'berlin-low-risk',
        ],
    ];

    wp_remote_post($webhook, [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => wp_json_encode($payload),
        'timeout' => 15,
        'blocking'=> false,
    ]);
}

/* ─── GitHub Auto-Updater ─────────────────────────────── */

define('BSQ_GITHUB_REPO', 'adelsherif8/berlin-sleep-questionnaire');

add_filter('pre_set_site_transient_update_plugins', 'bsq_check_for_update');
function bsq_check_for_update($transient) {
    if (empty($transient->checked)) return $transient;

    $release = bsq_get_github_release();
    if (!$release || empty($release->tag_name)) return $transient;

    $new_version = ltrim($release->tag_name, 'v');
    if (!version_compare($new_version, BSQ_VERSION, '>')) return $transient;

    $download_url = '';
    if (!empty($release->assets)) {
        foreach ($release->assets as $asset) {
            if (substr($asset->name, -4) === '.zip') {
                $download_url = $asset->browser_download_url;
                break;
            }
        }
    }

    if ($download_url) {
        $transient->response[plugin_basename(__FILE__)] = (object)[
            'slug'        => dirname(plugin_basename(__FILE__)),
            'plugin'      => plugin_basename(__FILE__),
            'new_version' => $new_version,
            'url'         => $release->html_url,
            'package'     => $download_url,
        ];
    }

    return $transient;
}

add_filter('plugins_api', 'bsq_plugin_info', 20, 3);
function bsq_plugin_info($result, $action, $args) {
    if ($action !== 'plugin_information') return $result;
    if ($args->slug !== dirname(plugin_basename(__FILE__))) return $result;

    $release = bsq_get_github_release();
    if (!$release) return $result;

    return (object)[
        'name'          => 'Berlin Sleep Apnea Questionnaire',
        'slug'          => dirname(plugin_basename(__FILE__)),
        'version'       => ltrim($release->tag_name ?? '', 'v'),
        'author'        => 'Riverwalk Dentistry',
        'homepage'      => 'https://github.com/' . BSQ_GITHUB_REPO,
        'sections'      => ['description' => $release->body ?? ''],
        'download_link' => !empty($release->assets[0]) ? $release->assets[0]->browser_download_url : '',
    ];
}

// Auto-apply updates for this plugin without requiring admin to click Update
add_filter('auto_update_plugin', function ($update, $item) {
    return (isset($item->plugin) && $item->plugin === plugin_basename(__FILE__)) ? true : $update;
}, 10, 2);

function bsq_get_github_release() {
    $cached = get_transient('bsq_github_release');
    if ($cached !== false) return $cached;

    $response = wp_remote_get('https://api.github.com/repos/' . BSQ_GITHUB_REPO . '/releases/latest', [
        'timeout' => 10,
        'headers' => [
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version'),
        ],
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return false;
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    set_transient('bsq_github_release', $release, 12 * HOUR_IN_SECONDS);
    return $release;
}
