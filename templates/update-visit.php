<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();

if (!in_array('engineer', (array) $user->roles)) {
    wp_die('Unauthorized');
}

global $wpdb;

$visit_id = isset($_GET['visit_id']) ? intval($_GET['visit_id']) : 0;

if (!$visit_id) {
    echo "<div class='pw-success-box'>Invalid Visit</div>";
    return;
}

/* FETCH VISIT */

$current_visit = $wpdb->get_row(
$wpdb->prepare(
"SELECT v.*, 
p.property_name,
p.location_name,
p.property_code,
p.google_map,
p.address as full_address,
p.contact_person,
p.contact_number,
p.property_type,
p.plot_size,
u.user_email as email
FROM {$wpdb->prefix}pw_visits v
LEFT JOIN {$wpdb->prefix}pw_properties p
ON v.property_id=p.id
LEFT JOIN {$wpdb->users} u
ON p.user_id = u.ID
WHERE v.id=%d",
$visit_id
)
);

if(!$current_visit){
echo "<div class='pw-success-box'>Visit Not Found</div>";
return;
}

/* FETCH ALL VISITS */

$all_visits = $wpdb->get_results(
$wpdb->prepare(
"SELECT *
FROM {$wpdb->prefix}pw_visits
WHERE property_id=%d
ORDER BY visit_date ASC",
$current_visit->property_id
)
);
?>

<div class="pw-rectangle">

<h2>Update Visit</h2>

<!-- PROPERTY DETAILS -->

<!-- PROPERTY HEADER FULL DETAILS -->

<div class="pw-header-card">

<h2><?php echo esc_html($current_visit->property_name); ?></h2>

<div class="pw-sub-id">
Property ID: <?php echo esc_html($current_visit->property_code); ?>
</div>

<div class="pw-header-grid">

<div>
<label>Contact Person</label>
<span><?php echo esc_html($current_visit->contact_person ?? ''); ?></span>
</div>

<div>
<label>Contact Number</label>
<span><?php echo esc_html($current_visit->contact_number ?? ''); ?></span>
</div>

<div>
<label>Email</label>
<span><?php echo esc_html($current_visit->email ?? ''); ?></span>
</div>

<div>
<label>Property Type</label>
<span><?php echo esc_html($current_visit->property_type ?? ''); ?></span>
</div>

<div>
<label>Plot Size</label>
<span><?php echo esc_html($current_visit->plot_size ?? ''); ?></span>
</div>

<div>
<label>Location</label>
<span><?php echo esc_html($current_visit->location_name); ?></span>
</div>

<div style="grid-column: span 3;">
<label>Address</label>
<span><?php echo esc_html($current_visit->full_address); ?></span>
</div>

<?php if(!empty($current_visit->google_map)): ?>
<div style="grid-column: span 3;">
<label>Google Map</label>
<a href="<?php echo esc_url($current_visit->google_map); ?>" target="_blank">
View Location
</a>
</div>
<?php endif; ?>

<!-- 🔥 NEW FIELD -->
<div>
<label>Visit Date</label>
<span><?php echo esc_html($current_visit->visit_date); ?></span>
</div>

</div>
</div>

<hr>

<!-- VISIT CARDS -->

<div class="pw-visit-cards">

<?php foreach($all_visits as $index=>$v):

$card='pw-card-pending';

/* COMPLETED VISIT */

if($v->visit_status=='Completed'){
$card='pw-card-completed';
}

/* SCHEDULED VISIT */

elseif($v->visit_status=='Scheduled'){
$card='pw-card-scheduled';
}

/* NEXT VISIT LOGIC */

if($v->visit_status=='Pending'){

$previous_completed=true;

foreach($all_visits as $check){

if($check->id == $v->id){
break;
}

if($check->visit_status != 'Completed'){
$previous_completed=false;
break;
}

}

if($previous_completed){
$card='pw-card-next';
}

}

?>

<a class="pw-visit-card <?php echo esc_attr($card);?>"
href="<?php echo esc_url(home_url('/visit-details?visit_id='.$v->id)); ?>">

Visit <?php echo esc_html($index+1); ?>

<br>

<?php echo esc_html($v->visit_status); ?>

</a>

<?php endforeach; ?>

</div>

</div>