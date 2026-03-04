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

/* ================= FETCH CURRENT VISIT ================= */

$current_visit = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT v.*, p.property_name, p.location_name, p.property_code
         FROM {$wpdb->prefix}pw_visits v
         LEFT JOIN {$wpdb->prefix}pw_properties p
         ON v.property_id = p.id
         WHERE v.id = %d",
        $visit_id
    )
);

if (!$current_visit) {
    echo "<div class='pw-success-box'>Visit Not Found</div>";
    return;
}

/* ================= FETCH ALL VISITS ================= */

$all_visits = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_visits
         WHERE property_id = %d
         ORDER BY visit_date ASC",
        $current_visit->property_id
    )
);

/* ================= FETCH SUBSCRIPTION ================= */

$subscription = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_subscriptions
         WHERE property_id = %d
         ORDER BY id DESC LIMIT 1",
        $current_visit->property_id
    )
);

/* ================= HANDLE UPDATE ================= */

if (isset($_POST['pw_update_visit'])) {

    if (!wp_verify_nonce($_POST['_wpnonce'], 'pw_update_visit_nonce')) {
        wp_die('Security Failed');
    }

    // Only assigned engineer can update
    if ($current_visit->engineer_id != $engineer_id) {
        wp_die('You are not assigned to this visit.');
    }

    $notes = sanitize_textarea_field($_POST['report']);
    $image_url = '';

    if (!empty($_FILES['visit_image']['name'])) {

        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $uploaded = wp_handle_upload($_FILES['visit_image'], ['test_form'=>false]);

        if (!isset($uploaded['error'])) {
            $image_url = esc_url_raw($uploaded['url']);
        }
    }

    if (!empty($image_url)) {
        $notes .= "\n\nImage: " . $image_url;
    }

    $wpdb->update(
        $wpdb->prefix . 'pw_visits',
        [
            'visit_status' => 'Completed',
            'notes'        => $notes
        ],
        ['id' => $visit_id]
    );

    // Check if all visits completed
    $total_visits = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}pw_visits
         WHERE property_id = %d",
        $current_visit->property_id
    )
);

$completed_visits = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}pw_visits
         WHERE property_id = %d AND visit_status = 'Completed'",
        $current_visit->property_id
    )
);

if ($completed_visits == $total_visits) {

    // All visits done
    $wpdb->update(
        $wpdb->prefix . 'pw_properties',
        ['subscription_status' => 'Subscription Completed'],
        ['id' => $current_visit->property_id]
    );

} else {

    // At least one visit completed but not all
    $wpdb->update(
        $wpdb->prefix . 'pw_properties',
        ['subscription_status' => 'Visit In Progress'],
        ['id' => $current_visit->property_id]
    );
}

    echo "<div class='pw-success-box'>Visit Completed Successfully</div>";

    // Refresh data
    $current_visit->visit_status = 'Completed';
    $current_visit->notes = $notes;
}
?>

<!-- ================= TOP SUMMARY ================= -->

<div class="pw-rectangle">

<h2>Update Visit</h2>

<div class="pw-grid-3">

<div>
<label>Property ID</label>
<input type="text" value="<?php echo esc_attr($current_visit->property_code); ?>" readonly>
</div>

<div>
<label>Property Name</label>
<input type="text" value="<?php echo esc_attr($current_visit->property_name); ?>" readonly>
</div>

<div>
<label>Location</label>
<input type="text" value="<?php echo esc_attr($current_visit->location_name); ?>" readonly>
</div>

<div>
<label>Package</label>
<input type="text" value="<?php echo esc_attr($subscription->package_type ?? ''); ?>" readonly>
</div>

<div>
<label>Visit Date</label>
<input type="text" value="<?php echo esc_attr($current_visit->visit_date); ?>" readonly>
</div>

<div>
<label>Status</label>
<input class="pw-status-field <?php echo strtolower($current_visit->visit_status); ?>" 
type="text" 
value="<?php echo esc_attr($current_visit->visit_status); ?>" 
readonly>
</div>

</div>

<hr>

<!-- ================= VISIT TABS ================= -->

<div class="pw-visit-tabs">

<?php foreach ($all_visits as $index => $v): ?>

<?php $active = ($v->id == $visit_id) ? 'active' : ''; ?>

<a class="pw-visit-tab <?php echo $active; ?>"
href="<?php echo esc_url(home_url('/update-visit?visit_id=' . $v->id)); ?>">
Visit <?php echo $index + 1; ?>
(<?php echo esc_html($v->visit_status); ?>)
</a>

<?php endforeach; ?>

</div>

<hr>

<!-- ================= EDIT SECTION ================= -->

<?php if (
    $current_visit->visit_status !== 'Completed' &&
    $current_visit->engineer_id == $engineer_id
): ?>

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

<h3>Visit Report</h3>
<p><?php echo nl2br(esc_html($current_visit->notes)); ?></p>

<?php endif; ?>

</div>