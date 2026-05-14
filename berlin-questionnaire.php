<?php
/**
 * Plugin Name: Berlin Sleep Apnea Questionnaire
 * Description: Multi-step Berlin Questionnaire with scoring, results, and GoHighLevel webhook integration.
 * Version:     1.0.4
 * Plugin URI:  https://upwork.com/freelancers/adelsherif8
 * Author:      Adel Emad
 * Author URI:  https://upwork.com/freelancers/adelsherif8
 * License:     GPL-2.0+
 * GitHub Plugin URI: adelsherif8/berlin-sleep-questionnaire
 */

defined('ABSPATH') || exit;

define('BSQ_VERSION', '1.0.4');
define('BSQ_DIR',     plugin_dir_path(__FILE__));
define('BSQ_URL',     plugin_dir_url(__FILE__));

/* ─── Colour helper ───────────────────────────────────── */

function bsq_primary_css(string $hex): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) $hex = '2d6a5a';
    [$r, $g, $b] = [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
    $dark  = sprintf('#%02x%02x%02x', max(0,(int)($r*.80)), max(0,(int)($g*.80)), max(0,(int)($b*.80)));
    $light = "rgba($r,$g,$b,0.10)";
    return "--bsq-primary:#{$hex};--bsq-primary-dark:{$dark};--bsq-primary-light:{$light}";
}

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

function bsq_custom_field_list(): array {
    return [
        // Demographics
        'age'            => ['label' => 'Age',                      'example' => '45',                    'group' => 'Demographics'],
        'gender'         => ['label' => 'Gender',                   'example' => 'male / female',          'group' => 'Demographics'],
        'height'         => ['label' => 'Height',                   'example' => "5'10\"",                 'group' => 'Demographics'],
        'weight'         => ['label' => 'Weight (lbs)',             'example' => '185',                   'group' => 'Demographics'],
        'bmi'            => ['label' => 'BMI',                      'example' => '26.4',                  'group' => 'Demographics'],
        // Berlin Answers
        'q2'             => ['label' => 'Q2 — Snores?',             'example' => 'yes / no / dont_know',  'group' => 'Berlin Answers'],
        'q3'             => ['label' => 'Q3 — Snoring volume',      'example' => 'very_loud',             'group' => 'Berlin Answers'],
        'q4'             => ['label' => 'Q4 — Snoring frequency',   'example' => 'nearly_every_day',      'group' => 'Berlin Answers'],
        'q5'             => ['label' => 'Q5 — Bothers others?',     'example' => 'yes / no',              'group' => 'Berlin Answers'],
        'q6'             => ['label' => 'Q6 — Stop breathing?',     'example' => 'nearly_every_day',      'group' => 'Berlin Answers'],
        'q7'             => ['label' => 'Q7 — Tired after sleep',   'example' => '3_4_times',             'group' => 'Berlin Answers'],
        'q8'             => ['label' => 'Q8 — Tired during day',    'example' => 'nearly_every_day',      'group' => 'Berlin Answers'],
        'q9'             => ['label' => 'Q9 — Fall asleep driving?','example' => 'yes / no',              'group' => 'Berlin Answers'],
        'q10'            => ['label' => 'Q10 — Blood pressure?',    'example' => 'yes / no / dont_know',  'group' => 'Berlin Answers'],
        // Score
        'risk_level'     => ['label' => 'Risk Level',               'example' => 'High Risk / Low Risk',  'group' => 'Score'],
        'pos_categories' => ['label' => 'Positive Categories (0–3)','example' => '2',                     'group' => 'Score'],
        'cat1_positive'  => ['label' => 'Category 1 Positive',      'example' => 'Yes / No',              'group' => 'Score'],
        'cat2_positive'  => ['label' => 'Category 2 Positive',      'example' => 'Yes / No',              'group' => 'Score'],
        'cat3_positive'  => ['label' => 'Category 3 Positive',      'example' => 'Yes / No',              'group' => 'Score'],
    ];
}

