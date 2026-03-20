<?php
if (!is_user_logged_in()) return;

global $wpdb;

$visit_id = intval($_GET['visit_id'] ?? 0);

if(!$visit_id){
echo "<div class='pw-error'>Visit not found</div>";
return;
}

$visit = $wpdb->get_row(
$wpdb->prepare(
"SELECT v.*,p.property_name,p.property_code,
u.display_name AS engineer
FROM {$wpdb->prefix}pw_visits v
LEFT JOIN {$wpdb->prefix}pw_properties p
ON v.property_id=p.id
LEFT JOIN {$wpdb->users} u
ON v.engineer_id=u.ID
WHERE v.id=%d",
$visit_id
)
);

if(!$visit){
echo "<div class='pw-error'>Visit not found</div>";
return;
}

$photos = array_filter(explode(',', $visit->visit_photos ?? ''));
$videos = array_filter(explode(',', $visit->visit_videos ?? ''));
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<div class="pw-report-container">

<!-- PROPERTY HEADER -->

<div class="pw-report-header">
<h2><?php echo esc_html($visit->property_name); ?></h2>
<p class="pw-property-id">
Property ID : <?php echo esc_html($visit->property_code); ?>
</p>
</div>


<!-- GALLERY -->

<div class="pw-gallery">

<div class="swiper pw-swiper">

<div class="swiper-wrapper">

<?php foreach($photos as $p): ?>

<div class="swiper-slide">
<img src="<?php echo esc_url($p); ?>">
</div>

<?php endforeach; ?>


<?php foreach($videos as $v): ?>

<div class="swiper-slide">
<video controls>
<source src="<?php echo esc_url($v); ?>">
</video>
</div>

<?php endforeach; ?>

</div>

<!-- arrows -->
<div class="swiper-button-next"></div>
<div class="swiper-button-prev"></div>

<!-- dots -->
<div class="swiper-pagination"></div>

</div>

</div>


<!-- VISIT DETAILS -->

<div class="pw-visit-details">

<div class="pw-detail">
<label>Engineer</label>
<p><?php echo esc_html($visit->engineer); ?></p>
</div>

<div class="pw-detail">
<label>Date</label>
<p><?php echo esc_html($visit->visit_date); ?></p>
</div>


</div>


<!-- COMMENT -->

<div class="pw-comment">

<label>Engineer Comments</label>

<p>
<?php echo nl2br(esc_html($visit->visit_comment)); ?>
</p>

</div>
<div class="pw-inspection-report">

<h3>Inspection Summary</h3>

<div class="pw-report-grid">

<div>
<label>Inspection Status</label>
<span><?php echo esc_html($visit->inspection_status); ?></span>
</div>

<div>
<label>Surrounding Activity</label>
<span><?php echo esc_html($visit->surrounding_activity); ?></span>
</div>

<div>
<label>Security Risk</label>
<span><?php echo esc_html($visit->security_risk); ?></span>
</div>

<div>
<label>Boundary Security</label>
<span><?php echo esc_html($visit->boundary_security); ?></span>
</div>

<div>
<label>Encroachment</label>
<span><?php echo esc_html($visit->encroachment_check); ?></span>
</div>

</div>


</div>


<script>

new Swiper('.pw-swiper',{

loop:true,

navigation:{
nextEl:'.swiper-button-next',
prevEl:'.swiper-button-prev'
},

pagination:{
el:'.swiper-pagination',
clickable:true
}

});

</script>


<style>

.pw-report-container{
width:100%;
max-width:1100px;
margin:10px auto 40px auto;
background:#ffffff;
border-radius:8px;
box-shadow:0 8px 25px rgba(0,0,0,0.08);
padding:30px;
}
.pw-content{
padding-top:10px;
}

/* HEADER */

.pw-report-header{
text-align:center;
margin-bottom:25px;
}

.pw-report-header h2{
font-size:30px;
margin:0;
font-weight:700;
}

.pw-property-id{
margin-top:6px;
font-size:15px;
color:#777;
}

/* GALLERY */

.pw-gallery{
width:100%;
margin-top:20px;
}

.pw-swiper{
width:100%;
}

.pw-gallery img,
.pw-gallery video{
width:100%;
max-height:500px;
object-fit:contain;
border-radius:6px;
background:#f5f5f5;
}
.swiper-slide{
display:flex;
align-items:center;
justify-content:center;
background:#f5f5f5;
}

/* SWIPER DOTS */

.swiper-pagination{
margin-top:12px;
text-align:center;
}

.swiper-pagination-bullet{
width:10px;
height:10px;
background:#bbb;
opacity:1;
}

.swiper-pagination-bullet-active{
background:#1e73be;
transform:scale(1.2);
}

/* DETAILS ROW */

.pw-visit-details{
display:flex;
justify-content:space-between;
margin-top:25px;
padding-top:15px;
border-top:1px solid #eee;
}

.pw-detail{
flex:1;
}

.pw-detail label{
font-size:13px;
color:#888;
display:block;
}

.pw-detail p{
font-size:16px;
font-weight:600;
margin-top:5px;
}

/* COMMENT */

.pw-comment{
margin-top:25px;
background:#f7f7f7;
padding:18px;
border-radius:6px;
}

.pw-comment label{
font-size:13px;
color:#666;
display:block;
margin-bottom:5px;
}

.pw-comment p{
font-size:15px;
line-height:1.6;
}

/* MOBILE */

@media(max-width:768px){

.pw-report-container{
padding:20px;
}

.pw-gallery img,
.pw-gallery video{
height:260px;
}

.pw-visit-details{
flex-direction:column;
gap:12px;
}

}

.swiper-wrapper{
width:100%;
}

.swiper-slide{
width:100% !important;
display:flex;
justify-content:center;
}

</style>