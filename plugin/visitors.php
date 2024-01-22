<?php

/**
 * @package JV_Visitors
 * @version 1.0.0s
 */
/*
    Plugin Name: Visitors
    Description: Display IP address, user agent and referrer of the website visitors
    Author:      Jerome Vonk
    Version:     1.0.0
    Author URI:  http://jeromevonk.github.io
    License: GPL v2 or later
    License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


// ------------------------------------------------------------------
// Add a menu page
// - Use the admin_menu hook
// - Add a page called 'Visitors' to admin menu
// ------------------------------------------------------------------
function custom_admin_menu_item()
{
    add_menu_page(
        'Visitors',            // Page Title
        'Visitors',            // Menu Title
        'manage_options',      // Capability
        'visitors',            // Menu Slug
        'visitors_admin_page', // Callback function to display the page
        'dashicons-airplane'   // URL to the icon
    );
}

add_action('admin_menu', 'custom_admin_menu_item');

// ------------------------------------------------------------------
// Store visitor info
// - Use the wp_loaded hook
// - Get IP, user agent and referrer from HTTP request headers
// ------------------------------------------------------------------
function store_visitor_info()
{
    // Get the visitor's IP address from the HTTP_X_FORWARDED_FOR header if available
    $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']) : '';

    // If the header is not set, fall back to REMOTE_ADDR
    if (empty($ip)) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
    }

    // Ensure the IP address is valid
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'Unknown';
        $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url($_SERVER['HTTP_REFERER']) : 'Direct visit';
        $date = current_time('mysql');

        $visitor_data = array(
            'ip' => $ip,
            'user_agent' => $user_agent,
            'referer' => $referer,
            'date' => $date,
        );

        $last_visitors = get_option('last_visitors', array());

        // Limit the number of stored visitor data to 50
        $limit = 50;
        array_unshift($last_visitors, $visitor_data);
        $last_visitors = array_slice($last_visitors, 0, $limit);

        update_option('last_visitors', $last_visitors);
    }
}

add_action('wp_loaded', 'store_visitor_info');


// ------------------------------------------------------------
//  Visitors Admin Page
//  Displays the recorded information
// ------------------------------------------------------------
function visitors_admin_page()
{
    $last_visitors = get_option('last_visitors', array());

    echo '<div class="wrap">';
    echo '<h2>Visitors</h2>';

    // Enqueue styles
    echo '<style>';
    echo '.visitor-list { display: none; flex-wrap: wrap; justify-content: space-between; }';
    echo '.visitor-item { width: 48%; box-sizing: border-box; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }';
    echo '.date-buttons-container { margin-bottom: 20px; }';
    echo '#visitor-container .date-button { background-color: #fff; color: #000; padding: 10px 15px; margin: 5px 5px 5px 0; border: 2px solid #000; border-radius: 5px; cursor: pointer; }';
    echo '#visitor-container .date-button:hover { background-color: #f5f5f5; }';
    echo '</style>';

    // Group visitors by date
    $grouped_visitors = array();
    foreach ($last_visitors as $visitor_data) {
        $date = date('Y-m-d', strtotime($visitor_data['date']));
        $grouped_visitors[$date][] = $visitor_data;
    }

    if (!empty($grouped_visitors)) {
        echo '<div id="visitor-container">';

        // Display buttons for each day
        echo '<div class="date-buttons-container">';
        foreach (array_keys($grouped_visitors) as $date) {
            echo '<button class="date-button button" data-date="' . esc_attr($date) . '">' . esc_html($date) . '</button>';
        }
        echo '</div>';

        // Display visitors for each day
        foreach ($grouped_visitors as $date => $visitors) {
            echo '<div class="visitor-list" id="visitor-list-' . esc_attr($date) . '">';
            foreach ($visitors as $visitor_data) {
                echo '<div class="visitor-item">';
                echo '<strong>IP:</strong> ' . esc_html($visitor_data['ip']) . '<br>';
                echo '<strong>User Agent:</strong> ' . esc_html($visitor_data['user_agent']) . '<br>';
                echo '<strong>Referer:</strong> <a href="' . esc_url($visitor_data['referer']) . '">' . esc_html($visitor_data['referer']) . '</a><br>';
                echo '<strong>Date:</strong> ' . esc_html($visitor_data['date']);
                echo '</div>';
            }
            echo '</div>';
        }

        echo '</div>';

        // Enqueue JavaScript
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function() {';
        echo '  var dateButtons = document.querySelectorAll(".date-button");';
        echo '  var visitorLists = document.querySelectorAll(".visitor-list");';
        echo '  dateButtons.forEach(function(button) {';
        echo '    button.addEventListener("click", function() {';
        echo '      var selectedDate = this.getAttribute("data-date");';
        echo '      visitorLists.forEach(function(list) {';
        echo '        list.style.display = (list.id === "visitor-list-" + selectedDate) ? "flex" : "none";';
        echo '      });';
        echo '    });';
        echo '  });';
        echo '});';
        echo '</script>';
    } else {
        echo '<p>No visitor data available.</p>';
    }

    echo '</div>';
}
