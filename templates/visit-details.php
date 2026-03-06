<?php
if (!is_user_logged_in()) return;

global $wpdb;

$visit_id = isset($_GET['visit_id']) ? intval($_GET['visit_id']) : 0;

if(!$visit_id){
echo "<div class='pw-success-box'>Invalid Visit</div>";
return;
}

/* ============================================
HANDLE VISIT SUBMISSION
============================================ */

if(isset($_POST['complete_visit'])){

$current_user = wp_get_current_user();
$engineer_id = $current_user->ID;

$visit_comment = sanitize_textarea_field($_POST['visit_comment']);
$actual_date = sanitize_text_field($_POST['actual_date']);

$photos=[];
$videos=[];

require_once(ABSPATH.'wp-admin/includes/file.php');

/* PHOTO UPLOAD */

if(!empty($_FILES['visit_photos']['name'][0])){

foreach($_FILES['visit_photos']['name'] as $key=>$value){

$file=[
'name'=>$_FILES['visit_photos']['name'][$key],
'type'=>$_FILES['visit_photos']['type'][$key],
'tmp_name'=>$_FILES['visit_photos']['tmp_name'][$key],
'error'=>$_FILES['visit_photos']['error'][$key],
'size'=>$_FILES['visit_photos']['size'][$key]
];

$upload=wp_handle_upload($file,['test_form'=>false]);

if(!isset($upload['error'])){
$photos[]=$upload['url'];
}

}

}

/* VIDEO UPLOAD */

if(!empty($_FILES['visit_videos']['name'][0])){

foreach($_FILES['visit_videos']['name'] as $key=>$value){

$file=[
'name'=>$_FILES['visit_videos']['name'][$key],
'type'=>$_FILES['visit_videos']['type'][$key],
'tmp_name'=>$_FILES['visit_videos']['tmp_name'][$key],
'error'=>$_FILES['visit_videos']['error'][$key],
'size'=>$_FILES['visit_videos']['size'][$key]
];

$upload=wp_handle_upload($file,['test_form'=>false]);

if(!isset($upload['error'])){
$videos[]=$upload['url'];
}

}

}

/* UPDATE VISIT */

$wpdb->update(
$wpdb->prefix.'pw_visits',
[
'visit_status'=>'Completed',
'visit_date'=>$actual_date,
'notes'=>$visit_comment,
'visit_photos'=>implode(',',$photos),
'visit_videos'=>implode(',',$videos)
],
['id'=>$visit_id]
);

/* =====================================
UPDATE PROPERTY SUBSCRIPTION STATUS
===================================== */

/* GET PROPERTY ID */

$property_id = $wpdb->get_var(
$wpdb->prepare(
"SELECT property_id 
FROM {$wpdb->prefix}pw_visits 
WHERE id=%d",
$visit_id
)
);


/* GET PACKAGE TYPE FROM SUBSCRIPTIONS */

$package = $wpdb->get_var(
$wpdb->prepare(
"SELECT package_type
FROM {$wpdb->prefix}pw_subscriptions
WHERE property_id=%d
ORDER BY id DESC
LIMIT 1",
$property_id
)
);

/* TOTAL VISITS BASED ON PACKAGE */

$total = 0;

if($package == 'monthly'){
$total = 12;
}
elseif($package == 'quarterly'){
$total = 4;
}
elseif($package == 'yearly'){
$total = 1;
}

/* COUNT COMPLETED VISITS */

$completed = $wpdb->get_var(
$wpdb->prepare(
"SELECT COUNT(*)
FROM {$wpdb->prefix}pw_visits
WHERE property_id=%d
AND visit_status='Completed'",
$property_id
)
);

/* GENERATE STATUS */

if($completed >= $total){

$status = "Subscription Completed";

}else{

$next = $completed + 1;

$status = "Visit ".$next."/".$total." Scheduled";

}

/* UPDATE PROPERTY TABLE */

$wpdb->update(
$wpdb->prefix.'pw_properties',
[
'subscription_status'=>$status
],
['id'=>$property_id]
);

echo "<script>window.location.href='".home_url('/update-visit?visit_id='.$visit_id)."';</script>";
exit;

}

