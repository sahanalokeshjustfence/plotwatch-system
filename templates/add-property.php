<?php 
if (!is_user_logged_in()) return; 
?>

<h2>Add Property</h2>

<form method="post" class="pw-form">

    <?php wp_nonce_field('pw_add_property_nonce'); ?>
    <input type="hidden" name="pw_add_property" value="1">

    <!-- Property Name -->
    <input type="text" 
           name="property_name" 
           placeholder="Property Name" 
           class="full" 
           required>

    <!-- Location Name -->
    <input type="text" 
           name="location_name" 
           placeholder="Location Name" 
           required>

    <!-- Full Address -->
    <textarea name="address" 
              placeholder="Full Address" 
              class="full" 
              required></textarea>

    <!-- Google Map Pin -->
    <input type="text" 
           name="google_map" 
           placeholder="Google Map Pin / Coordinates (Optional)">

    <!-- Plot Size -->
    <input type="text" 
           name="plot_size" 
           placeholder="Plot Size (Ex: 1200 sqft)" 
           required>

    <!-- Property Type -->
    <select name="property_type" required>
        <option value="">Select Property Type</option>
        <option value="Residential">Residential</option>
        <option value="Commercial">Commercial</option>
        <option value="Land">Land</option>
        <option value="Warehouse">Warehouse</option>
    </select>

    <!-- Contact Person -->
    <input type="text" 
           name="contact_person" 
           placeholder="Contact Person" 
           required>

    <!-- Contact Number -->
    <input type="text" 
           name="contact_number" 
           placeholder="Contact Number" 
           required>

    <!-- Special Instructions -->
    <textarea name="special_instructions" 
              placeholder="Special Instructions (Optional)" 
              class="full"></textarea>

    <button type="submit" class="pw-btn full">
        Submit Property
    </button>

</form>