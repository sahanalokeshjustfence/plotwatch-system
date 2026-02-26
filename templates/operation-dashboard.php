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

$tab     = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'new';
$search  = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status  = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$page    = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

$per_page = 10;
$offset   = ($page - 1) * $per_page;

/* =====================================================
   BASE WHERE CONDITION
===================================================== */

$where = " WHERE 1=1 ";

if ($tab === 'new') {
    $where .= " AND p.subscription_status = 'Pending Package Assignment' ";
    $heading = "New Properties";
} else {
    $heading = "All Properties";
}

/* Search Filter */
if (!empty($search)) {
    $like = '%' . $wpdb->esc_like($search) . '%';
    $where .= $wpdb->prepare(
        " AND (p.property_name LIKE %s
               OR p.location_name LIKE %s
               OR p.property_code LIKE %s)",
        $like,
        $like,
        $like
    );
}

/* Status Filter */
if (!empty($status)) {
    $where .= $wpdb->prepare(
        " AND p.subscription_status = %s",
        $status
    );
}

/* =====================================================
   TOTAL COUNT
===================================================== */

$total = $wpdb->get_var(
    "SELECT COUNT(*)
     FROM {$wpdb->prefix}pw_properties p
     $where"
);

$total_pages = max(1, ceil($total / $per_page));

/* =====================================================
   FETCH DATA
===================================================== */

$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT p.*, u.display_name AS customer_name
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

<h2><?php echo esc_html($heading); ?></h2>

<!-- =====================================================
     FILTER BAR
===================================================== -->

<div class="pw-filter-bar">

<form method="get" class="pw-filter-left">

    <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">

    <input type="text"
           name="search"
           placeholder="Search property..."
           value="<?php echo esc_attr($search); ?>">

    <select name="status">
        <option value="">All Status</option>
        <option value="Pending Package Assignment" <?php selected($status,'Pending Package Assignment'); ?>>Pending</option>
        <option value="Package Assigned" <?php selected($status,'Package Assigned'); ?>>Package Assigned</option>
        <option value="Visit Scheduled" <?php selected($status,'Visit Scheduled'); ?>>Visit Scheduled</option>
        <option value="Visit Completed" <?php selected($status,'Visit Completed'); ?>>Visit Completed</option>
        <option value="Subscription Completed" <?php selected($status,'Subscription Completed'); ?>>Subscription Completed</option>
    </select>

    <button type="submit" class="pw-btn">Filter</button>

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

$status_class = 'pw-status-pending';

switch ($row->subscription_status) {
    case 'Package Assigned':
        $status_class = 'pw-status-active';
        break;
    case 'Visit Scheduled':
        $status_class = 'pw-status-warning';
        break;
    case 'Visit Completed':
        $status_class = 'pw-status-active';
        break;
    case 'Subscription Completed':
        $status_class = 'pw-status-completed';
        break;
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
<?php echo esc_html($row->subscription_status); ?>
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

<!-- =====================================================
     PAGINATION
===================================================== -->

<div class="pw-pagination">

<?php if ($page > 1): ?>
<a class="pw-page-btn"
   href="<?php echo add_query_arg([
        'tab' => $tab,
        'search' => $search,
        'status' => $status,
        'paged' => $page - 1
   ]); ?>">
← Previous
</a>
<?php endif; ?>

<span class="pw-page-info">
Page <?php echo $page; ?> of <?php echo $total_pages; ?>
</span>

<?php if ($page < $total_pages): ?>
<a class="pw-page-btn"
   href="<?php echo add_query_arg([
        'tab' => $tab,
        'search' => $search,
        'status' => $status,
        'paged' => $page + 1
   ]); ?>">
Next →
</a>
<?php endif; ?>

</div>

<?php else : ?>

<div class="pw-success-box">
No records found.
</div>

<?php endif; ?>