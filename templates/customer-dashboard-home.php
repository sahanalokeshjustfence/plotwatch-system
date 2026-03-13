<?php
if (!is_user_logged_in()) return;

global $wpdb;

$user_id = get_current_user_id();
$table = $wpdb->prefix . 'pw_properties';
$property_filter = isset($_GET['property']) ? intval($_GET['property']) : '';
$start_filter = isset($_GET['start']) ? $_GET['start'] : '';
$end_filter = isset($_GET['end']) ? $_GET['end'] : '';
$active_filter = isset($_GET['active']) ? $_GET['active'] : '';

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
AND (%d=0 OR p.id=%d)
AND (%s='' OR v.visit_date>=%s)
AND (%s='' OR v.visit_date<=%s)
AND v.visit_date>=CURDATE()
ORDER BY v.visit_date ASC
LIMIT 5",
$user_id,
$property_filter,$property_filter,
$start_filter,$start_filter,
$end_filter,$end_filter
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
   MONTHLY VISIT DONUT
================================ */

$chart_data = $wpdb->get_results(
$wpdb->prepare(
"SELECT 
MONTH(visit_date) m,
SUM(CASE WHEN visit_status='Scheduled' THEN 1 ELSE 0 END) scheduled,
SUM(CASE WHEN visit_status='Completed' THEN 1 ELSE 0 END) completed,
SUM(CASE WHEN visit_status='Missed' THEN 1 ELSE 0 END) missed,
SUM(CASE WHEN visit_status='Scheduled' AND visit_date < CURDATE() THEN 1 ELSE 0 END) overdue
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

<div class="pw-filter-bar">
<button class="pw-filter-btn" onclick="openFilter()">⚲ Filter</button>
</div>

<!-- FILTER MODAL -->
<div id="pwFilterModal" class="pw-filter-modal">
<div class="pw-filter-box">
<h3>Filter Options</h3>

<form method="get">

<label>Property</label>
<select name="property">
<option value="">Select...</option>

<?php foreach($property_dropdown as $prop): ?>

<option value="<?php echo $prop->id ?>" 
<?php if($property_filter==$prop->id) echo 'selected'; ?>>

<?php echo $prop->property_name ?> 
(PW<?php echo str_pad($prop->id,4,"0",STR_PAD_LEFT); ?>)

</option>

<?php endforeach; ?>

</select>

<label>Start Date</label>
<input type="date" name="start" value="<?php echo $start_filter ?>">

<label>End Date</label>
<input type="date" name="end" value="<?php echo $end_filter ?>">

<label>Active Subscription</label>

<div class="pw-radio">

<label><input type="radio" name="active" value="yes"
<?php if($active_filter=='yes') echo 'checked'; ?>> Yes</label>

<label><input type="radio" name="active" value="no"
<?php if($active_filter=='no') echo 'checked'; ?>> No</label>

</div>

<div class="pw-filter-actions">

<a href="<?php echo home_url('/customer-dashboard'); ?>" class="pw-reset">
Reset
</a>

<button type="submit" class="pw-apply">
Apply
</button>

</div>

</form>
</div>
</div>

<!-- SUMMARY CARDS -->

<div class="pw-stats-grid">

<div class="pw-stat-card blue" data-tooltip="Total properties created">
<div class="pw-stat-number"><?php echo $total_properties ?></div>
<div class="pw-stat-label">Total Properties</div>
</div>

<div class="pw-stat-card green" data-tooltip="Currently active subscriptions">
<div class="pw-stat-number"><?php echo $active_subscriptions ?></div>
<div class="pw-stat-label">Active Subscriptions</div>
</div>

<div class="pw-stat-card orange" data-tooltip="Scheduled upcoming visits">
<div class="pw-stat-number"><?php echo $upcoming_visits ?></div>
<div class="pw-stat-label">Upcoming Visits</div>
</div>

<div class="pw-stat-card purple" data-tooltip="Total completed visits">
<div class="pw-stat-number"><?php echo $completed_visits ?></div>
<div class="pw-stat-label">Completed Visits</div>
</div>

<div class="pw-stat-card pink" data-tooltip="Next scheduled property visit">
<div class="pw-stat-number">
<?php echo $next_visit ? date('d M',strtotime($next_visit->visit_date)) : '--'; ?>
</div>
<div class="pw-stat-label">Next Visit</div>
</div>

<div class="pw-stat-card red" data-tooltip="Subscriptions expiring soon">
<div class="pw-stat-number">
<?php echo count($expiry); ?>
</div>
<div class="pw-stat-label">Expiring Soon</div>
</div>

</div>

<!-- VISIT ANALYTICS + UPCOMING VISITS -->

<div class="pw-dashboard-grid">

<!-- VISIT ANALYTICS -->

<div class="pw-dash-box pw-analytics-box">

<h3>Visit Analytics</h3>

<div class="pw-donut-grid">

<div class="pw-donut-card">
<canvas id="scheduledChart"></canvas>
<div class="pw-donut-title">Scheduled</div>
</div>

<div class="pw-donut-card">
<canvas id="completedChart"></canvas>
<div class="pw-donut-title">Completed</div>
</div>

<div class="pw-donut-card">
<canvas id="missedChart"></canvas>
<div class="pw-donut-title">Missed</div>
</div>

<div class="pw-donut-card">
<canvas id="overdueChart"></canvas>
<div class="pw-donut-title">Overdue</div>
</div>

</div>

</div>

<!-- UPCOMING VISITS -->

<div class="pw-dash-box">

<h3>Upcoming Visits</h3>

<div class="pw-visit-list-modern">

<?php foreach($visits as $v): ?>

<div class="pw-visit-item" data-tooltip="Upcoming property visit">

<div class="pw-visit-left">
<?php echo date('d M Y', strtotime($v->visit_date)); ?>
</div>

<div class="pw-visit-right">
<?php echo esc_html($v->property_name); ?>
</div>

</div>

<?php endforeach; ?>

</div>

</div>

</div>

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

<div class="pw-timeline">

<div class="pw-step green" data-tooltip="Property Created">
<span>Created</span>
</div>

<?php if($subscription): ?>

<div class="pw-step green" data-tooltip="Package Assigned">
<span>Package</span>
</div>

<?php endif; ?>

<?php

$step=1;

foreach($property_visits as $visit){

$class="gray";

if($visit->visit_status=="Scheduled") $class="purple";
if($visit->visit_status=="Completed") $class="green";

echo '<div class="pw-step '.$class.'" data-tooltip="'.$visit->visit_status.'">
<span>Visit '.$step.'</span>
</div>';

$step++;

}

?>

</div>

</div>

<?php endforeach; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo PW_URL ?>assets/js/main.js"></script>

<script>

let scheduledTotal = 0;
let completedTotal = 0;
let missedTotal = 0;
let overdueTotal = 0;

<?php foreach($chart_data as $d): ?>

scheduledTotal += <?php echo $d->scheduled ?>;
completedTotal += <?php echo $d->completed ?>;
missedTotal += <?php echo $d->missed ?>;
overdueTotal += <?php echo $d->overdue ?>;

<?php endforeach; ?>

document.addEventListener("DOMContentLoaded",function(){

createDonut("scheduledChart",scheduledTotal,"#3b82f6");
createDonut("completedChart",completedTotal,"#22c55e");
createDonut("missedChart",missedTotal,"#f59e0b");
createDonut("overdueChart",overdueTotal,"#ef4444");

});

</script>

</div>