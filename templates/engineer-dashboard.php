<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();

if (!in_array('engineer', (array) $user->roles)) {
    wp_die('Unauthorized');
}

global $wpdb;
$engineer_id = $user->ID;

/* ============================================
   FETCH ASSIGNED VISITS
============================================ */

$visits = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT v.*, p.property_code, p.property_name, p.location_name
         FROM {$wpdb->prefix}pw_visits v
         LEFT JOIN {$wpdb->prefix}pw_properties p
         ON v.property_id = p.id
         WHERE v.engineer_id = %d
         ORDER BY v.visit_date ASC",
        $engineer_id
    )
);
?>

<h2>Engineer Dashboard</h2>

<?php if (!empty($visits)): ?>

<table class="pw-table">
<tr>
<th>Property ID</th>
<th>Property</th>
<th>Location</th>
<th>Visit Date</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php foreach ($visits as $visit): ?>

<tr>
<td><?php echo esc_html($visit->property_code); ?></td>
<td><?php echo esc_html($visit->property_name); ?></td>
<td><?php echo esc_html($visit->location_name); ?></td>
<td><?php echo esc_html($visit->visit_date); ?></td>

<td>
<span class="pw-status-badge 
<?php echo $visit->status === 'Completed' ? 'pw-status-completed' : 'pw-status-warning'; ?>">
<?php echo esc_html($visit->status); ?>
</span>
</td>

<td>

<?php if ($visit->status !== 'Completed'): ?>

<a href="<?php echo esc_url(home_url('/update-visit?visit_id=' . intval($visit->id))); ?>" 
class="pw-small-btn">
Update
</a>

<?php else: ?>

<a href="<?php echo esc_url(home_url('/update-visit?visit_id=' . intval($visit->id))); ?>" 
class="pw-small-btn">
View
</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</table>

<?php else: ?>

<div class="pw-success-box">
No assigned visits.
</div>

<?php endif; ?>