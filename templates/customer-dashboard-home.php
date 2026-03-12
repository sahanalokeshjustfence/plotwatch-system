<?php
if (!is_user_logged_in()) return;

global $wpdb;

$user_id = get_current_user_id();
$table = $wpdb->prefix . 'pw_properties';

/* ===============================
   PROPERTY DROPDOWN
================================ */

$property_dropdown = $wpdb->get_results(
$wpdb->prepare(
"SELECT id,property_name
FROM $table
WHERE user_id=%d",
$user_id
)
);

/* ===============================
   DASHBOARD DATA
================================ */

$total_properties = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*) FROM $table WHERE user_id=%d",
$user_id
)
);

$active_subscriptions = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*)
FROM {$wpdb->prefix}pw_subscriptions s
JOIN $table p ON p.id=s.property_id
WHERE p.user_id=%d
AND CURDATE() BETWEEN s.start_date AND s.end_date",
$user_id
)
);

$upcoming_visits = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*)
FROM {$wpdb->prefix}pw_visits v
JOIN $table p ON p.id=v.property_id
WHERE p.user_id=%d
AND v.visit_date>=CURDATE()",
$user_id
)
);

$completed_visits = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*)
FROM {$wpdb->prefix}pw_visits v
JOIN $table p ON p.id=v.property_id
WHERE p.user_id=%d
AND v.visit_status='Completed'",
$user_id
)
);

/* ===============================
   PROPERTY LIST
================================ */

$properties = $wpdb->get_results(
$wpdb->prepare(
"SELECT id,property_name
FROM $table
WHERE user_id=%d",
$user_id
)
);

/* ===============================
   UPCOMING VISITS
================================ */

$visits = $wpdb->get_results(
$wpdb->prepare(
"SELECT v.visit_date,p.property_name
FROM {$wpdb->prefix}pw_visits v
JOIN $table p ON p.id=v.property_id
WHERE p.user_id=%d
AND v.visit_date>=CURDATE()
ORDER BY v.visit_date ASC
LIMIT 5",
$user_id
)
);

/* ===============================
   NEXT VISIT
================================ */

$next_visit = $wpdb->get_row(
$wpdb->prepare(
"SELECT v.visit_date,p.property_name
FROM {$wpdb->prefix}pw_visits v
JOIN $table p ON p.id=v.property_id
WHERE p.user_id=%d
AND v.visit_date>=CURDATE()
ORDER BY v.visit_date ASC
LIMIT 1",
$user_id
)
);

/* ===============================
   SUBSCRIPTION EXPIRY
================================ */

$expiry = $wpdb->get_results(
$wpdb->prepare(
"SELECT p.property_name,s.end_date
FROM {$wpdb->prefix}pw_subscriptions s
JOIN $table p ON p.id=s.property_id
WHERE p.user_id=%d
AND s.end_date<=DATE_ADD(CURDATE(),INTERVAL 30 DAY)",
$user_id
)
);

/* ===============================
   MONTHLY VISIT GRAPH
================================ */

$chart_data = $wpdb->get_results(
$wpdb->prepare(
"SELECT MONTH(visit_date) m, COUNT(*) c
FROM {$wpdb->prefix}pw_visits v
JOIN $table p ON p.id=v.property_id
WHERE p.user_id=%d
GROUP BY m",
$user_id
)
);
?>

<div class="pw-dashboard">

<!-- FILTER BAR -->

<div class="pw-filter-wrapper">

<form class="pw-filter-bar">

<select name="property">

<option value="">Property Name / ID</option>

<?php foreach($property_dropdown as $prop): ?>

<option value="<?php echo $prop->id ?>">
<?php echo $prop->property_name ?> (<?php echo $prop->id ?>)
</option>

<?php endforeach; ?>

</select>

<input type="date" name="start">
<input type="date" name="end">

<select name="active">
<option value="">Active Subscription</option>
<option value="yes">Yes</option>
<option value="no">No</option>
</select>

<button type="submit">Apply</button>

</form>

</div>



<!-- SUMMARY CARDS -->

<div class="pw-stats-grid">

<div class="pw-stat-card blue">
<div class="pw-stat-number"><?php echo $total_properties ?></div>
<div class="pw-stat-label">Total Properties</div>
</div>

