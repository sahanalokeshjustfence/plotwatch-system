<?php
if (!is_user_logged_in()) return;

global $wpdb;
$user_id = get_current_user_id();

/*
|--------------------------------------------------------------------------
| FETCH ACTIVE PROPERTIES ONLY
|--------------------------------------------------------------------------
*/

$properties = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pw_properties 
         WHERE user_id = %d 
         AND subscription_status IN 
         ('Active Subscription','Visit Scheduled','Visit Completed')
         ORDER BY id DESC",
        $user_id
    )
);
?>

<h2>Dashboard</h2>

<div class="pw-grid">

<?php if ($properties): ?>
<?php foreach ($properties as $prop): ?>

    <?php
    // Status Badge Class
    $status_class = 'pw-pending';

    if ($prop->subscription_status == 'Active Subscription') {
        $status_class = 'pw-active';
    }
    elseif ($prop->subscription_status == 'Visit Scheduled') {
        $status_class = 'pw-warning';
    }
    elseif ($prop->subscription_status == 'Visit Completed') {
        $status_class = 'pw-active';
    }

    // Fetch latest subscription
    $subscription = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pw_subscriptions 
             WHERE property_id = %d 
             ORDER BY id DESC LIMIT 1",
            $prop->id
        )
    );

    // Fetch latest visit log
    $visit = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pw_property_logs 
             WHERE property_id = %d 
             ORDER BY id DESC LIMIT 1",
            $prop->id
        )
    );
    ?>

    <div class="pw-card">

        <h3><?php echo esc_html($prop->property_name); ?></h3>

        <p><strong>Property ID:</strong> 
            <?php echo esc_html($prop->property_code); ?>
        </p>

        <p>
            <span class="pw-badge <?php echo $status_class; ?>">
                <?php echo esc_html($prop->subscription_status); ?>
            </span>
        </p>

        <?php if ($subscription): ?>
            <p><strong>Package:</strong> 
                <?php echo esc_html($subscription->package_type); ?>
            </p>

            <p><strong>Start:</strong> 
                <?php echo esc_html($subscription->start_date); ?>
            </p>

            <p><strong>End:</strong> 
                <?php echo esc_html($subscription->end_date); ?>
            </p>
        <?php endif; ?>

        <?php if ($visit): ?>
            <p><strong>Last Visit:</strong> 
                <?php echo esc_html($visit->created_at); ?>
            </p>
        <?php endif; ?>

        <button class="pw-small-btn"
        onclick="openModal(`
            <h2><?php echo esc_html($prop->property_name); ?></h2>
            <p><strong>ID:</strong> <?php echo esc_html($prop->property_code); ?></p>
            <p><strong>Status:</strong> <?php echo esc_html($prop->subscription_status); ?></p>
            <hr>
            <h3>Property Details</h3>
            <p><strong>Location:</strong> <?php echo esc_html($prop->location_name); ?></p>
            <p><strong>Address:</strong> <?php echo esc_html($prop->address); ?></p>
            <p><strong>Plot Size:</strong> <?php echo esc_html($prop->plot_size); ?></p>
            <hr>
            <?php if ($subscription): ?>
                <h3>Subscription</h3>
                <p><strong>Package:</strong> <?php echo esc_html($subscription->package_type); ?></p>
                <p><strong>Start Date:</strong> <?php echo esc_html($subscription->start_date); ?></p>
                <p><strong>End Date:</strong> <?php echo esc_html($subscription->end_date); ?></p>
                <p><strong>Cost:</strong> ₹<?php echo esc_html($subscription->cost); ?></p>
            <?php endif; ?>
            <?php if ($visit): ?>
                <hr>
                <h3>Latest Visit</h3>
                <p><strong>Date:</strong> <?php echo esc_html($visit->created_at); ?></p>
                <p><strong>Comment:</strong> <?php echo esc_html($visit->comment); ?></p>
            <?php endif; ?>
        `)">
        View Details
        </button>

    </div>

<?php endforeach; ?>
<?php else: ?>
    <p>No active properties.</p>
<?php endif; ?>

</div>