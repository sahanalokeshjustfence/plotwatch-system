<?php
if (!is_user_logged_in()) return;

global $wpdb;

/* ===============================
FILTER
================================ */

$start_filter = isset($_GET['start']) ? $_GET['start'] : '';
$end_filter   = isset($_GET['end']) ? $_GET['end'] : '';

/* ===============================
STATS
================================ */

$total_properties = $wpdb->get_var(
"SELECT COUNT(*) FROM {$wpdb->prefix}pw_properties"
);

$completed_properties = $wpdb->get_var(
"SELECT COUNT(*) 
FROM {$wpdb->prefix}pw_properties
WHERE subscription_status='Subscription Completed'"
);

$today_visits = $wpdb->get_var(
"SELECT COUNT(*) 
FROM {$wpdb->prefix}pw_visits
WHERE visit_date = CURDATE()"
);

$active_subscriptions = $wpdb->get_var(
"SELECT COUNT(*) 
FROM {$wpdb->prefix}pw_subscriptions
WHERE CURDATE() BETWEEN start_date AND end_date"
);

$upcoming_visits = $wpdb->get_var(
"SELECT COUNT(*) 
FROM {$wpdb->prefix}pw_visits
WHERE visit_date >= CURDATE()
AND visit_status='Scheduled'"
);

$expiring = $wpdb->get_var(
"SELECT COUNT(*)
FROM {$wpdb->prefix}pw_subscriptions
WHERE end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
);

/* ===============================
VISIT ANALYTICS
================================ */

$scheduled = $wpdb->get_var(
"SELECT COUNT(*) FROM {$wpdb->prefix}pw_visits WHERE visit_status='Scheduled'"
);

$completed = $wpdb->get_var(
"SELECT COUNT(*) FROM {$wpdb->prefix}pw_visits WHERE visit_status='Completed'"
);

$missed = $wpdb->get_var(
"SELECT COUNT(*) FROM {$wpdb->prefix}pw_visits WHERE visit_status='Missed'"
);

$overdue = $wpdb->get_var(
"SELECT COUNT(*) 
FROM {$wpdb->prefix}pw_visits
WHERE visit_status='Scheduled'
AND visit_date < CURDATE()"
);

/* ===============================
UPCOMING VISITS
================================ */

$visits = $wpdb->get_results(
"SELECT v.visit_date,p.property_name
FROM {$wpdb->prefix}pw_visits v
JOIN {$wpdb->prefix}pw_properties p
ON p.id=v.property_id
WHERE v.visit_date >= CURDATE()
ORDER BY v.visit_date ASC
LIMIT 5"
);

/* ===============================
EXPIRY
================================ */

$expiry = $wpdb->get_results(
"SELECT p.property_name,s.end_date
FROM {$wpdb->prefix}pw_subscriptions s
JOIN {$wpdb->prefix}pw_properties p
ON p.id=s.property_id
WHERE s.end_date <= DATE_ADD(CURDATE(),INTERVAL 30 DAY)
ORDER BY s.end_date ASC
LIMIT 5"
);

/* ===============================
ENGINEER VISIT STATS
================================ */

$engineers = $wpdb->get_results(
"
SELECT 
u.ID,
u.display_name,

SUM(CASE WHEN v.visit_status='Scheduled' THEN 1 ELSE 0 END) scheduled,
SUM(CASE WHEN v.visit_status='Pending' THEN 1 ELSE 0 END) pending,
SUM(CASE WHEN v.visit_status='Completed' THEN 1 ELSE 0 END) completed

FROM {$wpdb->users} u

LEFT JOIN {$wpdb->prefix}pw_visits v
ON v.engineer_id = u.ID

GROUP BY u.ID
"
);
/* ===============================
ENGINEERS
================================ */

$engineers = get_users([
'role'=>'engineer'
]);
?>

<div class="pw-dashboard">

<h2>Operation Dashboard</h2>

<!-- FILTER -->

<div class="pw-filter-bar">
<button class="pw-filter-btn" onclick="openFilter()">⚲ Filter</button>
</div>

<div id="pwFilterModal" class="pw-filter-modal">

<div class="pw-filter-box">

<form method="get">

<label>Start Date</label>
<input type="date" name="start" value="<?php echo $start_filter ?>">

<label>End Date</label>
<input type="date" name="end" value="<?php echo $end_filter ?>">

<div class="pw-filter-actions">

<a href="<?php echo home_url('/operation-dashboard'); ?>" class="pw-reset">
Reset
</a>

