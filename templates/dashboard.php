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
   VIEW SINGLE PROPERTY
===================================================== */

if (isset($_GET['view'])):

$property_id = intval($_GET['view']);

$prop = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND user_id = %d",
        $property_id,
        $user_id
    )
);

if ($prop):
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

<a href="<?php echo home_url('/customer-dashboard?tab=my-properties'); ?>" 
   class="pw-small-btn" 
   style="margin-left:10px;">
   Back
</a>

</form>

</div>

<?php
endif;
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

if ($prop->subscription_status === 'Active Subscription') {
    $status_class = 'pw-status-active';
}
elseif ($prop->subscription_status === 'Visit Scheduled') {
    $status_class = 'pw-status-warning';
}
elseif ($prop->subscription_status === 'Completed') {
    $status_class = 'pw-status-completed';
}
?>

<a href="<?php echo home_url('/customer-dashboard?tab=my-properties&view=' . $prop->id); ?>" 
   class="pw-property-summary-card">

    <div class="pw-card-top">
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