/* ============================================
FETCH VISIT
============================================ */

$visit = $wpdb->get_row(
$wpdb->prepare("
SELECT v.*,p.property_name
FROM {$wpdb->prefix}pw_visits v
LEFT JOIN {$wpdb->prefix}pw_properties p
ON v.property_id=p.id
WHERE v.id=%d
",$visit_id)
);

if(!$visit){
echo "<div class='pw-success-box'>Visit not found</div>";
return;
}
?>

<div class="pw-rectangle">

<h2>Visit Details</h2>

<?php if($visit->visit_status == "Pending"): ?>

<div class="pw-success-box">
Visit Not Scheduled Yet
</div>

<?php elseif($visit->visit_status == "Scheduled"): ?>

<!-- VISIT FORM -->

<form method="post" enctype="multipart/form-data" class="pw-visit-form">

<div class="pw-form-row">

<div class="pw-field">
<label>Operation Comment</label>

<textarea readonly>
<?php echo esc_html($visit->notes); ?>
</textarea>

</div>

<div class="pw-field">
<label>Date of Visit</label>
<input type="date" name="actual_date" required>
</div>

</div>


<div class="pw-field">
<label>Visit Comment</label>
<textarea name="visit_comment" required></textarea>
</div>


<div class="pw-field">
<label>Upload Photos (Max 10)</label>
<input type="file" name="visit_photos[]" multiple accept="image/*" required>
</div>


<div class="pw-field">
<label>Upload Videos (Max 2)</label>
<input type="file" name="visit_videos[]" multiple accept="video/*">
</div>


<div class="pw-submit">
<button class="pw-btn" name="complete_visit">Complete Visit</button>
</div>

</form>

<?php elseif($visit->visit_status == "Completed"): ?>

<!-- VISIT MEDIA -->

<?php

$photos = array_values(array_filter(explode(',', $visit->visit_photos ?? '')));
$videos = array_values(array_filter(explode(',', $visit->visit_videos ?? '')));

$media=[];

foreach($photos as $p){
$media[]=['type'=>'image','src'=>$p];
}

foreach($videos as $v){
$media[]=['type'=>'video','src'=>$v];
}

?>

<?php if(!empty($media)): ?>

<div class="pw-gallery">

<button class="pw-arrow left" onclick="prevMedia()">❮</button>

<div id="mediaContainer"></div>

<button class="pw-arrow right" onclick="nextMedia()">❯</button>

</div>

<div class="pw-thumbs">

<?php foreach($media as $index=>$m): ?>

<?php if($m['type']=='image'): ?>

<img src="<?php echo esc_url($m['src']); ?>"
onclick="changeMedia(<?php echo $index; ?>)"
class="pw-thumb">

<?php else: ?>

<video class="pw-thumb"
onclick="changeMedia(<?php echo $index; ?>)">
<source src="<?php echo esc_url($m['src']); ?>">
</video>

<?php endif; ?>

<?php endforeach; ?>

</div>

<?php endif; ?>

<div class="pw-visit-report">

<h3>Visit Report</h3>

<p><?php echo nl2br(esc_html($visit->notes)); ?></p>

<p><strong>Date:</strong> <?php echo esc_html($visit->visit_date); ?></p>

</div>

<?php endif; ?>

</div>

<script>

let media=[
<?php
if(!empty($media)){
foreach($media as $m){
echo "{type:'".$m['type']."',src:'".esc_url($m['src'])."'},"; 
}
}
?>
];

let index=0;

const container=document.getElementById("mediaContainer");

function renderMedia(){

if(!container || media.length==0) return;

let item=media[index];

if(item.type==="image"){

container.innerHTML=`<img src="${item.src}" style="width:100%">`;

}else{

container.innerHTML=`<video controls style="width:100%">
<source src="${item.src}">
</video>`;

}

}

renderMedia();

function changeMedia(i){
index=i;
renderMedia();
}

function nextMedia(){
index++;
if(index>=media.length) index=0;
renderMedia();
}

function prevMedia(){
index--;
if(index<0) index=media.length-1;
renderMedia();
}

</script>