<button type="submit" class="pw-apply">
Apply
</button>

</div>

</form>

</div>

</div>

<!-- STATS -->

<div class="pw-stats-grid">

<div class="pw-stat-card blue">
<div class="pw-stat-number"><?php echo $total_properties ?></div>
<div class="pw-stat-label">Total Properties</div>
</div>

<div class="pw-stat-card green">
<div class="pw-stat-number"><?php echo $completed_properties ?></div>
<div class="pw-stat-label">Completed Properties</div>
</div>

<div class="pw-stat-card orange">
<div class="pw-stat-number"><?php echo $today_visits ?></div>
<div class="pw-stat-label">Today Visits</div>
</div>

<div class="pw-stat-card purple">
<div class="pw-stat-number"><?php echo $active_subscriptions ?></div>
<div class="pw-stat-label">Active Subscription</div>
</div>

<div class="pw-stat-card pink">
<div class="pw-stat-number"><?php echo $upcoming_visits ?></div>
<div class="pw-stat-label">Upcoming Visits</div>
</div>

<div class="pw-stat-card red">
<div class="pw-stat-number"><?php echo $expiring ?></div>
<div class="pw-stat-label">Expiring Soon</div>
</div>

</div>


<!-- ROW 1 -->

<div class="pw-dashboard-grid">

<!-- VISIT ANALYTICS -->

<div class="pw-dash-box">

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


<!-- UPCOMING SUBSCRIPTION -->

<div class="pw-dash-box">

<h3>Upcoming Subscription Expiry</h3>

<?php if(!empty($expiry)): ?>

<?php foreach($expiry as $e): ?>

<div class="pw-list-item">
<div><?php echo esc_html($e->property_name) ?></div>
<div><?php echo date('d M',strtotime($e->end_date)) ?></div>
</div>

<?php endforeach; ?>

<?php else: ?>

<div class="pw-list-item">
<div>No Expiring Subscriptions</div>
<div>-</div>
</div>

<?php endif; ?>

</div>

</div>

<!-- ROW 2 -->

<div class="pw-dashboard-grid">



<!-- ENGINEERS -->

<div class="pw-dash-box pw-engineer-box">

<h3>Engineers</h3>

<table class="pw-table pw-engineer-table">

<tr>
<th>Engineer</th>
<th>S</th>
<th>P</th>
<th>C</th>
</tr>

<?php foreach($engineers as $eng): ?>

<tr>

<td><?php echo esc_html($eng->display_name); ?></td>

<td class="pw-badge blue">
<?php echo intval($eng->scheduled); ?>
</td>

<td class="pw-badge orange">
<?php echo intval($eng->pending); ?>
</td>

<td class="pw-badge green">
<?php echo intval($eng->completed); ?>
</td>

</tr>

<?php endforeach; ?>

</table>

</div>

<!-- UPCOMING VISITS -->

<div class="pw-dash-box">

<h3>Upcoming Visits</h3>

<?php foreach($visits as $v): ?>

<div class="pw-list-item">
<div><?php echo date('d M',strtotime($v->visit_date)) ?></div>
<div><?php echo esc_html($v->property_name) ?></div>
</div>

<?php endforeach; ?>

</div>

</div>



</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

/* ===============================
DONUT CHART FUNCTION
================================ */

function createDonut(id,value,color){

const ctx=document.getElementById(id);

if(!ctx) return;

new Chart(ctx,{
type:'doughnut',
data:{
datasets:[{
data:[value, Math.max(1,value)],
backgroundColor:[color,'#e5e7eb'],
borderWidth:0
}]
},
options:{
cutout:'70%',
plugins:{
legend:{display:false},
tooltip:{enabled:false}
}
},
plugins:[{

id:'textCenter',

afterDraw(chart){

const {ctx} = chart;
ctx.save();

ctx.font="bold 18px Arial";
ctx.fillStyle="#333";
ctx.textAlign="center";
ctx.textBaseline="middle";

ctx.fillText(value, chart.width/2, chart.height/2);

}

}]
});

}

/* ===============================
RENDER DONUTS
================================ */

createDonut("scheduledChart",<?php echo $scheduled ?>,"#3b82f6");
createDonut("completedChart",<?php echo $completed ?>,"#22c55e");
createDonut("missedChart",<?php echo $missed ?>,"#f59e0b");
createDonut("overdueChart",<?php echo $overdue ?>,"#ef4444");


function openFilter(){
let modal=document.getElementById("pwFilterModal");
modal.style.display = modal.style.display === "block" ? "none" : "block";
}
</script>