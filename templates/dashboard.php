<?php
if (!is_user_logged_in()) return;

global $wpdb;

$user_id = get_current_user_id();

/*
|--------------------------------------------------------------------------
| FETCH ACTIVE PROPERTIES
|--------------------------------------------------------------------------
*/

$properties = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_properties 
         WHERE user_id = %d 
         AND subscription_status IN 
         ('Active Subscription','Visit Scheduled','Completed')
         ORDER BY id DESC",
        $user_id
    )
);
?>

<h2>My Properties</h2>

<?php if (!empty($properties)) : ?>

<?php foreach ($properties as $prop) : ?>

<?php
/* ================= STATUS BADGE ================= */

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

/* ================= LATEST SUBSCRIPTION ================= */

$subscription = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_subscriptions 
         WHERE property_id = %d 
         ORDER BY id DESC LIMIT 1",
        $prop->id
    )
);

/* ================= UPCOMING VISIT ================= */

$upcoming_visit = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT v.*, u.display_name AS engineer_name
         FROM {$wpdb->prefix}pw_visits v
         LEFT JOIN {$wpdb->users} u ON v.engineer_id = u.ID
         WHERE v.property_id = %d
         ORDER BY v.visit_date ASC LIMIT 1",
        $prop->id
    )
);

/* ================= VISIT HISTORY ================= */

$visit_history = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT v.*, u.display_name AS engineer_name
         FROM {$wpdb->prefix}pw_visits v
         LEFT JOIN {$wpdb->users} u ON v.engineer_id = u.ID
         WHERE v.property_id = %d
         ORDER BY v.visit_date DESC LIMIT 5",
        $prop->id
    )
);
?>

<div class="pw-rectangle" style="margin-bottom:30px;">

    <h3><?php echo esc_html($prop->property_name); ?></h3>

    <div class="pw-grid-3">

        <div>
            <strong>Property ID</strong>
            <p><?php echo esc_html($prop->property_code); ?></p>
        </div>

        <div>
            <strong>Location</strong>
            <p><?php echo esc_html($prop->location_name); ?></p>
        </div>

        <div>
            <strong>Status</strong>
            <p>
                <span class="pw-status-badge <?php echo esc_attr($status_class); ?>">
                    <?php echo esc_html($prop->subscription_status); ?>
                </span>
            </p>
        </div>

    </div>

    <?php if ($subscription) : ?>

    <hr>

    <h4>Subscription Details</h4>

    <div class="pw-grid-3">

        <div>
            <strong>Package</strong>
            <p><?php echo esc_html($subscription->package_type); ?></p>
        </div>

        <div>
            <strong>Start Date</strong>
            <p><?php echo esc_html(date('d-m-Y', strtotime($subscription->start_date))); ?></p>
        </div>

        <div>
            <strong>End Date</strong>
            <p><?php echo esc_html(date('d-m-Y', strtotime($subscription->end_date))); ?></p>
        </div>

    </div>

    <?php endif; ?>


    <?php if ($upcoming_visit) : ?>

    <hr>

    <h4>Next Visit</h4>

    <div class="pw-grid-3">

        <div>
            <strong>Visit Date</strong>
            <p><?php echo esc_html(date('d-m-Y', strtotime($upcoming_visit->visit_date))); ?></p>
        </div>

        <div>
            <strong>Engineer</strong>
            <p><?php echo esc_html($upcoming_visit->engineer_name); ?></p>
        </div>

        <div>
            <strong>Status</strong>
            <p><?php echo esc_html($upcoming_visit->status); ?></p>
        </div>

    </div>

    <?php if (!empty($upcoming_visit->report_file)) : ?>
        <p>
            <strong>Report:</strong> 
            <a href="<?php echo esc_url($upcoming_visit->report_file); ?>" target="_blank">
                View Report
            </a>
        </p>
    <?php endif; ?>

    <?php endif; ?>


    <?php if (!empty($visit_history)) : ?>

    <hr>

    <h4>Recent Visits</h4>

    <table class="pw-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Engineer</th>
                <th>Status</th>
                <th>Report</th>
            </tr>
        </thead>
        <tbody>

        <?php foreach ($visit_history as $visit) : ?>

            <tr>
                <td><?php echo esc_html(date('d-m-Y', strtotime($visit->visit_date))); ?></td>
                <td><?php echo esc_html($visit->engineer_name); ?></td>
                <td><?php echo esc_html($visit->status); ?></td>
                <td>
                    <?php if (!empty($visit->report_file)) : ?>
                        <a href="<?php echo esc_url($visit->report_file); ?>" target="_blank">
                            View
                        </a>
                    <?php else : ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

    <?php endif; ?>

</div>

<?php endforeach; ?>

<?php else : ?>

<div class="pw-success-box">
    No active properties found.
</div>

<?php endif; ?>