<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();
if (!in_array('operation_member', (array) $user->roles)) {
    wp_die('Unauthorized');
}

global $wpdb;

$property_id = intval($_GET['property_id'] ?? 0);
$tab = $_GET['tab'] ?? 'package';
$edit_mode = isset($_GET['edit']);

if (!$property_id) {
    echo "<div class='pw-success-box'>Invalid Property</div>";
    return;
}

/* ================= LOAD PROPERTY ================= */

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


/* ================= LOAD LATEST SUBSCRIPTION ================= */

$subscription = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_subscriptions
         WHERE property_id = %d
         ORDER BY id DESC LIMIT 1",
        $property_id
    )
);

$package_assigned = !empty($subscription);

/* ================= SAVE PACKAGE ================= */

if (isset($_POST['save_package'])) {

    check_admin_referer('pw_assign_package_nonce');

    $package_type = sanitize_text_field($_POST['package_type']);
    $start_date   = sanitize_text_field($_POST['start_date']);
    $end_date     = sanitize_text_field($_POST['end_date']);
    $price        = floatval($_POST['package_price']);
    $addons       = $_POST['addons'] ?? [];

    // Insert into subscriptions table
$wpdb->insert(
    "{$wpdb->prefix}pw_subscriptions",
    [
        'property_id'   => $property_id,
        'package_type'  => $package_type,
        'start_date'    => $start_date,
        'end_date'      => $end_date,
        'package_price' => $price,
        'addons'        => json_encode($addons),
        'status'        => 'Active'
    ]
);

$subscription_id = $wpdb->insert_id;

/* ================= RECREATE VISITS ================= */

// Delete old visits
$wpdb->delete(
    "{$wpdb->prefix}pw_visits",
    ['property_id' => $property_id]
);

// Decide duration & interval
$duration = ($package_type == 'monthly') ? 12 : 
            (($package_type == 'quarterly') ? 4 : 1);

$interval = ($package_type == 'monthly') ? 1 : 
            (($package_type == 'quarterly') ? 3 : 12);

// Create visits
for ($i = 0; $i < $duration; $i++) {

    $visit_date = date(
        'Y-m-d',
        strtotime("+".($i * $interval)." month", strtotime($start_date))
    );

    $wpdb->insert(
        "{$wpdb->prefix}pw_visits",
        [
            'property_id'    => $property_id,
            'subscription_id'=> $subscription_id,
            'visit_date'     => $visit_date,
            'visit_status'   => 'Pending'
        ]
    );
}

/* ================= UPDATE PROPERTY STATUS ================= */

$wpdb->update(
    "{$wpdb->prefix}pw_properties",
    ['subscription_status' => 'Visits Created'],
    ['id' => $property_id]
);
    

    /* Save addons */
    /*$wpdb->delete("{$wpdb->prefix}pw_property_addons", ['property_id'=>$property_id]);

    foreach($addons as $addon_id){
        $wpdb->insert("{$wpdb->prefix}pw_property_addons",[
            'property_id'=>$property_id,
            'addon_id'=>intval($addon_id)
        ]);
    }*/

    echo "<script>window.location.href='?property_id=$property_id&tab=package';</script>";
    exit;
}

/* ================= SAVE VISIT ================= */

if (isset($_POST['assign_visit'])) {

    check_admin_referer('pw_assign_visit_nonce');

    $visit_id    = intval($_POST['visit_id']);
    $engineer_id = intval($_POST['engineer_id']);
    $visit_date  = sanitize_text_field($_POST['visit_date']);
    $notes       = sanitize_text_field($_POST['comments']);

    $wpdb->update(
        "{$wpdb->prefix}pw_visits",
        [
            'engineer_id'  => $engineer_id,
            'visit_date'   => $visit_date,
            'notes'        => $notes,
            'visit_status' => 'Scheduled'
        ],
        ['id'=>$visit_id]
    );
    $wpdb->update(
    "{$wpdb->prefix}pw_properties",
    ['subscription_status' => 'Visit Assigned'],
    ['id'=>$property_id]
);

    echo "<script>window.location.href='?property_id=$property_id&tab=visit';</script>";
    exit;
}

/* ================= FETCH DATA ================= */

$addons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pw_addons ORDER BY name ASC");

$selected_addons = [];

if ($subscription && !empty($subscription->addons)) {
    $selected_addons = json_decode($subscription->addons, true);
}

$visits = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_visits WHERE property_id=%d ORDER BY visit_date ASC",
        $property_id
    )
);

$engineers = get_users(['role'=>'engineer']);
?>

<div class="pw-detail-wrapper">

<!-- ================= HEADER ================= -->

<div class="pw-header-card">

<h2><?php echo esc_html($property->property_name); ?></h2>
<div class="pw-sub-id">
Property ID: <?php echo esc_html($property->property_code); ?>
</div>

<div class="pw-header-grid">

<div><label>Contact Person</label><span><?php echo esc_html($property->contact_person); ?></span></div>
<div><label>Contact Number</label><span><?php echo esc_html($property->contact_number); ?></span></div>
<div><label>Email</label><span><?php echo esc_html($property->user_email); ?></span></div>
<div><label>Property Type</label><span><?php echo esc_html($property->property_type); ?></span></div>
<div><label>Plot Size</label><span><?php echo esc_html($property->plot_size); ?></span></div>
<div><label>Location</label><span><?php echo esc_html($property->location_name); ?></span></div>

