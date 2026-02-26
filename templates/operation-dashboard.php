<?php
if (!is_user_logged_in()) return;

$user = wp_get_current_user();
if (!in_array('operation_member', $user->roles)) wp_die('Unauthorized');

global $wpdb;

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'new';

/* =====================================================
   FETCH DATA BASED ON TAB
===================================================== */

if ($tab === 'new') {

    $rows = $wpdb->get_results(
        "SELECT p.*, u.display_name AS customer_name 
         FROM {$wpdb->prefix}pw_properties p
         LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
         WHERE p.subscription_status = 'Pending Package Assignment'
         ORDER BY p.id DESC"
    );

    $heading = "New Properties";
}
elseif ($tab === 'assigned') {

    $rows = $wpdb->get_results(
        "SELECT p.*, u.display_name AS customer_name 
         FROM {$wpdb->prefix}pw_properties p
         LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
         WHERE p.subscription_status IN ('Package Assigned','Active Subscription','Visit Scheduled')
         ORDER BY p.id DESC"
    );

    $heading = "Assigned Properties";
}
else {

    $rows = $wpdb->get_results(
        "SELECT p.*, u.display_name AS customer_name 
         FROM {$wpdb->prefix}pw_properties p
         LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
         WHERE p.subscription_status = 'Completed'
         ORDER BY p.id DESC"
    );

    $heading = "Completed Properties";
}

?>

<h2><?php echo esc_html($heading); ?></h2>

<?php if ($rows): ?>

<table class="pw-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Property</th>
            <th>Location</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>

    <?php foreach ($rows as $row): ?>

        <tr>
            <td><?php echo esc_html($row->property_code); ?></td>
            <td><?php echo esc_html($row->customer_name); ?></td>
            <td><?php echo esc_html($row->property_name); ?></td>
            <td><?php echo esc_html($row->location_name); ?></td>

            <td>
                <?php
                $status_class = 'pw-badge pw-pending';

                if ($row->subscription_status === 'Active Subscription')
                    $status_class = 'pw-badge pw-active';

                if ($row->subscription_status === 'Visit Scheduled')
                    $status_class = 'pw-badge pw-warning';

                if ($row->subscription_status === 'Completed')
                    $status_class = 'pw-badge pw-active';
                ?>

                <span class="<?php echo $status_class; ?>">
                    <?php echo esc_html($row->subscription_status); ?>
                </span>
            </td>

            <td>

                <?php if ($tab === 'new'): ?>

                    <!-- ASSIGN PACKAGE MODAL -->
                    <button class="pw-small-btn"
                        onclick="openModal(`
                            <h3><?php echo esc_js($row->property_name); ?></h3>
                            <p><strong>Customer:</strong> <?php echo esc_js($row->customer_name); ?></p>
                            <p><strong>Location:</strong> <?php echo esc_js($row->location_name); ?></p>
                            <hr>

                            <form method='post'>
                                <?php wp_nonce_field('pw_assign_package_nonce'); ?>
                                <input type='hidden' name='pw_assign_package' value='1'>
                                <input type='hidden' name='property_id' value='<?php echo $row->id; ?>'>

                                <label>Package</label>
                                <select name='package_type' required>
                                    <option value=''>Select Package</option>
                                    <option value='Monthly Monitoring'>Monthly Monitoring</option>
                                    <option value='Quarterly Monitoring'>Quarterly Monitoring</option>
                                    <option value='Yearly Monitoring'>Yearly Monitoring</option>
                                </select>

                                <label>Start Date</label>
                                <input type='date' name='start_date' required>

                                <label>End Date</label>
                                <input type='date' name='end_date' required>

                                <label>Cost</label>
                                <input type='number' step='0.01' name='cost' required>

                                <br><br>
                                <button class='pw-small-btn'>Assign Package</button>
                            </form>
                        `)">
                        Assign
                    </button>

                <?php else: ?>

                    <!-- VIEW ONLY -->
                    <button class="pw-small-btn"
                        onclick="openModal(`
                            <h3><?php echo esc_js($row->property_name); ?></h3>
                            <p><strong>Customer:</strong> <?php echo esc_js($row->customer_name); ?></p>
                            <p><strong>Location:</strong> <?php echo esc_js($row->location_name); ?></p>
                            <p><strong>Status:</strong> <?php echo esc_js($row->subscription_status); ?></p>
                        `)">
                        View
                    </button>

                <?php endif; ?>

            </td>
        </tr>

    <?php endforeach; ?>

    </tbody>
</table>

<?php else: ?>

<p>No records found.</p>

<?php endif; ?>