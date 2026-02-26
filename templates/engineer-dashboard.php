<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();
if (!in_array('engineer', $user->roles)) wp_die('Unauthorized');

global $wpdb;

/* HANDLE VISIT SUBMIT */
if (isset($_POST['pw_engineer_report'])) {

    if (!wp_verify_nonce($_POST['_wpnonce'], 'pw_engineer_report_nonce')) {
        wp_die('Security failed');
    }

    $property_id = intval($_POST['property_id']);
    $comment     = sanitize_textarea_field($_POST['comment']);
    $media_url   = '';

    if (!empty($_FILES['media_file']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $uploaded = wp_handle_upload($_FILES['media_file'], ['test_form'=>false]);
        if (!isset($uploaded['error'])) {
            $media_url = $uploaded['url'];
        }
    }

    $wpdb->insert(
        $wpdb->prefix . 'pw_property_logs',
        [
            'property_id' => $property_id,
            'engineer_id' => $user->ID,
            'visit_date'  => current_time('Y-m-d'),
            'comment'     => $comment,
            'media_url'   => $media_url,
            'visit_status'=> 'Completed',
            'created_at'  => current_time('mysql')
        ]
    );

    $wpdb->update(
        $wpdb->prefix . 'pw_properties',
        ['subscription_status' => 'Visit Completed'],
        ['id' => $property_id]
    );

    echo "<div class='pw-success'>Visit Completed Successfully</div>";
}

/* FETCH ASSIGNED */
$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_properties
         WHERE assigned_engineer = %d
         AND subscription_status IN ('Active Subscription','Visit Scheduled')",
        $user->ID
    )
);
?>

<h2>Assigned Properties</h2>

<?php if ($rows): ?>

<table class="pw-table">
<tr>
<th>ID</th>
<th>Name</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php foreach ($rows as $row): ?>
<tr>
<td><?php echo esc_html($row->property_code); ?></td>
<td><?php echo esc_html($row->property_name); ?></td>
<td><?php echo esc_html($row->subscription_status); ?></td>
<td>
<button class="pw-small-btn"
onclick="pwOpenEngineerModal(<?php echo $row->id; ?>)">
Open
</button>
</td>
</tr>
<?php endforeach; ?>
</table>

<?php else: ?>
<p>No assigned properties.</p>
<?php endif; ?>

<!-- ENGINEER MODAL -->
<div id="pwEngineerModal" class="pw-modal" style="display:none;">
<div class="pw-modal-content">

<span class="pw-close" onclick="pwCloseEngineerModal()">&times;</span>

<h3>Submit Visit Report</h3>

<form method="post" enctype="multipart/form-data">
<?php wp_nonce_field('pw_engineer_report_nonce'); ?>
<input type="hidden" name="pw_engineer_report" value="1">
<input type="hidden" name="property_id" id="pw_property_id">

<textarea name="comment" placeholder="Work comments..." required style="width:100%;margin-bottom:10px;"></textarea>

<input type="file" name="media_file" style="margin-bottom:10px;">

<button type="submit" class="pw-small-btn">Mark Visit Completed</button>
</form>

</div>
</div>

<script>
function pwOpenEngineerModal(id){
    document.getElementById("pw_property_id").value = id;
    document.getElementById("pwEngineerModal").style.display = "block";
}

function pwCloseEngineerModal(){
    document.getElementById("pwEngineerModal").style.display = "none";
}
</script>