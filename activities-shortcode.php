<?php

/**
 * Activities Shortcode with AJAX Filters (multi-tax, keeps selected, "All ..." options)
 */

if (!defined('ABSPATH')) exit;

add_shortcode('activities', 'activities_stylish_shortcode');
add_action('wp_ajax_styled_filter_activities', 'ajax_styled_filter_callback');
add_action('wp_ajax_nopriv_styled_filter_activities', 'ajax_styled_filter_callback');

/**
 * Shortcode wrapper
 */
function activities_stylish_shortcode($atts)
{
    static $instance = 0;
    $instance++;

    $atts = shortcode_atts(array(
        'posts_per_page' => -1,
        'pagination'     => false,
        'filter_bar'     => false,
    ), $atts, 'activities');

    $atts['instance']   = $instance;

    ob_start();
    echo '<div class="activities-section" id="activities-section-' . esc_attr($atts['instance']) . '" data-posts-per-page="' . esc_attr($atts['posts_per_page']) . '">';
    echo '  <div class="styled-activity-results" id="styled-activity-results-' . esc_attr($atts['instance']) . '">';
    echo activities_stylish_render($atts);
    echo '  </div>';
    echo '</div>';
    $output = ob_get_clean();

    return $output;
}

/**
 * Render results
 */
function activities_stylish_render($atts)
{
    $atts = shortcode_atts(array(
        'posts_per_page' => 6,
        'term_id'        => 0,
        'pagination'     => false,
        'filter_bar'     => false,
        'instance'       => 1,
        'page'           => 1,
        'tax_query'      => array(),
        'filters'        => array(),
    ), $atts);

    $paged = max(1, (int) $atts['page']);
    $pagination_enabled = (bool) $atts['pagination'];

    $allowed_taxonomies = array('level', 'domain', 'part-of-speech');
    $label_map = array(
        'level'          => esc_html__('All Levels', 'verboviva'),
        'domain'         => esc_html__('All Domains', 'verboviva'),
        'part-of-speech' => esc_html__('All Part of Speech', 'verboviva'),
    );

    $selected_filters = array();
    if (!empty($atts['filters'])) {
        foreach ($atts['filters'] as $tx => $term_id) {
            $tx = sanitize_key($tx);
            if (in_array($tx, $allowed_taxonomies, true)) {
                $selected_filters[$tx] = sanitize_title($term_id);
            }
        }
    }

    $args = array(
        'post_type'      => 'activity',
        'posts_per_page' => (int) $atts['posts_per_page'],
        'orderby'        => 'date',
        'order'          => 'DESC',
        'paged'          => $paged,
    );

    if (!empty($atts['tax_query'])) {
        $args['tax_query'] = $atts['tax_query'];
    }

    $query = new WP_Query($args);

    ob_start();
    $nonce = wp_create_nonce('activities_nonce');

    // ==== FILTER BAR ====
    if ($atts['filter_bar']) {
        echo '<div class="filter_wrapper" data-instance="' . esc_attr($atts['instance']) . '" data-filter-bar="' . ($atts['filter_bar'] ? 'true' : 'false') . '">';
        echo '<input type="hidden" class="activities-nonce" value="' . esc_attr($nonce) . '">';

        foreach ($allowed_taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
            ));
            if (!is_wp_error($terms)) {
                $current_selected = isset($selected_filters[$taxonomy]) ? (string) $selected_filters[$taxonomy] : '';
                echo '<div class="activity_select"><select class="activity-filter" data-taxonomy="' . esc_attr($taxonomy) . '">';
                echo '<option value="" ' . selected($current_selected, '', false) . '>' . esc_html($label_map[$taxonomy]) . '</option>';
                foreach ($terms as $term) {
                    $val = $term->slug;
                    echo '<option value="' . esc_attr($val) . '" ' . selected($current_selected, $val, false) . '>' . esc_html($term->name) . '</option>';
                }
                echo '</select></div>';
            }
        }
        echo '</div>';
    }

    // ==== RESULTS GRID ====
    if ($query->have_posts()) {
        echo '<div class="activities-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div class="activity-card">';
            if (has_post_thumbnail()) {
                echo '<div class="activity-image">' . get_the_post_thumbnail(get_the_ID(), 'medium');
                $level_terms = get_the_terms(get_the_ID(), 'level');
                $base_url = get_permalink(get_page_by_path('spanish/all-activities'));
                if ($level_terms && ! is_wp_error($level_terms)) {
                    $term_id = $level_terms[0]->term_id;
                    $custom_link = add_query_arg(
                        array('level' => $level_terms[0]->slug),
                        $base_url
                    );
                    echo '<div class="activity-categories">';
                    echo '<a class="taxonomies" href="' . esc_url($custom_link) . '">' . esc_html($level_terms[0]->name) . '</a>';
                    echo '</div>';
                }
                echo '</div>';
            }
            echo '<div class="activity-content">';
            echo '<h3>' . esc_html(get_the_title()) . '</h3>';
            echo '<p>' . esc_html(get_the_excerpt()) . '</p>';

            // Term icon
            $icon_url = '';
            $custom_color = '';
            $icon_link = '';
            $taxonomies = get_object_taxonomies(get_post_type(get_the_ID()), 'names');

            if ($taxonomies) {
                foreach ($taxonomies as $taxonomy) {
                    $terms = get_the_terms(get_the_ID(), $taxonomy);
                    if ($terms && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            $image_id = get_term_meta($term->term_id, 'custom_icon_id', true);
                            $color = get_term_meta($term->term_id, 'custom_color', true);
                            if ($image_id) {
                                $icon_url = wp_get_attachment_url($image_id);
                                $custom_color = $color ? $color : '#eeeeee';
                                break 2;
                            }
                        }
                    }
                }
            }
            $base_url = get_permalink(get_page_by_path('spanish/all-activities'));
            if ($icon_url && ! is_wp_error($term)) {
                $taxonomy  = $term->taxonomy;
                $slug   = $term->slug;
                $icon_link = add_query_arg(
                    array($taxonomy => $slug),
                    $base_url
                );

                echo '<div class="activity-icon" style="background-color: ' . esc_attr($custom_color) . ';">';
                echo '<a href="' . esc_url($icon_link) . '">';
                echo '<img src="' . esc_url($icon_url) . '" alt="Icon" />';
                echo '</a>';
                echo '</div>';
            }
            echo '</div></div>';
        }
        echo '</div>';

        if ($pagination_enabled && $query->max_num_pages > 1) {
            echo '<div class="styled-pagination" data-instance="' . esc_attr($atts['instance']) . '" data-total="' . esc_attr($query->max_num_pages) . '" data-current="' . esc_attr($paged) . '">';
            for ($i = 1; $i <= $query->max_num_pages; $i++) {
                $active = ($i === (int) $paged) ? 'active' : '';
                echo '<button class="pagination-btn ' . esc_attr($active) . '" data-page="' . esc_attr($i) . '">' . esc_html($i) . '</button>';
            }
            echo '</div>';
        }
    } else {
        echo '<div class="no_activities_found">' . esc_html__('No activities found.', 'verboviva') . '</div>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}

/**
 * AJAX handler
 */
function ajax_styled_filter_callback()
{
    check_ajax_referer('activities_nonce');

    $allowed_taxonomies = array('level', 'domain', 'part-of-speech');
    $atts = array(
        'posts_per_page' => isset($_POST['posts_per_page']) ? (int) $_POST['posts_per_page'] : 6,
        'instance'       => isset($_POST['instance']) ? (int) $_POST['instance'] : 1,
        'pagination'     => isset($_POST['pagination']) ? filter_var($_POST['pagination'], FILTER_VALIDATE_BOOLEAN) : false,
        'page'           => isset($_POST['page']) ? (int) $_POST['page'] : 1,
        'filter_bar'     => isset($_POST['filter_bar']) ? filter_var($_POST['filter_bar'], FILTER_VALIDATE_BOOLEAN) : false,
        'filters'        => array(),
    );

    $tax_query = array('relation' => 'AND');

    if (!empty($_POST['filters']) && is_array($_POST['filters'])) {
        foreach ($_POST['filters'] as $tx => $tid) {
            $tx = sanitize_key($tx);
            if (!in_array($tx, $allowed_taxonomies, true)) continue;
            $slug = sanitize_title($tid);
            $atts['filters'][$tx] = $slug ? $slug : '';
            if ($slug) {
                $tax_query[] = array(
                    'taxonomy' => $tx,
                    'field'    => 'slug',
                    'terms'    => $slug,
                );
            }
        }
    }

    if (count($tax_query) > 1) {
        $atts['tax_query'] = $tax_query;
    }

    echo activities_stylish_render($atts);
    wp_die();
}
