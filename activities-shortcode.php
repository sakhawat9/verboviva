<?php
function activities_stylish_shortcode($atts)
{
    static $instance = 0;
    $instance++;


    $atts = shortcode_atts(array(
        'posts_per_page' => -1,
        'filter'         => '',
        'term_id'        => 0,
        'filter_title'   => '',
        'pagination'     => false,
        'archive'        => '',
    ), $atts, 'activities');

    $atts['instance'] = $instance;
    $atts['pagination'] = filter_var($atts['pagination'], FILTER_VALIDATE_BOOLEAN);

    if (is_tax('domain') || is_tax('level') || is_tax('part-of-speech')) {
        if (is_tax() && empty($atts['filter']) && empty($atts['term_id'])) {
            $queried_object = get_queried_object();
            if ($queried_object && !is_wp_error($queried_object)) {
                $atts['filter'] = $queried_object->taxonomy;
                $atts['term_id'] = $queried_object->term_id;
            }
        }

        $cache_key = 'activities_render_' . md5(serialize($atts));
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        ob_start();

        echo '<div class="activities-section" id="activities-section-' . esc_attr($atts['instance']) . '" data-taxonomy="' . $atts['filter'] . '" data-instance="' . esc_attr($atts['instance']) . '" data-term="' . $atts['term_id'] . '" data-posts-per-page="' . esc_attr($atts['posts_per_page']) . '">';
        echo '<div class="styled-activity-results" id="styled-activity-results-' . esc_attr($atts['instance']) . '">';

        echo activities_stylish_render($atts);
        echo '</div>';
        echo '</div>';

        $output = ob_get_clean();
        set_transient($cache_key, $output, 300);
        return $output;
    } else {
        if (empty($atts['term_id']) && !empty($atts['filter'])) {
            $taxonomy = sanitize_key($atts['filter']);
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'number' => 1,
                'orderby' => 'name',
                'order' => 'ASC',
            ));

            if (!empty($terms) && !is_wp_error($terms)) {
                $atts['term_id'] = $terms[0]->term_id;
            }
        }

        $cache_key = 'activities_render_' . md5(serialize($atts));
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        ob_start();

        echo '<div class="activities-section" id="activities-section-' . esc_attr($atts['instance']) . '" data-posts-per-page="' . esc_attr($atts['posts_per_page']) . '">';
        echo '<div class="styled-activity-results" id="styled-activity-results-' . esc_attr($atts['instance']) . '">';

        echo activities_stylish_render($atts);
        echo '</div>';

        // Taxonomy filter UI
        if (!empty($atts['filter'])) {
            $taxonomy = sanitize_key($atts['filter']);
            $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));

            echo '<div class="activity-filters-bar">';
            if (!empty($atts['filter_title'])) {
                echo '<h2 class="filter-title">' . esc_html($atts['filter_title']) . '</h2>';
            }

            if (!empty($terms) && !is_wp_error($terms)) {
                echo '<div class="activity-filters ' . esc_attr($atts['filter']) . '" data-taxonomy="' . esc_attr($taxonomy) . '" data-instance="' . esc_attr($atts['instance']) . '">';
                foreach ($terms as $term) {
                    $icon_html = '';
                    $custom_color = '';
                    if ($taxonomy) {
                        $image_id = get_term_meta($term->term_id, 'custom_icon_id', true);
                        $custom_color = get_term_meta($term->term_id, 'custom_color', true);
                        if (!$custom_color) {
                            $custom_color = '#FEE7C0';
                        }
                        $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
                        if ($image_url) {
                            $icon_html = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($term->name) . '" class="filter-icon" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;" />';
                        }
                    }

                    $active_class = ($atts['term_id'] == $term->term_id) ? 'active' : '';
                    echo '<button class="filter-btn ' . esc_attr($active_class) . '" style="--custom-color: ' . esc_attr($custom_color) . '" data-term="' . esc_attr($term->term_id) . '" data-slug="' . esc_attr($term->slug) . '">' . wp_kses_post($icon_html) . esc_html($term->name) . '</button>';
                }
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
        $output = ob_get_clean();

        set_transient($cache_key, $output, 300); // cache for 5 minutes
        return $output;
    }
}
add_shortcode('activities', 'activities_stylish_shortcode');

