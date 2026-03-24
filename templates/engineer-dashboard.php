<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();

if (!in_array('engineer', (array) $user->roles)) {
    wp_die('Unauthorized');
}

global $wpdb;
$engineer_id = $user->ID;

/* =====================================================
   FILTERS & PAGINATION
===================================================== */

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

$per_page = 10;
$offset   = ($page - 1) * $per_page;

/* =====================================================
   WHERE CONDITION
===================================================== */

$where = " WHERE v.engineer_id = %d ";

if (!empty($search)) {

$like = '%' . $wpdb->esc_like($search) . '%';

$where .= $wpdb->prepare(
" AND (p.property_name LIKE %s
       OR p.location_name LIKE %s
       OR p.property_code LIKE %s)",
$like,$like,$like
);

}

if (!empty($status)) {

$where .= $wpdb->prepare(
" AND v.visit_status = %s",
$status
);

}

if (!empty($start_date)) {

$where .= $wpdb->prepare(
" AND DATE(v.visit_date) >= %s",
$start_date
);

}

if (!empty($end_date)) {

$where .= $wpdb->prepare(
" AND DATE(v.visit_date) <= %s",
$end_date
);

}

/* =====================================================
   TOTAL COUNT
===================================================== */

$total = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*)
 FROM {$wpdb->prefix}pw_visits v
 LEFT JOIN {$wpdb->prefix}pw_properties p
 ON v.property_id = p.id
 $where",
$engineer_id
)
);

$total_pages = max(1, ceil($total / $per_page));

/* =====================================================
   FETCH VISITS
===================================================== */

$visits = $wpdb->get_results(
$wpdb->prepare(
"SELECT v.*, p.property_code, p.property_name, p.location_name
 FROM {$wpdb->prefix}pw_visits v
 LEFT JOIN {$wpdb->prefix}pw_properties p
 ON v.property_id = p.id
 $where
 ORDER BY v.visit_date ASC
 LIMIT %d OFFSET %d",
$engineer_id,
$per_page,
$offset
)
);
?>

<h2 style="margin-bottom:25px;">Property visits</h2>


<!-- =====================================================
TOP BAR
===================================================== -->

<div class="pw-top-bar">

<form method="get" class="pw-search-form">

<input type="text"
name="search"
placeholder="Search property..."
value="<?php echo esc_attr($search); ?>">

</form>

<button type="button" class="pw-btn" id="filterToggle">
Filter
</button>

</div>


<!-- =====================================================
FILTER POPUP
===================================================== -->

<div id="filterBox" class="pw-filter-popup" style="display:none;">

<form method="get">

<label>Status</label>

<select name="status">

<option value="">All</option>

<option value="Pending"
<?php selected($status,'Pending'); ?>>Pending</option>

<option value="Scheduled"
<?php selected($status,'Scheduled'); ?>>Scheduled</option>

<option value="Completed"
<?php selected($status,'Completed'); ?>>Completed</option>

</select>

<label>Start Date</label>

<input type="date"
name="start_date"
value="<?php echo esc_attr($start_date); ?>">

<label>End Date</label>

<input type="date"
name="end_date"
value="<?php echo esc_attr($end_date); ?>">

<div class="pw-filter-actions">

<button class="pw-btn">Apply</button>

<a href="<?php echo esc_url(remove_query_arg(['status','start_date','end_date','search','paged'])); ?>" class="pw-btn-light">
Reset
</a>

</div>

</form>

</div>



<?php if (!empty($visits)): ?>

<table class="pw-table">

<thead>

<tr>

<th>Property ID</th>
<th>Property</th>
<th>Location</th>
<th>Visit Date</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php foreach ($visits as $visit): ?>

<tr>

<td><?php echo esc_html($visit->property_code); ?></td>

<td><?php echo esc_html($visit->property_name); ?></td>

<td><?php echo esc_html($visit->location_name); ?></td>

<td><?php echo esc_html($visit->visit_date); ?></td>

<td>

<?php

$visit_status_class = 'pw-status-pending';
$today = date('Y-m-d');

if ($visit->visit_status != 'Completed' && $visit->visit_date < $today) {

$visit->visit_status = "Overdue";
$visit_status_class = 'pw-status-overdue';

}else{

switch ($visit->visit_status) {

case 'Scheduled':
$visit_status_class = 'pw-status-scheduled';
break;

case 'Completed':
$visit_status_class = 'pw-status-completed';
break;

default:
$visit_status_class = 'pw-status-pending';

}

}

?>

<span class="pw-status-badge <?php echo esc_attr($visit_status_class); ?>">
<?php echo esc_html($visit->visit_status); ?>
</span>

</td>

<td>

<?php if ($visit->visit_status !== 'Completed'): ?>

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

</tbody>

</table>


<!-- =====================================================
PAGINATION
===================================================== -->

<div class="pw-pagination">

<?php if ($page > 1): ?>

<a class="pw-page-btn"
href="<?php echo esc_url(add_query_arg(['paged'=>$page-1])); ?>">
← Previous
</a>

<?php endif; ?>


<span class="pw-page-info">

Page <?php echo $page; ?> of <?php echo $total_pages; ?>

</span>


<?php if ($page < $total_pages): ?>

<a class="pw-page-btn"
href="<?php echo esc_url(add_query_arg(['paged'=>$page+1])); ?>">
Next →
</a>

<?php endif; ?>

</div>

<?php else: ?>

<div class="pw-success-box">
No assigned visits.
</div>

<?php endif; ?>


<script>

document.getElementById("filterToggle").addEventListener("click", function(){

let box = document.getElementById("filterBox");

box.style.display = box.style.display === "block" ? "none" : "block";

});

</script>