<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();
if (!in_array('administrator', $user->roles) &&
    !in_array('operation_member', $user->roles)) {
    wp_die('Unauthorized');
}

global $wpdb;

/* ================= SAVE ADDON ================= */

if (isset($_POST['pw_add_addon'])) {

    $name  = sanitize_text_field($_POST['addon_name']);
    $price = floatval($_POST['addon_price']);

    if ($name && $price >= 0) {

        $wpdb->insert(
            $wpdb->prefix . 'pw_addons',
            array(
                'name'  => $name,
                'price' => $price
            )
        );

        echo "<div class='pw-success-box'>Addon Added Successfully</div>";
    }
}

/* ================= FETCH ADDONS ================= */

$addons = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}pw_addons ORDER BY id DESC"
);
?>

<div class="pw-rectangle">

<h2>Manage Add-ons</h2>

<form method="post" class="pw-grid-3">

<div>
<label>Add-on Name</label>
<input type="text" name="addon_name" required>
</div>

<div>
<label>Price</label>
<input type="number" step="0.01" name="addon_price" required>
</div>

<div style="align-self:end;">
<button type="submit" name="pw_add_addon" class="pw-btn">
Add Add-on
</button>
</div>

</form>

<hr style="margin:40px 0;">

<h3>Existing Add-ons</h3>

<table class="pw-table">
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Price</th>
</tr>
</thead>

<tbody>

<?php if ($addons): ?>
<?php foreach ($addons as $addon): ?>

<tr>
<td><?php echo esc_html($addon->id); ?></td>
<td><?php echo esc_html($addon->name); ?></td>
<td>₹<?php echo esc_html($addon->price); ?></td>
</tr>

<?php endforeach; ?>
<?php else: ?>

<tr>
<td colspan="3">No Add-ons Found</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>