function activities_stylish_render($atts)
{
    $atts = shortcode_atts(array(
        'posts_per_page' => 6,
        'filter'         => '',
        'filter_title'   => '',
        'term_id'        => 0,
        'pagination'     => false,
        'instance'       => 1,
        'page'           => 1,
    ), $atts);

    $taxonomy = $atts['filter'];
    $term_id  = intval($atts['term_id']);
    $paged    = max(1, intval($atts['page']));
    $pagination_enabled = filter_var($atts['pagination'], FILTER_VALIDATE_BOOLEAN);

    $args = array(
        'post_type'      => 'activity',
        'posts_per_page' => intval($atts['posts_per_page']),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'paged'          => $paged,
    );

    if (!empty($taxonomy)) {
        if ($term_id) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ),
            );
        } else {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'operator' => 'EXISTS',
                ),
            );
        }
    }

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) {
        echo '<div class="activities-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div class="activity-card">';

            // Thumbnail
            if (has_post_thumbnail()) {
                echo '<div class="activity-image">' . get_the_post_thumbnail(get_the_ID(), 'medium');
                // Show level taxonomy
                $terms = get_the_terms(get_the_ID(), 'level');
                if ($terms && !is_wp_error($terms)) {
                    $term_link = get_term_link($terms[0]);
                    if (!is_wp_error($term_link)) {
                        echo '<div class="activity-categories">';
                        echo '<a href="' . esc_url($term_link) . '" class="taxonomies">';
                        echo esc_html($terms[0]->name);
                        echo '</a></div>';
                    }
                }
                echo '</div>';
            }

            echo '<div class="activity-content">';
            // echo '<h3 class="activity-title"><a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a></h3>';
            echo '<h3 class="activity-title">' . get_the_title() . '</h3>';
            echo '<p class="activity-excerpt">' . get_the_excerpt() . '</p>';

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
                                $icon_link = get_term_link($term); // 🔗 Archive page link
                                break 2;
                            }
                        }
                    }
                }
            }

            if ($icon_url && $icon_link && !is_wp_error($icon_link)) {
                echo '<div class="activity-icon" style="background-color: ' . esc_attr($custom_color) . ';">';
                echo '<a href="' . esc_url($icon_link) . '">';
                echo '<img src="' . esc_url($icon_url) . '" alt="Icon" />';
                echo '</a>';
                echo '</div>';
            }

            echo '</div>';
            echo '</div>';
        }
        echo '</div>';

        // Pagination
        if ($pagination_enabled && $query->max_num_pages > 1) {
            echo '<div class="styled-pagination" data-instance="' . esc_attr($atts['instance']) . '" data-total="' . $query->max_num_pages . '" data-current="' . esc_attr($paged) . '">';
            for ($i = 1; $i <= $query->max_num_pages; $i++) {
                $active = $i == $paged ? 'active' : '';
                echo '<button class="pagination-btn ' . $active . '" data-page="' . esc_attr($i) . '">' . esc_html($i) . '</button>';
            }
            echo '</div>';
        }
    } else {
        echo wp_kses_post('<p>No activities found.</p>', 'verboviva');
    }

    wp_reset_postdata();
    return ob_get_clean();
}


function ajax_styled_filter_callback()
{
    check_ajax_referer('activities_nonce');

    $atts = array(
        'posts_per_page' => intval($_POST['posts_per_page']),
        'filter'         => sanitize_text_field($_POST['taxonomy']),
        'term_id'        => intval($_POST['term_id']),
        'instance'       => intval($_POST['instance']),
        'pagination'     => filter_var($_POST['pagination'], FILTER_VALIDATE_BOOLEAN),
        'page'           => isset($_POST['page']) ? intval($_POST['page']) : 1,
        'archive'        =>  '',
    );

    echo activities_stylish_render($atts);
    wp_die();
}
add_action('wp_ajax_styled_filter_activities', 'ajax_styled_filter_callback');
add_action('wp_ajax_nopriv_styled_filter_activities', 'ajax_styled_filter_callback');