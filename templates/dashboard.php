<?php
if (!is_user_logged_in()) return;

global $wpdb;
$user_id = get_current_user_id();
$table = $wpdb->prefix . 'pw_properties';


/* =====================================================
   UPDATE PROPERTY
===================================================== */

if (isset($_POST['pw_update_property'])) {

    $wpdb->update(
        $table,
        [
            'property_name'   => sanitize_text_field($_POST['property_name']),
            'location_name'   => sanitize_text_field($_POST['location_name']),
            'plot_size'       => sanitize_text_field($_POST['plot_size']),
            'property_type'   => sanitize_text_field($_POST['property_type']),
            'contact_person'  => sanitize_text_field($_POST['contact_person']),
            'contact_number'  => sanitize_text_field($_POST['contact_number']),
            'address'         => sanitize_textarea_field($_POST['address']),
            'google_map'      => sanitize_text_field($_POST['google_map']),
        ],
        ['id' => intval($_POST['property_id'])]
    );

    echo "<script>location.reload();</script>";
}


/* =====================================================
   MODIFY PROPERTY PAGE
===================================================== */

if (isset($_GET['view']) && isset($_GET['modify'])):

$property_id = intval($_GET['view']);

$prop = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND user_id = %d",
        $property_id,
        $user_id
    )
);

if (!$prop) return;
?>

<div class="pw-property-detail-card">

<h2>Property Details</h2>

<form method="post">

<input type="hidden" name="property_id" value="<?php echo $prop->id; ?>">

<div class="pw-grid-3">

<div>
<label>Property ID</label>
<input type="text" value="<?php echo esc_attr($prop->property_code ?: $prop->id); ?>" disabled>
</div>

<div>
<label>Property Name</label>
<input type="text" name="property_name" value="<?php echo esc_attr($prop->property_name); ?>">
</div>

<div>
<label>Location Name</label>
<input type="text" name="location_name" value="<?php echo esc_attr($prop->location_name); ?>">
</div>

<div>
<label>Plot Size</label>
<input type="text" name="plot_size" value="<?php echo esc_attr($prop->plot_size); ?>">
</div>

<div>
<label>Property Type</label>
<input type="text" name="property_type" value="<?php echo esc_attr($prop->property_type); ?>">
</div>

<div>
<label>Contact Person</label>
<input type="text" name="contact_person" value="<?php echo esc_attr($prop->contact_person); ?>">
</div>

<div>
<label>Contact Number</label>
<input type="text" name="contact_number" value="<?php echo esc_attr($prop->contact_number); ?>">
</div>

<div class="full">
<label>Full Address</label>
<textarea name="address"><?php echo esc_textarea($prop->address); ?></textarea>
</div>

<div class="full">
<label>Google Map</label>
<input type="text" name="google_map" value="<?php echo esc_attr($prop->google_map); ?>">
</div>

</div>

<button type="submit" name="pw_update_property" class="pw-btn">
Update Property
</button>

<a href="<?php echo home_url('/customer-dashboard?tab=my-properties&view='.$prop->id); ?>" 
class="pw-small-btn">
Back
</a>

</form>

</div>

<?php
return;
endif;



/* =====================================================
   VIEW SINGLE PROPERTY (VISIT CARDS)
===================================================== */

if (isset($_GET['view']) && !isset($_GET['modify'])):

$property_id = intval($_GET['view']);

$prop = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND user_id = %d",
        $property_id,
        $user_id
    )
);

if (!$prop) return;

/* ================= FETCH VISITS ================= */

$visits = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_visits 
         WHERE property_id=%d 
         ORDER BY visit_date ASC",
        $property_id
    )
);
?>

<div class="pw-property-detail-card">

<h2><?php echo esc_html($prop->property_name); ?></h2>

<div class="pw-property-actions">

<!-- PROPERTY MODIFY CARD -->

<a href="<?php echo home_url('/customer-dashboard?tab=my-properties&view=' . $prop->id . '&modify=1'); ?>"
class="pw-action-card pw-card-property">

