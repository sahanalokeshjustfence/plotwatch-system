<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();

if (!in_array('engineer', (array) $user->roles)) {
    wp_die('Unauthorized');
}

global $wpdb;

$engineer_id = $user->ID;
$visit_id = isset($_GET['visit_id']) ? intval($_GET['visit_id']) : 0;

if (!$visit_id) {
    echo "<div class='pw-success-box'>Invalid Visit</div>";
    return;
}

/* ================= FETCH VISIT ================= */

$visit = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT v.*, p.property_name, p.location_name, p.property_code
         FROM {$wpdb->prefix}pw_visits v
         LEFT JOIN {$wpdb->prefix}pw_properties p
         ON v.property_id = p.id
         WHERE v.id = %d AND v.engineer_id = %d",
        $visit_id,
        $engineer_id
    )
);

if (!$visit) {
    echo "<div class='pw-success-box'>Visit Not Found</div>";
    return;
}

/* ================= HANDLE SUBMIT ================= */

if (isset($_POST['pw_update_visit'])) {

    if (!wp_verify_nonce($_POST['_wpnonce'], 'pw_update_visit_nonce')) {
        wp_die('Security Failed');
    }

    $report = sanitize_textarea_field($_POST['report']);
    $media_url = '';

    if (!empty($_FILES['visit_image']['name'])) {

        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $uploaded = wp_handle_upload($_FILES['visit_image'], ['test_form'=>false]);

        if (!isset($uploaded['error'])) {
            $media_url = esc_url_raw($uploaded['url']);
        }
    }

    $wpdb->update(
        $wpdb->prefix . 'pw_visits',
        [
            'status' => 'Completed',
            'report' => $report,
            'image'  => $media_url,
            'completed_at' => current_time('mysql')
        ],
        ['id' => $visit_id]
    );

    echo "<div class='pw-success-box'>Visit Updated Successfully</div>";

    // Refresh visit data
    $visit->status = 'Completed';
    $visit->report = $report;
    $visit->image  = $media_url;
}
?>

<div class="pw-rectangle">

<h2>Update Visit</h2>

<div class="pw-grid-3">

<div>
<label>Property ID</label>
<input type="text" value="<?php echo esc_attr($visit->property_code); ?>" readonly>
</div>

<div>
<label>Property Name</label>
<input type="text" value="<?php echo esc_attr($visit->property_name); ?>" readonly>
</div>

<div>
<label>Location</label>
<input type="text" value="<?php echo esc_attr($visit->location_name); ?>" readonly>
</div>

<div>
<label>Visit Date</label>
<input type="text" value="<?php echo esc_attr($visit->visit_date); ?>" readonly>
</div>

<div>
<label>Status</label>
<input type="text" value="<?php echo esc_attr($visit->status); ?>" readonly>
</div>

</div>

<?php if ($visit->status !== 'Completed'): ?>

<hr>

<form method="post" enctype="multipart/form-data">

<?php wp_nonce_field('pw_update_visit_nonce'); ?>
<input type="hidden" name="pw_update_visit" value="1">

<label>Visit Report</label>
<textarea name="report" required style="width:100%;height:120px;margin-bottom:15px;"></textarea>

<label>Upload Image</label>
<input type="file" name="visit_image" style="margin-bottom:15px;">

<br>
<button class="pw-btn">Mark Visit Completed</button>

</form>

<?php else: ?>

<hr>

<h3>Visit Report</h3>
<p><?php echo esc_html($visit->report); ?></p>

<?php if (!empty($visit->image)): ?>
<img src="<?php echo esc_url($visit->image); ?>" 
style="max-width:300px;border-radius:8px;margin-top:10px;">
<?php endif; ?>

<?php endif; ?>

</div>