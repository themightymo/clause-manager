
<?php
/**
 * Plugin Name: Clause Manager
 * Plugin URI:  https://example.com
 * Description: A simple plugin to store, select, and display contract clauses.
 * Version:     1.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Add the [clause_manager_form] shortcode on the page you'd like to use to generate your contract.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * 1. Register the "clause" Custom Post Type
 */
function cm_register_clauses_cpt() {
    $labels = array(
        'name'               => 'Clauses',
        'singular_name'      => 'Clause',
        'add_new'            => 'Add New Clause',
        'add_new_item'       => 'Add New Clause',
        'edit_item'          => 'Edit Clause',
        'new_item'           => 'New Clause',
        'all_items'          => 'All Clauses',
        'view_item'          => 'View Clause',
        'search_items'       => 'Search Clauses',
        'not_found'          => 'No clauses found',
        'not_found_in_trash' => 'No clauses found in Trash',
        'menu_name'          => 'Clauses'
    );
    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,  // Show in admin
        'exclude_from_search'=> true,
        'hierarchical'       => false,
        'supports'           => array('title', 'editor'),
        'menu_icon'          => 'dashicons-edit',
    );
    register_post_type('clause', $args);
}
add_action('init', 'cm_register_clauses_cpt');

/**
 * 2. Shortcode to Display Clause Selection Form and Output
 * Usage: [clause_manager_form]
 *
 * - On initial load, display all clauses as checkboxes.
 * - On form submission, compile selected clauses into a single output.
 */
function cm_clause_manager_form_shortcode( $atts ) {
    // Handle form submission
    if ( isset($_POST['cm_selected_clauses']) && is_array($_POST['cm_selected_clauses']) ) {
        // Sanitize clause IDs
        $clause_ids = array_map('intval', $_POST['cm_selected_clauses']);
        
        // Query the selected clauses
        $args = array(
            'post_type' => 'clause',
            'post__in'  => $clause_ids,
            'orderby'   => 'post__in',  // keep the order in which they were selected, if desired
            'posts_per_page' => -1
        );
        $query = new WP_Query($args);

        // Start output buffering
        ob_start();
        echo '<h2>Your Compiled Contract</h2>';
        echo '<div style="margin-bottom:10px;">';
        echo '<button onclick="window.print()">Print Contract</button>';
        echo '</div>';

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                echo '<h3>' . get_the_title() . '</h3>';
                echo '<div>' . apply_filters('the_content', get_the_content()) . '</div>';
                echo '<hr>';
            }
        } else {
            echo '<p>No clauses selected or found.</p>';
        }
        wp_reset_postdata();

        // Return the compiled content
        return ob_get_clean();
    }
    else {
        // Display the clause selection form
        return cm_render_clause_selection_form();
    }
}
add_shortcode('clause_manager_form', 'cm_clause_manager_form_shortcode');

/**
 * Helper function to render the selection form (checkboxes of all clauses)
 */
function cm_render_clause_selection_form() {
    // Fetch all clauses
    $args = array(
        'post_type'      => 'clause',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC'
    );
    $clauses = get_posts($args);

    // Start output buffering
    ob_start();
    ?>
    <form method="post" action="">
        <h2>Select Clauses to Include</h2>
        <p>Check the boxes for each clause you want in your final contract, then click "Generate Contract".</p>
        
        <?php if ( $clauses ) : ?>
            <ul style="list-style:none; padding-left:0;">
                <?php foreach( $clauses as $clause ) : ?>
                    <li style="margin-bottom: 6px;">
                        <label>
                            <input type="checkbox" name="cm_selected_clauses[]" value="<?php echo esc_attr($clause->ID); ?>">
                            <?php echo esc_html($clause->post_title); ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>No clauses found. Please add clauses in the WordPress admin.</p>
        <?php endif; ?>
        
        <input type="submit" value="Generate Contract" class="button button-primary">
    </form>
    <?php

    return ob_get_clean();
}
