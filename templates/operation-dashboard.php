<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();
if (!in_array('operation_member', (array) $user->roles)) {
    wp_die('Unauthorized');
}

global $wpdb;

/* =====================================================
   FILTERS & PAGINATION
===================================================== */

$tab    = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'new';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

/* NEW FILTER VARIABLES */

$property_code = isset($_GET['property_code']) ? sanitize_text_field($_GET['property_code']) : '';
$property_name = isset($_GET['property_name']) ? sanitize_text_field($_GET['property_name']) : '';
$start_date    = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_date      = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

$per_page = 10;
$offset   = ($page - 1) * $per_page;

/* =====================================================
   WHERE CONDITION
===================================================== */

$where = " WHERE 1=1 ";

if ($tab === 'new') {
$heading = "New Properties";
}else{
$heading = "All Properties";
}

/* SEARCH */

if (!empty($search)) {

$like = '%' . $wpdb->esc_like($search) . '%';

$where .= $wpdb->prepare(
" AND (p.property_name LIKE %s
OR p.location_name LIKE %s
OR p.property_code LIKE %s)",
$like, $like, $like
);

}

/* PROPERTY CODE FILTER */

if (!empty($property_code)) {

$where .= $wpdb->prepare(
" AND p.property_code LIKE %s",
"%$property_code%"
);

}

/* PROPERTY NAME FILTER */

if (!empty($property_name)) {

$where .= $wpdb->prepare(
" AND p.property_name LIKE %s",
"%$property_name%"
);

}

/* STATUS FILTER */

if (!empty($status)) {

$where .= $wpdb->prepare(
" AND p.subscription_status = %s",
$status
);

}

/* DATE FILTER */

if (!empty($start_date)) {

$where .= $wpdb->prepare(
" AND DATE(p.created_at) >= %s",
$start_date
);

}

if (!empty($end_date)) {

$where .= $wpdb->prepare(
" AND DATE(p.created_at) <= %s",
$end_date
);

}

/* =====================================================
   TOTAL
===================================================== */

$total = $wpdb->get_var(
"SELECT COUNT(*)
FROM {$wpdb->prefix}pw_properties p
$where"
);

$total_pages = max(1, ceil($total / $per_page));

/* =====================================================
   FETCH
===================================================== */

$rows = $wpdb->get_results(
$wpdb->prepare(
"SELECT 
p.id,
p.property_code,
p.property_name,
p.location_name,
p.subscription_status,
u.display_name AS customer_name
FROM {$wpdb->prefix}pw_properties p
LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
$where
ORDER BY p.created_at DESC
LIMIT %d OFFSET %d",
$per_page,
$offset
)
);
?>

<h2 style="margin-bottom:25px;"><?php echo esc_html($heading); ?></h2>

<div class="pw-top-bar">

<form method="get" class="pw-search-form">

<input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">

<input type="text"
name="search"
placeholder="Search property..."
value="<?php echo esc_attr($search); ?>">

</form>

<button type="button" class="pw-btn" id="filterToggle">
Filter
</button>

</div>

<div id="filterBox" class="pw-filter-popup" style="display:none;">

<form method="get">

<input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">
<input type="hidden" name="search" value="<?php echo esc_attr($search); ?>">

<label>Property ID</label>
<input type="text" name="property_code" value="<?php echo esc_attr($property_code); ?>">

<label>Property Name</label>
<input type="text" name="property_name" value="<?php echo esc_attr($property_name); ?>">

<label>Status</label>

<select name="status">

<option value="">All Status</option>

<option value="Pending Package Assignment" <?php selected($status,'Pending Package Assignment'); ?>>
Pending Package Assignment
</option>

<option value="Visits Created" <?php selected($status,'Visits Created'); ?>>
Visits Created
</option>

<option value="Visit Assigned" <?php selected($status,'Visit Assigned'); ?>>
Visit Assigned
</option>

<option value="Subscription Completed" <?php selected($status,'Subscription Completed'); ?>>
Subscription Completed
</option>

</select>

<label>Start Date</label>
<input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">

<label>End Date</label>
<input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">

<div class="pw-filter-actions">

<button class="pw-btn">Apply</button>

<a href="<?php echo esc_url( remove_query_arg(['property_code','property_name','status','start_date','end_date','search','paged']) ); ?>" class="pw-btn-light">
Reset
</a>

</div>

</form>

</div>

<?php if (!empty($rows)) : ?>

<table class="pw-table">

<thead>

<tr>
<th>#</th>
<th>Property ID</th>
<th>Customer</th>
<th>Property</th>
<th>Location</th>
<th>Status</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php

$serial = $offset + 1;

foreach ($rows as $row):

$status_value = trim((string)$row->subscription_status);

if ($status_value === '') {
$status_value = 'Pending Package Assignment';
}

$status_class = 'pw-status-pending';

if ($status_value === 'Subscription Completed') {
$status_class = 'pw-status-completed';
}
elseif ($status_value === 'Visits Created') {
$status_class = 'pw-status-warning';
}
elseif (stripos($status_value,'visit') !== false) {
$status_class = 'pw-status-active';
}

$assign_url = esc_url(
home_url('/assign-package?property_id=' . intval($row->id))
);

?>

<tr>

<td><?php echo $serial++; ?></td>

<td><?php echo esc_html($row->property_code); ?></td>

<td><?php echo esc_html($row->customer_name); ?></td>

<td><?php echo esc_html($row->property_name); ?></td>

<td><?php echo esc_html($row->location_name); ?></td>

<td>

<span class="pw-status-badge <?php echo esc_attr($status_class); ?>">
<?php echo esc_html($status_value); ?>
</span>

</td>

<td>

<a href="<?php echo $assign_url; ?>" class="pw-small-btn">
View
</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<div class="pw-pagination">

<?php if ($page > 1): ?>

<a class="pw-page-btn"
href="<?php echo esc_url( add_query_arg(['paged'=>$page-1,'tab'=>$tab,'search'=>$search,'status'=>$status]) ); ?>">
← Previous
</a>

<?php endif; ?>

<span class="pw-page-info">
Page <?php echo $page; ?> of <?php echo $total_pages; ?>
</span>

<?php if ($page < $total_pages): ?>

<a class="pw-page-btn"
href="<?php echo esc_url( add_query_arg(['paged'=>$page+1,'tab'=>$tab,'search'=>$search,'status'=>$status]) ); ?>">
Next →
</a>

<?php endif; ?>

</div>

<?php else : ?>

<div class="pw-success-box">
No records found.
</div>

<?php endif; ?>

<script>

document.getElementById("filterToggle").addEventListener("click", function(){

let box = document.getElementById("filterBox");

box.style.display = box.style.display === "block" ? "none" : "block";

});

</script>