function bsq_render_settings() {
    if (isset($_POST['bsq_nonce']) && wp_verify_nonce($_POST['bsq_nonce'], 'bsq_save')) {
        update_option('bsq_ghl_api_key',     sanitize_text_field($_POST['bsq_ghl_api_key']     ?? ''));
        update_option('bsq_ghl_location_id', sanitize_text_field($_POST['bsq_ghl_location_id'] ?? ''));
        update_option('bsq_booking_url',     sanitize_text_field($_POST['bsq_booking_url']     ?? '/booking-method'));
        update_option('bsq_primary_color',   sanitize_hex_color($_POST['bsq_primary_color']    ?? '#2d6a5a') ?: '#2d6a5a');
        foreach (array_keys(bsq_custom_field_list()) as $key) {
            update_option('bsq_cf_' . $key, sanitize_text_field($_POST['bsq_cf_' . $key] ?? ''));
        }
        echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved.</strong></p></div>';
    }

    $api_key     = get_option('bsq_ghl_api_key',     '');
    $location_id = get_option('bsq_ghl_location_id', '');
    $booking_url = get_option('bsq_booking_url',     '/booking-method');
    $primary     = get_option('bsq_primary_color',   '#2d6a5a');

    // Group fields for display
    $groups = [];
    foreach (bsq_custom_field_list() as $key => $meta) {
        $groups[$meta['group']][$key] = $meta;
    }
    ?>
    <style>
        .bsq-wrap       { max-width:900px; margin-top:20px; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        .bsq-card       { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:24px 28px; margin-bottom:22px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
        .bsq-card-title { margin:0 0 4px; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#64748b; padding-bottom:14px; border-bottom:1px solid #f1f5f9; }
        .bsq-sc-row     { display:flex; align-items:center; gap:12px; background:#f8fafb; border:1px solid #e2e8f0; border-radius:7px; padding:13px 16px; font-family:monospace; font-size:15px; font-weight:700; color:#2d6a5a; }
        .bsq-sc-row button { flex-shrink:0; }
        .bsq-ft         { width:100%; border-collapse:collapse; margin-top:16px; }
        .bsq-ft th      { text-align:left; padding:7px 12px; background:#f8fafb; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; border-bottom:2px solid #e2e8f0; }
        .bsq-ft td      { padding:8px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:13px; }
        .bsq-ft td:first-child { width:260px; font-weight:500; color:#1e293b; }
        .bsq-ft td code { background:#f1f5f9; padding:2px 7px; border-radius:4px; font-size:11px; color:#475569; }
        .bsq-ft input   { width:100%; max-width:320px; }
        .bsq-group-row td { background:#f8fafb; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; padding:6px 12px; border-bottom:1px solid #e2e8f0; }
        .bsq-auto-tag   { display:inline-block; background:#dcfce7; color:#166534; font-size:10px; font-weight:700; padding:2px 7px; border-radius:99px; margin-left:6px; text-transform:uppercase; letter-spacing:.04em; }
    </style>

    <div class="bsq-wrap">
        <h1 style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
            <span style="background:#2d6a5a;color:#fff;width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">🔬</span>
            Berlin Sleep Questionnaire
        </h1>
        <p style="color:#64748b;margin:0 0 22px">Configure the questionnaire, GHL connection, and custom field mapping.</p>

        <form method="post">
            <?php wp_nonce_field('bsq_save', 'bsq_nonce'); ?>

            <!-- ── Shortcode ── -->
            <div class="bsq-card">
                <p class="bsq-card-title">Shortcode</p>
                <p style="margin:0 0 10px;color:#475569;font-size:13px">Paste this into any Page or Post where you want the questionnaire to appear.</p>
                <div class="bsq-sc-row">
                    <span>[berlin_questionnaire]</span>
                    <button type="button" class="button"
                            onclick="navigator.clipboard.writeText('[berlin_questionnaire]');this.textContent='Copied ✓';setTimeout(()=>this.textContent='Copy',2000)">
                        Copy
                    </button>
                </div>
            </div>

            <!-- ── Appearance ── -->
            <div class="bsq-card">
                <p class="bsq-card-title">Appearance</p>
                <table class="form-table" style="margin-top:0">
                    <tr>
                        <th style="width:180px"><label for="bsq_primary_color">Primary Colour</label></th>
                        <td>
                            <input type="color" id="bsq_primary_color" name="bsq_primary_color"
                                   value="<?php echo esc_attr($primary); ?>"
                                   style="height:38px;width:60px;cursor:pointer;border:1px solid #e2e8f0;border-radius:6px;padding:2px" />
                            <span style="margin-left:8px;font-size:13px;color:#475569">Used for the header, buttons, progress bar, and active selections.</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── GHL Connection ── -->
            <div class="bsq-card">
                <p class="bsq-card-title">GoHighLevel Connection</p>
                <table class="form-table" style="margin-top:0">
                    <tr>
                        <th style="width:180px"><label for="bsq_ghl_api_key">API Key</label></th>
                        <td>
                            <input type="password" id="bsq_ghl_api_key" name="bsq_ghl_api_key"
                                   value="<?php echo esc_attr($api_key); ?>" class="regular-text"
                                   placeholder="eyJ…" autocomplete="off" />
                            <p class="description">GHL → Settings → Private Integrations → Create Key &rarr; enable <strong>Contacts: Read + Write</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bsq_ghl_location_id">Location ID</label></th>
                        <td>
                            <input type="text" id="bsq_ghl_location_id" name="bsq_ghl_location_id"
                                   value="<?php echo esc_attr($location_id); ?>" class="regular-text"
                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxx" />
                            <p class="description">GHL → Settings → Business Info → Location ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bsq_booking_url">Booking Page URL</label></th>
                        <td>
                            <input type="text" id="bsq_booking_url" name="bsq_booking_url"
                                   value="<?php echo esc_attr($booking_url); ?>" class="regular-text"
                                   placeholder="/booking-method" />
                            <p class="description">The "Book Appointment" button on the results screen links here.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── Fields sent to GHL ── -->
            <div class="bsq-card">
                <p class="bsq-card-title">GHL Custom Field IDs</p>
                <p style="margin:0;color:#475569;font-size:13px">
                    Fields marked <span class="bsq-auto-tag">Auto</span> are mapped automatically (Name, Email, Phone, Source, Tags).
                    For the rest, create a matching Custom Field in GHL (Settings → Custom Fields) and paste its <strong>Field ID</strong> into the right column.
                    Leave blank to skip that field.
                </p>

                <!-- Auto-mapped fields (read-only info) -->
                <table class="bsq-ft" style="margin-top:18px">
                    <thead>
                        <tr><th>Field</th><th>Value sent</th><th>Mapping</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>First Name</td><td><code>From full name</code></td><td><span class="bsq-auto-tag">Auto</span></td></tr>
                        <tr><td>Last Name</td><td><code>From full name</code></td><td><span class="bsq-auto-tag">Auto</span></td></tr>
                        <tr><td>Email</td><td><code>patient email</code></td><td><span class="bsq-auto-tag">Auto</span></td></tr>
                        <tr><td>Phone</td><td><code>patient phone</code></td><td><span class="bsq-auto-tag">Auto</span></td></tr>
                        <tr><td>Source</td><td><code>Berlin Sleep Questionnaire</code></td><td><span class="bsq-auto-tag">Auto</span></td></tr>
                        <tr><td>Tags</td><td><code>sleep-apnea-screening, berlin-high-risk / berlin-low-risk</code></td><td><span class="bsq-auto-tag">Auto</span></td></tr>
                    </tbody>
                </table>

                <!-- Custom field mapping per group -->
                <?php foreach ($groups as $group_name => $fields): ?>
                <table class="bsq-ft" style="margin-top:16px">
                    <thead>
                        <tr><th colspan="3"><?php echo esc_html($group_name); ?></th></tr>
                        <tr><th>Field</th><th>Example value</th><th>GHL Custom Field ID</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fields as $key => $meta): ?>
                        <tr>
                            <td><?php echo esc_html($meta['label']); ?></td>
                            <td><code><?php echo esc_html($meta['example']); ?></code></td>
                            <td>
                                <input type="text" name="bsq_cf_<?php echo esc_attr($key); ?>"
                                       value="<?php echo esc_attr(get_option('bsq_cf_' . $key, '')); ?>"
                                       placeholder="Paste GHL field ID…" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endforeach; ?>
            </div>

            <?php submit_button('Save Settings', 'primary large'); ?>
        </form>

        <!-- ── Force Update Check ── -->
        <div class="bsq-card" style="margin-top:22px">
            <p class="bsq-card-title">Plugin Updates</p>
            <p style="margin:0 0 14px;color:#475569;font-size:13px">
                WordPress caches update data for up to 12 hours. If you just pushed a new release to GitHub and it isn't showing in
                <strong>Dashboard → Updates</strong>, click below to clear the cache and force an immediate re-check.
            </p>
            <button type="button" id="bsq-force-update-btn" class="button button-secondary">
                Force Update Check
            </button>
            <span id="bsq-force-update-msg" style="margin-left:12px;font-size:13px;color:#475569"></span>
        </div>
        <script>
        document.getElementById('bsq-force-update-btn').addEventListener('click', function () {
            var btn = this;
            var msg = document.getElementById('bsq-force-update-msg');
            btn.disabled = true;
            btn.textContent = 'Checking…';
            msg.textContent = '';
            var fd = new FormData();
            fd.append('action', 'bsq_force_update_check');
            fd.append('nonce', '<?php echo esc_js(wp_create_nonce('bsq_force_update')); ?>');
            fetch(ajaxurl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    btn.disabled = false;
                    btn.textContent = 'Force Update Check';
                    if (res.success) {
                        msg.style.color = '#166534';
                        msg.textContent = res.data.message;
                    } else {
                        msg.style.color = '#991b1b';
                        msg.textContent = 'Error — try again.';
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.textContent = 'Force Update Check';
                    msg.style.color = '#991b1b';
                    msg.textContent = 'Request failed.';
                });
        });
        </script>
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
    $primary = get_option('bsq_primary_color', '#2d6a5a');
    ob_start();
    echo '<style>#bsq-wrap{' . bsq_primary_css($primary) . '}</style>';
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

    // Bot protection: honeypot filled → silently drop; submitted too fast → silently drop
    if (!empty($d['_hp'])) { wp_send_json_success([]); return; }
    if (intval($d['_elapsed'] ?? 0) < 8) { wp_send_json_success([]); return; }

    $score = bsq_score($d);

    if (get_option('bsq_ghl_api_key') && get_option('bsq_ghl_location_id')) {
        bsq_send_ghl($d, $score);
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

/* ─── GHL API ─────────────────────────────────────────── */

function bsq_send_ghl(array $d, array $score): void {
    $api_key     = get_option('bsq_ghl_api_key',     '');
    $location_id = get_option('bsq_ghl_location_id', '');
    if (!$api_key || !$location_id) return;

    $parts = explode(' ', trim($d['full_name'] ?? ''), 2);

    // Values for each custom field key
    $field_values = [
        'age'            => $d['age']              ?? '',
        'gender'         => $d['gender']           ?? '',
        'height'         => $d['height_display']   ?? '',
        'weight'         => $d['weight_lbs']       ?? '',
        'bmi'            => (string) $score['bmi'],
        'q2'             => $d['q2']  ?? '',
        'q3'             => $d['q3']  ?? '',
        'q4'             => $d['q4']  ?? '',
        'q5'             => $d['q5']  ?? '',
        'q6'             => $d['q6']  ?? '',
        'q7'             => $d['q7']  ?? '',
        'q8'             => $d['q8']  ?? '',
        'q9'             => $d['q9']  ?? '',
        'q10'            => $d['q10'] ?? '',
        'risk_level'     => $score['risk_level'],
        'pos_categories' => (string) $score['pos_categories'],
        'cat1_positive'  => $score['cat1_positive'] ? 'Yes' : 'No',
        'cat2_positive'  => $score['cat2_positive'] ? 'Yes' : 'No',
        'cat3_positive'  => $score['cat3_positive'] ? 'Yes' : 'No',
    ];

    // Build customFields array — only include fields that have an ID configured
    $custom_fields = [];
    foreach ($field_values as $key => $value) {
        $field_id = get_option('bsq_cf_' . $key, '');
        if ($field_id && $value !== '') {
            $custom_fields[] = ['id' => $field_id, 'field_value' => $value];
        }
    }

    $payload = [
        'firstName'    => $parts[0] ?? '',
        'lastName'     => $parts[1] ?? '',
        'email'        => $d['email'] ?? '',
        'phone'        => $d['phone'] ?? '',
        'locationId'   => $location_id,
        'source'       => 'Berlin Sleep Questionnaire',
        'tags'         => [
            'sleep-apnea-screening',
            $score['risk_level'] === 'High Risk' ? 'berlin-high-risk' : 'berlin-low-risk',
        ],
    ];

    if (!empty($custom_fields)) {
        $payload['customFields'] = $custom_fields;
    }

    wp_remote_post('https://services.leadconnectorhq.com/contacts/', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Version'       => '2021-07-28',
            'Content-Type'  => 'application/json',
        ],
        'body'     => wp_json_encode($payload),
        'timeout'  => 15,
        'blocking' => false,
    ]);
}

/* ─── Force Update Check AJAX ────────────────────────── */

add_action('wp_ajax_bsq_force_update_check', function () {
    check_ajax_referer('bsq_force_update', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();

    delete_transient('bsq_github_release');
    delete_site_transient('update_plugins');

    wp_send_json_success(['message' => 'Cache cleared. Go to Dashboard → Updates and click "Check Again" to see the latest version.']);
});

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