<div class="pw-stat-card green">
<div class="pw-stat-number"><?php echo $active_subscriptions ?></div>
<div class="pw-stat-label">Active Subscriptions</div>
</div>

<div class="pw-stat-card orange">
<div class="pw-stat-number"><?php echo $upcoming_visits ?></div>
<div class="pw-stat-label">Upcoming Visits</div>
</div>

<div class="pw-stat-card purple">
<div class="pw-stat-number"><?php echo $completed_visits ?></div>
<div class="pw-stat-label">Completed Visits</div>
</div>

</div>



<!-- VISIT GRAPH -->

<div class="pw-dash-box">

<h3>Visit Analytics</h3>

<canvas id="visitChart"></canvas>

<script>

window.visitChartData = [0,0,0,0,0,0,0,0,0,0,0,0];

<?php foreach($chart_data as $d): ?>
window.visitChartData[<?php echo $d->m-1 ?>] = <?php echo $d->c ?>;
<?php endforeach; ?>

</script>

</div>



<div class="pw-dashboard-grid">

<!-- PROPERTY PROGRESS -->

<div class="pw-dash-box scroll-box">

<h3>Property Progress</h3>

<?php foreach($properties as $p):

$subscription = $wpdb->get_row(
$wpdb->prepare(
"SELECT * FROM {$wpdb->prefix}pw_subscriptions
WHERE property_id=%d
ORDER BY id DESC LIMIT 1",
$p->id
)
);

$property_visits = $wpdb->get_results(
$wpdb->prepare(
"SELECT visit_status,visit_date
FROM {$wpdb->prefix}pw_visits
WHERE property_id=%d
ORDER BY visit_date ASC",
$p->id
)
);

?>

<div class="pw-property-progress">

<div class="pw-property-title">
<?php echo esc_html($p->property_name); ?>
</div>

<div class="pw-box-progress">

<div class="pw-progress-box green" data-title="Property Created"></div>

<?php if($subscription): ?>
<div class="pw-progress-box green" data-title="Package Assigned"></div>
<?php endif; ?>

<?php

$max_visits=0;

if($subscription){

if($subscription->package_type=="Monthly") $max_visits=12;
if($subscription->package_type=="Quarterly") $max_visits=4;
if($subscription->package_type=="Yearly") $max_visits=1;

}

$index=0;

foreach($property_visits as $visit){

$class="gray";
$title="Pending Visit";

if($visit->visit_status=="Scheduled"){
$class="purple";
$title="Visit Scheduled ".$visit->visit_date;
}

if($visit->visit_status=="Completed"){
$class="green";
$title="Visit Completed ".$visit->visit_date;
}

echo '<div class="pw-progress-box '.$class.'" data-title="'.$title.'"></div>';

$index++;

}

for($i=$index;$i<$max_visits;$i++){
echo '<div class="pw-progress-box gray" data-title="Pending Visit"></div>';
}

?>

</div>

</div>

<?php endforeach; ?>

</div>



<!-- UPCOMING VISITS -->

<div class="pw-dash-box">

<h3>Upcoming Visits</h3>

<ul class="pw-visit-list">

<?php foreach($visits as $v): ?>

<li>
<span><?php echo date('d M Y', strtotime($v->visit_date)); ?></span>
<span><?php echo esc_html($v->property_name); ?></span>
</li>

<?php endforeach; ?>

</ul>

</div>



<!-- NEXT VISIT -->

<div class="pw-dash-box">

<h3>Next Visit</h3>

<?php if($next_visit): ?>

<div class="pw-next-card">

<div class="pw-next-date">
<?php echo date('d M Y', strtotime($next_visit->visit_date)); ?>
</div>

<div class="pw-next-property">
<?php echo esc_html($next_visit->property_name); ?>
</div>

</div>

<?php endif; ?>

</div>



<!-- SUBSCRIPTION EXPIRY -->

<div class="pw-dash-box">

<h3>Subscription Expiring Soon</h3>

<ul>

<?php foreach($expiry as $e): ?>

<li>
<?php echo esc_html($e->property_name) ?>
-
<?php echo date('d M Y',strtotime($e->end_date)) ?>
</li>

<?php endforeach; ?>

</ul>

</div>

</div>

</div>