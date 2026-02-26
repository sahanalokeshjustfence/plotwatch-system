<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();
if (!in_array('operation_member', (array) $user->roles)) {
    wp_die('Unauthorized');
}

global $wpdb;

$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'package';

if (!$property_id) {
    echo "<div class='pw-success-box'>Invalid Property</div>";
    return;
}

/* =============================
   LOAD PROPERTY
============================= */

$property = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT p.*, u.display_name, u.user_email
         FROM {$wpdb->prefix}pw_properties p
         LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
         WHERE p.id = %d",
         $property_id
    )
);

if (!$property) {
    echo "<div class='pw-success-box'>Property Not Found</div>";
    return;
}

/* =============================
   HANDLE PACKAGE SAVE
============================= */

if (isset($_POST['save_package'])) {

    $package_type = sanitize_text_field($_POST['package_type']);
    $start_date   = sanitize_text_field($_POST['start_date']);
    $end_date     = sanitize_text_field($_POST['end_date']);
    $addons       = isset($_POST['addons']) ? $_POST['addons'] : [];

    /* Update Property */
    $wpdb->update(
        "{$wpdb->prefix}pw_properties",
        [
            'subscription_status' => 'Package Assigned',
            'package_type'        => $package_type,
            'start_date'          => $start_date,
            'end_date'            => $end_date
        ],
        ['id' => $property_id]
    );

    /* Remove old visits */
    $wpdb->delete("{$wpdb->prefix}pw_visits", ['property_id' => $property_id]);

    /* AUTO CREATE VISITS */

    $duration = 0;
    $interval = 0;

    if ($package_type === 'monthly') {
        $duration = 12;
        $interval = 1;
    }
    elseif ($package_type === 'quarterly') {
        $duration = 4;
        $interval = 3;
    }
    elseif ($package_type === 'yearly') {
        $duration = 1;
        $interval = 12;
    }

    for ($i = 0; $i < $duration; $i++) {

        $visit_date = date(
            'Y-m-d',
            strtotime("+".($i * $interval)." month", strtotime($start_date))
        );

        $wpdb->insert("{$wpdb->prefix}pw_visits", [
            'property_id' => $property_id,
            'visit_date'  => $visit_date,
            'status'      => 'Pending'
        ]);
    }

    /* SAVE ADDONS */
    $wpdb->delete("{$wpdb->prefix}pw_property_addons", ['property_id'=>$property_id]);

    foreach ($addons as $addon_id) {
        $wpdb->insert("{$wpdb->prefix}pw_property_addons", [
            'property_id' => $property_id,
            'addon_id'    => intval($addon_id)
        ]);
    }

    echo "<div class='pw-success-box'>Package Assigned Successfully</div>";
}

/* =============================
   HANDLE VISIT ASSIGN
============================= */

if (isset($_POST['assign_visit'])) {

    $visit_id    = intval($_POST['visit_id']);
    $engineer_id = intval($_POST['engineer_id']);
    $visit_date  = sanitize_text_field($_POST['visit_date']);
    $comments    = sanitize_text_field($_POST['comments']);

    $wpdb->update(
        "{$wpdb->prefix}pw_visits",
        [
            'engineer_id' => $engineer_id,
            'visit_date'  => $visit_date,
            'comments'    => $comments,
            'status'      => 'Scheduled'
        ],
        ['id' => $visit_id]
    );

    echo "<div class='pw-success-box'>Visit Assigned</div>";
}

/* Fetch Addons */
$addons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pw_addons ORDER BY name ASC");

/* Fetch Visits */
$visits = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_visits 
         WHERE property_id = %d 
         ORDER BY visit_date ASC",
        $property_id
    )
);

/* Fetch Engineers */
$engineers = get_users(['role'=>'engineer']);
?>

<div class="pw-rectangle">

<!-- PREMIUM PROPERTY HEADER -->
<div class="pw-property-header">

<div class="pw-prop-left">
<h2><?php echo esc_html($property->property_name); ?></h2>
<span class="pw-prop-id">
<?php echo esc_html($property->property_code); ?>
</span>
</div>

<div class="pw-prop-grid">

<div>
<span>Customer</span>
<strong><?php echo esc_html($property->display_name); ?></strong>
</div>

<div>
<span>Email</span>
<strong><?php echo esc_html($property->user_email); ?></strong>
</div>

<div>
<span>Location</span>
<strong><?php echo esc_html($property->location_name); ?></strong>
</div>

<div>
<span>Plot Size</span>
<strong><?php echo esc_html($property->plot_size); ?></strong>
</div>

</div>

</div>

<!-- INNER TABS -->
<div class="pw-inner-tabs">
    <a href="<?php echo esc_url(home_url('/assign-package?property_id='.$property_id.'&tab=package')); ?>"
       class="<?php echo ($tab==='package')?'active':''; ?>">
       Assign Package
    </a>

    <a href="<?php echo esc_url(home_url('/assign-package?property_id='.$property_id.'&tab=visit')); ?>"
       class="<?php echo ($tab==='visit')?'active':''; ?>">
       Assign Visit
    </a>
</div>

<?php if ($tab === 'package'): ?>

<form method="post">

<h3>Package Details</h3>

<div class="pw-grid-3">

<div>
<label>Package Type</label>
<select name="package_type" required>
<option value="">Select Package</option>
<option value="monthly">Monthly</option>
<option value="quarterly">Quarterly</option>
<option value="yearly">Yearly</option>
</select>
</div>

<div>
<label>Start Date</label>
<input type="date" name="start_date" required>
</div>

<div>
<label>End Date</label>
<input type="date" name="end_date" required>
</div>

</div>

<h3>Add-ons</h3>

<div class="pw-grid-3">
<?php foreach ($addons as $addon): ?>
<div>
<label>
<input type="checkbox" name="addons[]" value="<?php echo esc_attr($addon->id); ?>">
<?php echo esc_html($addon->name); ?>
</label>
</div>
<?php endforeach; ?>
</div>

<br>
<button name="save_package" class="pw-btn">Save Package</button>
</form>

<?php elseif ($tab === 'visit'): ?>

<h3>Assign Visits</h3>

<table class="pw-table">
<thead>
<tr>
<th>Visit Date</th>
<th>Engineer</th>
<th>Status</th>
<th>Comments</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php foreach($visits as $visit): ?>
<tr>
<form method="post">
<input type="hidden" name="visit_id" value="<?php echo $visit->id; ?>">

<td>
<input type="date" name="visit_date"
value="<?php echo esc_attr($visit->visit_date); ?>">
</td>

<td>
<select name="engineer_id">
<option value="">Select</option>
<?php foreach($engineers as $eng): ?>
<option value="<?php echo $eng->ID; ?>"
<?php selected($visit->engineer_id,$eng->ID); ?>>
<?php echo esc_html($eng->display_name); ?>
</option>
<?php endforeach; ?>
</select>
</td>

<td><?php echo esc_html($visit->status); ?></td>

<td>
<input type="text" name="comments"
value="<?php echo esc_attr($visit->comments); ?>">
</td>

<td>
<button name="assign_visit" class="pw-small-btn">Save</button>
</td>

</form>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<?php endif; ?>

</div>