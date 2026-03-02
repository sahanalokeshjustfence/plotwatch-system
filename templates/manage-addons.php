<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();
if (!in_array('administrator', $user->roles) &&
    !in_array('operation_member', $user->roles)) {
    wp_die('Unauthorized');
}

global $wpdb;
$table = $wpdb->prefix . 'pw_addons';

/* ================= DELETE ================= */

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $wpdb->delete($table, ['id' => $delete_id]);
    echo "<div class='pw-success-box'>Add-on Deleted Successfully</div>";
}

/* ================= SAVE / UPDATE ================= */

if (isset($_POST['pw_save_addon'])) {

    $name    = sanitize_text_field($_POST['addon_name']);
    $desc    = sanitize_textarea_field($_POST['addon_description']);
    $edit_id = intval($_POST['edit_id']);

    if ($edit_id > 0) {

        $wpdb->update($table, [
            'name' => $name,
            'description' => $desc
        ], ['id' => $edit_id]);

        echo "<div class='pw-success-box'>Add-on Updated Successfully</div>";

    } else {

        $wpdb->insert($table, [
            'name' => $name,
            'description' => $desc
        ]);

        echo "<div class='pw-success-box'>Add-on Added Successfully</div>";
    }
}

/* ================= SEARCH ================= */

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

/* ================= WORDPRESS PAGINATION FIX ================= */

$page = max(1, get_query_var('paged'));
$per_page = 5;
$offset   = ($page - 1) * $per_page;

$where = "WHERE 1=1";

if (!empty($search)) {
    $like = '%' . $wpdb->esc_like($search) . '%';
    $where .= $wpdb->prepare(" AND name LIKE %s", $like);
}

/* ================= FETCH DATA ================= */

$total = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");
$total_pages = ceil($total / $per_page);

$addons = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table $where ORDER BY id DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);
?>

<div class="pw-manage-card">

<h2 style="margin-bottom:30px;">Manage Add-ons</h2>

<!-- SEARCH + ADD -->
<div class="pw-manage-toolbar">

<form method="get" class="pw-manage-search">
    <input type="text"
           name="search"
           placeholder="Search Add-on"
           value="<?php echo esc_attr($search); ?>">
</form>

<button type="button"
        id="addAddonBtn"
        class="pw-btn-red">
+ Add Add-on
</button>

</div>

<!-- TABLE -->
<table class="pw-manage-table">
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Description</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php if ($addons): ?>
<?php foreach ($addons as $addon): ?>
<tr>
<td><?php echo $addon->id; ?></td>
<td><?php echo esc_html($addon->name); ?></td>
<td><?php echo esc_html($addon->description); ?></td>
<td class="pw-action-cell">

<button type="button"
        class="pw-action-btn edit-btn"
        data-id="<?php echo $addon->id; ?>"
        data-name="<?php echo esc_attr($addon->name); ?>"
        data-desc="<?php echo esc_attr($addon->description); ?>">
Edit
</button>

<a href="<?php echo esc_url(add_query_arg('delete',$addon->id)); ?>"
   class="pw-action-btn delete-btn"
   onclick="return confirm('Delete this add-on?')">
Delete
</a>

</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="4">No Add-ons Found</td>
</tr>
<?php endif; ?>
</tbody>
</table>

<!-- PAGINATION (WP CORRECT VERSION) -->
<?php if ($total_pages > 1): ?>
<div class="pw-manage-pagination">
<?php for ($i=1; $i <= $total_pages; $i++): ?>
<a class="<?php echo ($i==$page)?'active':''; ?>"
   href="<?php echo esc_url( get_pagenum_link($i) . (!empty($search) ? '?search='.$search : '') ); ?>">
<?php echo $i; ?>
</a>
<?php endfor; ?>
</div>
<?php endif; ?>

</div>

<!-- MODAL -->
<div id="addonModal" class="pw-modal">
<div class="pw-modal-box">

<h3 id="modalTitle">Add Add-on</h3>

<form method="post">

<input type="hidden" name="edit_id" id="edit_id" value="0">

<input type="text"
       name="addon_name"
       id="addon_name"
       placeholder="Add-on Name"
       required>

<textarea name="addon_description"
          id="addon_description"
          placeholder="Description"></textarea>

<div style="margin-top:15px;">
<button type="submit"
        name="pw_save_addon"
        class="pw-btn-red">
Save
</button>

<button type="button"
        id="closeModalBtn"
        class="pw-btn-light">
Cancel
</button>
</div>

</form>

</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){

    const modal = document.getElementById("addonModal");
    const title = document.getElementById("modalTitle");
    const editId = document.getElementById("edit_id");
    const nameField = document.getElementById("addon_name");
    const descField = document.getElementById("addon_description");

    document.getElementById("addAddonBtn").addEventListener("click", function(){
        editId.value = 0;
        nameField.value = "";
        descField.value = "";
        title.innerText = "Add Add-on";
        modal.style.display = "flex";
    });

    document.querySelectorAll(".edit-btn").forEach(function(btn){
        btn.addEventListener("click", function(){
            editId.value = this.dataset.id;
            nameField.value = this.dataset.name;
            descField.value = this.dataset.desc;
            title.innerText = "Edit Add-on";
            modal.style.display = "flex";
        });
    });

    document.getElementById("closeModalBtn").addEventListener("click", function(){
        modal.style.display = "none";
    });

    window.addEventListener("click", function(e){
        if(e.target === modal){
            modal.style.display = "none";
        }
    });

});
</script>