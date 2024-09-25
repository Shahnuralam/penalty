/*
Plugin Name: Penalty 
Description: A plugin to manage penalties for the company.
Version: 1.1
Author: Shahnur Alam
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register custom post type for penalties
function cpp_register_penalty_post_type() {
    register_post_type('penalty', array(
        'labels' => array(
            'name' => __('Penalties'),
            'singular_name' => __('Penalty'),
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('editor', 'custom-fields'),
        'menu_icon' => 'dashicons-warning',
    ));
}
add_action('init', 'cpp_register_penalty_post_type');

// Register custom post type for penalty types
function cpp_register_penalty_type_post_type() {
    register_post_type('penalty_type', array(
        'labels' => array(
            'name' => __('Penalty Types'),
            'singular_name' => __('Penalty Type'),
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-category',
    ));
}
add_action('init', 'cpp_register_penalty_type_post_type');

// Add a custom admin menu page
function cpp_add_admin_menu() {
    add_menu_page('Company Penalty', 'Penalties', 'manage_options', 'company_penalty', 'cpp_penalty_page', 'dashicons-warning', 6);
    add_submenu_page('company_penalty', 'Add Penalty Type', 'Penalty Types', 'manage_options', 'add_penalty_type', 'cpp_penalty_type_page');
}
add_action('admin_menu', 'cpp_add_admin_menu');

function cpp_penalty_type_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check if we're adding or editing a penalty type
    if (isset($_POST['submit_penalty_type'])) {
        $penalty_type_name = sanitize_text_field($_POST['penalty_type_name']);
        $penalty_type_id = isset($_POST['penalty_type_id']) ? intval($_POST['penalty_type_id']) : 0;

        if ($penalty_type_id > 0) {
            // Edit existing penalty type
            $penalty_type = array(
                'ID'           => $penalty_type_id,
                'post_title'   => $penalty_type_name,
                'post_type'    => 'penalty_type',
            );
            wp_update_post($penalty_type);
            echo '<div class="cpp-updated"><p>Penalty type updated successfully!</p></div>';
        } else {
            // Add new penalty type
            $new_penalty_type = array(
                'post_title'    => $penalty_type_name,
                'post_status'   => 'publish',
                'post_type'     => 'penalty_type',
            );
            wp_insert_post($new_penalty_type);
            echo '<div class="cpp-updated"><p>Penalty type added successfully!</p></div>';
        }
    }

    // Handle delete request
    if (isset($_POST['delete_penalty_type'])) {
        $penalty_type_id = intval($_POST['penalty_type_id']);
        wp_delete_post($penalty_type_id, true);
        echo '<div class="cpp-updated"><p>Penalty type deleted successfully!</p></div>';
    }

    // Fetch all penalty types for display
    $penalty_types = get_posts(array(
        'post_type' => 'penalty_type',
        'numberposts' => -1,
        'post_status' => 'publish'
    ));

    ?>
    <div class="cpp-wrap">
        <h1 class="cpp-title">Manage Penalty Types</h1>
        <form method="POST" class="cpp-form">
            <input type="hidden" name="penalty_type_id" id="penalty_type_id" value="">
            <label for="penalty_type_name" class="cpp-label">Penalty Type Name:</label>
            <input type="text" name="penalty_type_name" id="penalty_type_name" class="cpp-input" required>
            <br>
            <input type="submit" name="submit_penalty_type" value="Add Penalty Type" class="cpp-button">
        </form>

        <h2 class="cpp-title">Penalty Type History</h2>
        <table class="cpp-table">
            <thead>
                <tr class="cpp-table-header">
                    <th scope="col">Penalty Type</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($penalty_types) {
                foreach ($penalty_types as $type) {
                    echo '<tr class="cpp-table-row">';
                    echo '<td class="cpp-table-cell">' . esc_html($type->post_title) . '</td>';
                    echo '<td class="cpp-table-cell">
                              <button class="cpp-button cpp-edit-button" onclick="editPenaltyType(' . esc_attr($type->ID) . ', \'' . esc_js($type->post_title) . '\')">Edit</button>
                              <form method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this type?\');">
                                  <input type="hidden" name="penalty_type_id" value="' . esc_attr($type->ID) . '">
                                  <input type="submit" name="delete_penalty_type" value="Delete" class="cpp-button cpp-delete-button">
                              </form>
                          </td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="2">No penalty types found.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

    <script>
    function editPenaltyType(id, name) {
        document.getElementById('penalty_type_id').value = id;
        document.getElementById('penalty_type_name').value = name;
        document.querySelector('input[name="submit_penalty_type"]').value = 'Update Penalty Type';
    }
    </script>
    <?php
}


function cpp_penalty_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle form submission for adding and editing penalties
    if (isset($_POST['cpp_add_penalty']) || isset($_POST['cpp_edit_penalty'])) {
        $penalty_id = isset($_POST['penalty_id']) ? intval($_POST['penalty_id']) : 0;
        $employee_name = sanitize_text_field($_POST['employee_name']);
        $penalty_type = sanitize_text_field($_POST['penalty_type']);
        $penalty_amount = sanitize_text_field($_POST['penalty_amount']);
        $penalty_date = sanitize_text_field($_POST['penalty_date']);

        if ($penalty_id > 0) {
            // Edit existing penalty
            $update_penalty = array(
                'ID' => $penalty_id,
                'post_title' => $employee_name,
                'post_content' => 'Type: ' . $penalty_type . ' | Amount: ' . $penalty_amount . ' | Date: ' . $penalty_date,
            );

            wp_update_post($update_penalty);
            echo '<div class="cpp-updated"><p>Penalty updated successfully!</p></div>';
        } else {
            // Add new penalty
            $new_penalty = array(
                'post_title' => $employee_name,
                'post_content' => 'Type: ' . $penalty_type . ' | Amount: ' . $penalty_amount . ' | Date: ' . $penalty_date,
                'post_status' => 'publish',
                'post_type' => 'penalty',
            );

            wp_insert_post($new_penalty);
            echo '<div class="cpp-updated"><p>Penalty added successfully!</p></div>';
        }
    }

    // Fetch all penalty types for the dropdown
    $penalty_types = get_posts(array(
        'post_type' => 'penalty_type',
        'numberposts' => -1,
    ));

    // Display the form for adding and editing penalties
    ?>
    <div class="cpp-wrap">
    <h1 class="cpp-title">Add Penalty</h1>
    <form method="POST" class="cpp-form">
        <input type="hidden" name="penalty_id" value="" id="penalty_id">
        <div class="cpp-form-group">
            <label for="employee_name" class="cpp-label">Employee Name:</label>
            <input type="text" name="employee_name" class="cpp-input" required>
        </div>
        <div class="cpp-form-group">
            <label for="penalty_type" class="cpp-label">Penalty Type:</label>
            <select name="penalty_type" class="cpp-input" required>
                <option value="">Select Penalty Type</option>
                <?php foreach ($penalty_types as $type): ?>
                    <option value="<?php echo esc_attr($type->post_title); ?>"><?php echo esc_html($type->post_title); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cpp-form-group">
            <label for="penalty_amount" class="cpp-label">Penalty Amount:</label>
            <input type="text" name="penalty_amount" class="cpp-input" required>
        </div>
        <div class="cpp-form-group">
            <label for="penalty_date" class="cpp-label">Penalty Date:</label>
            <input type="date" name="penalty_date" class="cpp-input" required>
        </div>
        <input type="submit" name="cpp_add_penalty" value="Add Penalty" class="cpp-button cpp-add-button" id="submit_button">
    </form>


        <h2 class="cpp-title">Penalty History</h2>
        <table class="cpp-table">
            <thead>
                <tr class="cpp-table-header">
                    <th scope="col">Employee Name</th>
                    <th scope="col">Penalty Type</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Date</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $penalties = new WP_Query(array('post_type' => 'penalty', 'posts_per_page' => -1));
            if ($penalties->have_posts()) {
                while ($penalties->have_posts()) {
                    $penalties->the_post();
                    $content = get_the_content();
                    preg_match('/Type: (.*?) \| Amount: (.*?) \| Date: (.*)/', $content, $matches);
                    $type = isset($matches[1]) ? $matches[1] : 'N/A';
                    $amount = isset($matches[2]) ? $matches[2] : 'N/A';
                    $date = isset($matches[3]) ? $matches[3] : 'N/A';
                    $penalty_id = get_the_ID();

                    echo '<tr class="cpp-table-row">';
                    echo '<td class="cpp-table-cell">' . esc_html(get_the_title()) . '</td>'; // Employee Name
                    echo '<td class="cpp-table-cell">' . esc_html($type) . '</td>';
                    echo '<td class="cpp-table-cell">' . esc_html($amount) . '</td>';
                    echo '<td class="cpp-table-cell">' . esc_html($date) . '</td>';
                    echo '<td class="cpp-table-cell">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="penalty_id" value="' . esc_attr($penalty_id) . '">
                                <input type="submit" name="cpp_delete_penalty" value="Delete" class="cpp-button cpp-delete-button" onclick="return confirm(\'Are you sure you want to delete this penalty?\');">
                            </form>
                            <button class="cpp-button cpp-edit-button" onclick="editPenalty(' . esc_attr($penalty_id) . ', \'' . esc_js(get_the_title()) . '\', \'' . esc_js($type) . '\', \'' . esc_js($amount) . '\', \'' . esc_js($date) . '\')">Edit</button>
                          </td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5">No penalties found.</td></tr>';
            }
            wp_reset_postdata();
            ?>
            </tbody>
        </table>
    </div>

    <script>
    function editPenalty(id, name, type, amount, date) {
        document.querySelector('input[name="penalty_id"]').value = id;
        document.querySelector('input[name="employee_name"]').value = name;
        document.querySelector('select[name="penalty_type"]').value = type;
        document.querySelector('input[name="penalty_amount"]').value = amount;
        document.querySelector('input[name="penalty_date"]').value = date;

        // Change the button text to "Update Penalty"
        document.getElementById('submit_button').value = 'Update Penalty';
    }
    </script>
    <?php
}



// CSS styles for the admin page
function cpp_admin_styles() {
    echo '
    <style>
           /* General Admin Styling */
       .cpp-form-group {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease;
    }

    .cpp-form-group:hover {
        transform: scale(1.02);
    }

    .cpp-wrap {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.8s ease-in;
        width: 80%;
        max-width: 600px;
        margin: 20px auto;
    }

    .cpp-title {
        font-size: 24px;
        margin-bottom: 20px;
        color: #333;
        animation: pulse 1s infinite;
        text-align: center;
    }

    .cpp-input {
        padding: 10px;
        margin-top: 5px;
        border: 2px solid #ccc;
        border-radius: 5px;
        transition: border-color 0.3s;
    }

    .cpp-input:focus {
        border-color: #0073aa;
        outline: none;
    }

    .cpp-button {
        background: #0073aa;
        color: #fff;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s, transform 0.3s;
    }

    .cpp-button:hover {
        background: #006799;
        transform: translateY(-2px);
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes pulse {
        0% { color: #0073aa; }
        50% { color: #00a0d2; }
        100% { color: #0073aa; }
    }
        .cpp-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .cpp-table-header {
            background-color: #0073aa;
            color: #fff;
            font-size: 16px;
            text-align: center;
        }
        .cpp-table-row {
            background: #f9f9f9;
            border-bottom: 1px solid #ccc;
            transition: background 0.3s;
            text-align: center;
        }
        .cpp-table-row:hover {
            background: #e1f5fe;
        }
        .cpp-table-cell {
            padding: 15px;
            margin: 5px 0;
            font-size: 14px;
            border-radius: 5px;
			border: 1px solid #8080804f;
			        line-height: 48px;
        }
        .cpp-updated {
            background-color: #46b450;
            padding: 10px;
            margin-bottom: 20px;
            color: #fff;
            border-radius: 5px;
            animation: fadeIn 1s ease-in-out;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        @keyframes pulse {
            0% {
                color: #0073aa;
            }
            50% {
                color: #00a0d2;
            }
            100% {
                color: #0073aa;
            }
        }

        /* Modal Styling */
        #cpp-edit-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }
    </style>
    ';
}
add_action('admin_head', 'cpp_admin_styles');


// Shortcode to display penalties
function cpp_display_penalties() {
    ob_start();
    ?>
    <div class="cpp-penalty-list">
        <h2 class="cpp-penalty-header">Penalty List</h2>
        <table class="cpp-penalty-table">
            <thead>
                <tr class="cpp-table-header">
                    <th>Employee Name</th>
                    <th>Penalty Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $penalties = new WP_Query(array('post_type' => 'penalty', 'posts_per_page' => -1));
            $total_amount = 0;

            if ($penalties->have_posts()) {
                while ($penalties->have_posts()) {
                    $penalties->the_post();
                    $content = get_the_content();
                    preg_match('/Type: (.*?) \| Amount: (.*?) \| Date: (.*)/', $content, $matches);
                    $type = isset($matches[1]) ? esc_html($matches[1]) : 'N/A';
                    $amount = isset($matches[2]) ? floatval($matches[2]) : 0;
                    $date = isset($matches[3]) ? esc_html($matches[3]) : 'N/A';
                    $total_amount += $amount;

                    echo '<tr class="cpp-table-row">';
                    echo '<td class="cpp-table-cell">' . esc_html(get_the_title()) . '</td>';
                    echo '<td class="cpp-table-cell">' . $type . '</td>';
                    echo '<td class="cpp-table-cell">' . esc_html($amount) . '</td>';
                    echo '<td class="cpp-table-cell">' . $date . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4">No penalties found.</td></tr>';
            }
            wp_reset_postdata();
            ?>
            </tbody>
        </table>

        <!-- Display total amount -->
        <div class="cpp-total-penalty">Total Penalty Amount: <strong><?php echo esc_html($total_amount); ?></strong></div>
    </div>

    <style>
        /* Frontend Styling */
        .cpp-penalty-list {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.8s ease-in;
        }
        .cpp-penalty-header {
            font-size: 24px;
            margin-bottom: 15px;
            color: #0073aa;
            text-align: center;
            animation: pulse 1.5s infinite;
        }
        .cpp-penalty-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .cpp-table-header {
            background-color: #0073aa;
            color: #fff;
            font-size: 16px;
            text-align: left;
        }
        .cpp-table-row {
            background: #f9f9f9;
            border-bottom: 1px solid #ccc;
            transition: background 0.3s, transform 0.3s;
        }
        .cpp-table-row:hover {
            background: #e1f5fe;
            transform: scale(1.02);
        }
        .cpp-table-cell {
            padding: 15px;
            margin: 5px 0;
            font-size: 14px;
            border-radius: 5px;
        }
        .cpp-total-penalty {
            font-size: 18px;
            margin-top: 20px;
            font-weight: bold;
            color: #0073aa;
            text-align: right;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        @keyframes pulse {
            0% {
                color: #0073aa;
            }
            50% {
                color: #00a0d2;
            }
            100% {
                color: #0073aa;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('display_penalties', 'cpp_display_penalties');
