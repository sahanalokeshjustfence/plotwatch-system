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
p.address as full_address
FROM {$wpdb->prefix}pw_visits v
LEFT JOIN {$wpdb->prefix}pw_properties p
ON v.property_id=p.id
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

<div class="pw-grid-3">

<div>
<label>Property ID</label>
<input type="text" value="<?php echo esc_attr($current_visit->property_code); ?>" readonly>
</div>

<div>
<label>Property Name</label>
<input type="text" value="<?php echo esc_attr($current_visit->property_name); ?>" readonly>
</div>

<div class="full">

<label>Full Address</label>

<input type="text" value="<?php echo esc_attr($current_visit->full_address); ?>" readonly>

<?php if(!empty($current_visit->google_map)): ?>

<a href="<?php echo esc_url($current_visit->google_map); ?>" target="_blank">
📍 View Location
</a>

<?php endif; ?>

</div>

<div>
<label>Visit Date</label>
<input type="text" value="<?php echo esc_attr($current_visit->visit_date); ?>" readonly>
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