<div style="grid-column: span 3;">
<label>Address</label>
<span><?php echo esc_html($property->address); ?></span>
</div>

<?php if(!empty($property->google_map)) : ?>
<div style="grid-column: span 3;">
<label>Google Map</label>
<a href="<?php echo esc_url($property->google_map); ?>" target="_blank">View Location</a>
</div>
<?php endif; ?>

<?php if(!empty($property->special_instructions)) : ?>
<div style="grid-column: span 3;">
<label>Special Instructions</label>
<span><?php echo esc_html($property->special_instructions); ?></span>
</div>
<?php endif; ?>

</div>
</div>

<!-- ================= TABS ================= -->

<div class="pw-tabs">
<a href="?property_id=<?php echo $property_id;?>&tab=package"
class="<?php echo ($tab=='package')?'active':'';?>">Assign Package</a>

<a href="?property_id=<?php echo $property_id;?>&tab=visit"
class="<?php echo ($tab=='visit')?'active':'';?>">Assign Visits</a>
</div>

<div class="pw-tab-content">

<?php if($tab=='package'): ?>

<?php if($package_assigned && !$edit_mode): ?>

<div class="pw-readonly-card">
<div class="pw-readonly-grid">
<div><label>Package</label><span><?php echo esc_html($subscription->package_type ?? ''); ?></span></div>
<div><label>Start Date</label><span><?php echo esc_html($subscription->start_date ?? ''); ?></span></div>
<div><label>End Date</label><span><?php echo esc_html($subscription->end_date ?? ''); ?></span></div>
<div><label>Price</label><span>₹<?php echo esc_html($subscription->package_price ?? ''); ?></span></div>
<div style="grid-column: span 2;">
    <label>Add-ons</label>
    <span>
        <?php
        if (!empty($selected_addons)) {
            $addon_names = [];

            foreach ($addons as $addon) {
                if (in_array($addon->id, $selected_addons)) {
                    $addon_names[] = esc_html($addon->name);
                }
            }

            echo implode(', ', $addon_names);
        } else {
            echo 'No Add-ons Selected';
        }
        ?>
    </span>
</div>
</div>

<a href="?property_id=<?php echo $property_id;?>&tab=package&edit=1"
class="pw-btn">Edit Package</a>
</div>

<?php else: ?>

<form method="post" class="pw-form-card">
<?php wp_nonce_field('pw_assign_package_nonce'); ?>

<div class="pw-form-grid">

<div>
<label>Package Type</label>
<select name="package_type" required>
<option value="">Select Package</option>
<option value="monthly" <?php selected($subscription->package_type ?? '', 'monthly'); ?>>Monthly</option>
<option value="quarterly" <?php selected($subscription->package_type ?? '', 'quarterly'); ?>>Quarterly</option>
<option value="yearly" <?php selected($subscription->package_type ?? '', 'yearly'); ?>>Yearly</option>
</select>
</div>

<div>
<label>Start Date</label>
<input type="date" name="start_date" value="<?php echo esc_attr($subscription->start_date ?? '');?>" required>
</div>

<div>
<label>End Date</label>
<input type="date" name="end_date" value="<?php echo esc_attr($subscription->end_date ?? '');?>" required>
</div>

<div>
<label>Package Price</label>
<input type="number" name="package_price" value="<?php echo esc_attr($subscription->package_price ?? '');?>">
</div>

</div>

<h4>Add-ons</h4>

<div class="pw-addon-grid">
<?php foreach($addons as $addon): ?>
<label class="pw-addon-item">
<input type="checkbox" name="addons[]" value="<?php echo $addon->id;?>"
<?php if(in_array($addon->id,$selected_addons)) echo "checked"; ?>>
<?php echo esc_html($addon->name);?>
</label>
<?php endforeach;?>
</div>

<br>
<button name="save_package" class="pw-btn">Save Package</button>
</form>

<?php endif; ?>

<?php elseif($tab=='visit'): ?>

<div class="pw-form-card">

<?php if(!empty($visits)): ?>

<table class="pw-table">
<thead>
<tr>
<th>Date</th>
<th>Engineer</th>
<th>Status</th>
<th>Notes</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php foreach($visits as $visit): ?>
<tr>
<form method="post">
<?php wp_nonce_field('pw_assign_visit_nonce'); ?>
<input type="hidden" name="visit_id" value="<?php echo $visit->id; ?>">

<td>
    <input class="pw-input" type="date" name="visit_date" 
        value="<?php echo esc_attr($visit->visit_date); ?>">
</td>
<td>
<select class="pw-input" name="engineer_id">
<option value="">Select</option>
<?php foreach($engineers as $eng): ?>
<option value="<?php echo $eng->ID; ?>" <?php selected($visit->engineer_id,$eng->ID); ?>>
<?php echo esc_html($eng->display_name); ?>
</option>
<?php endforeach; ?>
</select>
</td>

<td>
<span class="pw-status-badge <?php echo ($visit->visit_status=='Scheduled')?'pw-status-warning':'pw-status-pending'; ?>">
<?php echo esc_html($visit->visit_status); ?>
</span>
</td>

<td>
<input class="pw-input" type="text" name="comments" value="<?php echo esc_attr($visit->notes); ?>">

</td>

<td>

<button name="assign_visit" class="pw-btn pw-small-btn">Save</button>
</td>

</form>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<?php else: ?>
<div class="pw-success-box">No visits created yet. Assign package first.</div>
<?php endif; ?>

</div>

<?php endif; ?>

</div>
</div>