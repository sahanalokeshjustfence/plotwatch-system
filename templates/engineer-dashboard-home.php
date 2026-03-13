<?php
if (!is_user_logged_in()) return;

global $wpdb;
$user = wp_get_current_user();
$engineer_id = $user->ID;

$scheduled = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*) FROM {$wpdb->prefix}pw_visits
WHERE engineer_id=%d AND visit_status='Scheduled'",
$engineer_id
)
);

$completed = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*) FROM {$wpdb->prefix}pw_visits
WHERE engineer_id=%d AND visit_status='Completed'",
$engineer_id
)
);
?>

<h2>Engineer Dashboard</h2>

<div class="pw-engineer-stats">

<div class="pw-stat-card orange">
<div class="pw-stat-number"><?php echo $scheduled ?></div>
<div class="pw-stat-label">Visit Scheduled</div>
</div>

<div class="pw-stat-card green">
<div class="pw-stat-number"><?php echo $completed ?></div>
<div class="pw-stat-label">Visit Completed</div>
</div>

</div>