Modify Property

</a>

<?php if($visits): ?>

<?php foreach($visits as $i=>$visit):

$card='pw-card-pending';
$clickable=false;

/* STATUS COLORS */

if($visit->visit_status=='Completed'){
$card='pw-card-completed';
$clickable=true;
}

elseif($visit->visit_status=='Scheduled'){
$card='pw-card-scheduled';
}

elseif($visit->visit_status=='Pending'){

$previous_completed=true;

foreach($visits as $check){

if($check->id == $visit->id){
break;
}

if($check->visit_status!='Completed'){
$previous_completed=false;
break;
}

}

if($previous_completed){
$card='pw-card-next';
}

}

?>

<?php if($clickable): ?>

<a class="pw-action-card <?php echo $card;?>"
href="<?php echo home_url('/visit-reports?visit_id='.$visit->id); ?>">

Visit <?php echo $i+1; ?><br>

<?php echo esc_html($visit->visit_status); ?><br>

<?php echo esc_html($visit->visit_date); ?>

</a>

<?php else: ?>

<div class="pw-action-card <?php echo $card;?>">

Visit <?php echo $i+1; ?><br>

<?php echo esc_html($visit->visit_status); ?><br>

<?php echo esc_html($visit->visit_date); ?>

</div>

<?php endif; ?>

<?php endforeach; ?>

<?php endif; ?>

</div>

<a href="<?php echo home_url('/customer-dashboard?tab=my-properties'); ?>" 
class="pw-small-btn">
Back
</a>

</div>

<?php
return;
endif;



/* =====================================================
   FETCH PROPERTIES (CARD VIEW)
===================================================== */

$properties = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d ORDER BY id DESC",
        $user_id
    )
);
?>

<h2 class="pw-page-title">My Properties</h2>

<?php if (!empty($properties)) : ?>

<div class="pw-property-card-grid">

<?php foreach ($properties as $prop) :

$status_class = 'pw-status-pending';

switch ($prop->subscription_status) {

    case 'Visits Created':
        $status_class = 'pw-status-warning';
        break;

    case 'Visit Assigned':
        $status_class = 'pw-status-active';
        break;

    case 'Subscription Completed':
        $status_class = 'pw-status-completed';
        break;

    default:
        $status_class = 'pw-status-pending';
}

$subscription = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT start_date, end_date 
         FROM {$wpdb->prefix}pw_subscriptions
         WHERE property_id = %d
         ORDER BY id DESC LIMIT 1",
        $prop->id
    )
);

$subscription_badge = '';
$subscription_class = '';

if ($subscription && $subscription->start_date && $subscription->end_date) {

    $today = date('Y-m-d');

    if ($today >= $subscription->start_date && $today <= $subscription->end_date) {
    $subscription_badge = '● Active';
    $subscription_class = 'pw-sub-active';
} elseif ($today > $subscription->end_date) {
    $subscription_badge = '● Expired';
    $subscription_class = 'pw-sub-expired';
}
}
?>

<a href="<?php echo home_url('/customer-dashboard?tab=my-properties&view=' . $prop->id); ?>" 
class="pw-property-summary-card <?php echo esc_attr($subscription_class); ?>">

<div class="pw-card-top">

<?php if (!empty($subscription_badge)) : ?>
<span class="pw-sub-status <?php echo esc_attr($subscription_class); ?>">
<?php echo esc_html($subscription_badge); ?>
</span>
<?php endif; ?>

<span class="pw-property-id">
ID: <?php echo esc_html($prop->property_code ?: $prop->id); ?>
</span>

<span class="pw-status-badge <?php echo esc_attr($status_class); ?>">
<?php echo esc_html($prop->subscription_status); ?>
</span>

</div>

<h3><?php echo esc_html($prop->property_name); ?></h3>

<p class="pw-location">
<?php echo esc_html($prop->location_name); ?>
</p>

</a>

<?php endforeach; ?>

</div>

<?php else : ?>

<div class="pw-success-box">
No active properties found.
</div>

<?php endif; ?>