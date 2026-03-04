<?php 
if (!is_user_logged_in()) return; 
?>

<div class="pw-property-card">

    <h2 class="pw-page-title">Add Property</h2>

    <form method="post" class="pw-property-form">

        <?php wp_nonce_field('pw_add_property_nonce'); ?>
        <input type="hidden" name="pw_add_property" value="1">

        <!-- ROW 1 -->
        <div class="pw-grid-3">
            <div>
                <label>Property Name *</label>
                <input type="text" 
                       name="property_name" 
                       required>
            </div>

            <div>
                <label>Location Name *</label>
                <input type="text" 
                       name="location_name" 
                       required>
            </div>

            <div>
                <label>Plot Size (Ex: 1200 sqft) *</label>
                <input type="text" 
                       name="plot_size" 
                       required>
            </div>
        </div>

        <!-- ROW 2 -->
        <div class="pw-grid-3">
            <div>
                <label>Property Type *</label>
                <select name="property_type" required>
                    <option value="">Select Property Type</option>
                    <option value="Residential">Residential</option>
                    <option value="Commercial">Commercial</option>
                    <option value="Land">Land</option>
                    <option value="Warehouse">Warehouse</option>
                </select>
            </div>

            <div>
                <label>Contact Person *</label>
                <input type="text" 
                       name="contact_person" 
                       required>
            </div>

            <div>
                <label>Contact Number *</label>
                <input type="text" 
                       name="contact_number" 
                       required>
            </div>
        </div>

        <!-- ROW 3 -->
        <div class="pw-grid-3">
            <div class="full">
                <label>Full Address *</label>
                <textarea name="address" required></textarea>
            </div>
        </div>

        <!-- ROW 4 -->
        <div class="pw-grid-3">
            <div>
                <label>Google Map Pin / Coordinates *</label>
                <input type="url"
       name="google_map"
       placeholder="https://maps.google.com/..."
       pattern="https?://.+"
       required>
            </div>

            <div class="full">
                <label>Special Instructions (Optional)</label>
                <textarea name="special_instructions"></textarea>
            </div>
        </div>

        <button type="submit" class="pw-btn pw-submit-btn">
            Submit Property
        </button>

    </form>

</div>