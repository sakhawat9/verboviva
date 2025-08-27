<?php

function allow_svg_uploads($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'allow_svg_uploads');

function verboviba_enqueue_fontawesome() {
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', array(), '6.5.0' );
}
add_action( 'wp_enqueue_scripts', 'verboviba_enqueue_fontawesome' );


add_action('wp_enqueue_scripts', 'verboviva_enqueue_styles');
function verboviva_enqueue_styles()
{
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('twentytwentyfive-style'),
        '1.1.1'
    );

    wp_enqueue_script('jquery');
    wp_enqueue_script(
        'child-script',
        get_stylesheet_directory_uri() . '/child.min.js',
        array('jquery'), // Add 'jquery' if using it
        '1.0.0',
        true // Load in footer
    );

    wp_localize_script('child-script', 'activities_ajax_object', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('activities_nonce')
    ));
}


function recent_post_add_reading_time_after_excerpt($excerpt)
{
    global $post;

    if (get_post_type($post) !== 'post') {
        return $excerpt;
    }

    // Get the full content to calculate word count
    $content = get_the_content(null, false, $post);
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Assuming 200 words per minute

    // Reading time text
    $excerpt_reading_time_text = '<p class="wp-block-post-excerpt__excerpt">' . $excerpt . '</p><p class="reading-time"><svg width="20" height="20" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M981.333 512c0-129.579-52.565-246.997-137.472-331.861s-202.283-137.472-331.861-137.472-246.997 52.565-331.861 137.472-137.472 202.283-137.472 331.861 52.565 246.997 137.472 331.861 202.283 137.472 331.861 137.472 246.997-52.565 331.861-137.472 137.472-202.283 137.472-331.861zM896 512c0 106.069-42.923 201.984-112.469 271.531s-165.461 112.469-271.531 112.469-201.984-42.923-271.531-112.469-112.469-165.461-112.469-271.531 42.923-201.984 112.469-271.531 165.461-112.469 271.531-112.469 201.984 42.923 271.531 112.469 112.469 165.461 112.469 271.531zM469.333 256v256c0 16.597 9.472 31.019 23.595 38.144l170.667 85.333c21.077 10.539 46.72 2.005 57.259-19.072s2.005-46.72-19.072-57.259l-147.115-73.515v-229.632c0-23.552-19.115-42.667-42.667-42.667s-42.667 19.115-42.667 42.667z"></path></svg>' . $reading_time . ' min</p>';

    // Append reading time after the excerpt
    return $excerpt_reading_time_text;
}

add_filter('get_the_excerpt', 'recent_post_add_reading_time_after_excerpt');

function add_reading_time_after_excerpt_block($block_content, $block)
{
    if ($block['blockName'] === 'core/post-excerpt') { // Only modify the "Display the Excerpt" block

        global $post;
        $content = get_the_content(null, false, $post);
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / 200);

        // Reading time text with SVG icon
        $reading_time_text = '<p class="reading-time">
            <svg width="30" height="30" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
                <path d="M981.333 512c0-129.579-52.565-246.997-137.472-331.861s-202.283-137.472-331.861-137.472-246.997 52.565-331.861 137.472-137.472 202.283-137.472 331.861 52.565 246.997 137.472 331.861 202.283 137.472 331.861 137.472 246.997-52.565 331.861-137.472 137.472-202.283 137.472-331.861zM896 512c0 106.069-42.923 201.984-112.469 271.531s-165.461 112.469-271.531 112.469-201.984-42.923-271.531-112.469-112.469-165.461-112.469-271.531 42.923-201.984 112.469-271.531 165.461-112.469 271.531-112.469 201.984 42.923 271.531 112.469 112.469 165.461 112.469 271.531zM469.333 256v256c0 16.597 9.472 31.019 23.595 38.144l170.667 85.333c21.077 10.539 46.72 2.005 57.259-19.072s2.005-46.72-19.072-57.259l-147.115-73.515v-229.632c0-23.552-19.115-42.667-42.667-42.667s-42.667 19.115-42.667 42.667z"></path>
            </svg> ' . $reading_time . ' min</p>';

        $author_id = get_the_author_meta('ID');
        $author_name = get_the_author();
        $author_url = get_author_posts_url($author_id);
        $author_avatar = get_avatar($author_id, 30);

        $author_info = '
        <div class="author-box">
            <a href="' . esc_url($author_url) . '" class="author-link">
                ' . $author_avatar . '
            </a>
        </div>
        ';

        $bottom_info = '<div class="post_bottom_info">' . $reading_time_text . $author_info . '</div>';

        // Append the reading time after the excerpt
        return $block_content . $bottom_info;
    }

    return $block_content;
}

add_filter('render_block', 'add_reading_time_after_excerpt_block', 10, 2);

function shortcode_accordion_wrapper($atts, $content = null)
{
    ob_start();
?>
    <div class="custom-accordion"><?php echo do_shortcode($content); ?></div>
<?php return ob_get_clean();
}
add_shortcode('accordion', 'shortcode_accordion_wrapper');


function shortcode_accordion_title($atts, $content = null)
{
    global $post;

    $atts = shortcode_atts(array(
        'accordion_title' => 'Untitled',
        'page_id' => '',
        'bg' => '',
        'show_excerpt' => 'false',
        'show_image' => 'false'
    ), $atts);
    $page_ids = array_filter(array_map('intval', explode(',', $atts['page_id'])));
    $current_page_id = is_page() ? get_the_ID() : null;
    $should_open = in_array($current_page_id, $page_ids);
    $has_current_class = $should_open ? ' has-current-page' : '';
    $bg_style = $atts['bg'] ? 'background:' . esc_attr($atts['bg']) . ';' : '';
    ob_start(); ?><div class="accordion-section">
        <div class="accordion-header<?php echo $has_current_class; ?>" style="<?php echo $bg_style; ?>"><?php echo esc_html($atts['accordion_title']); ?></div>
        <div class="accordion-content open" style="<?php echo $should_open ? 'max-height:999px;' : ''; ?>">
            <?php if (!empty($page_ids)) {
                $pages = get_posts(array(
                    'post_type' => 'page',
                    'post__in' => $page_ids,
                    'orderby' => 'post__in',
                    'numberposts' => -1
                ));
                if (!empty($pages)): ?>
                    <ul>
                        <?php foreach ($pages as $pg): ?>
                            <?php $is_current = (is_page() && get_the_ID() === $pg->ID); ?>
                            <li><a href="<?php echo get_permalink($pg); ?>" class="<?php echo $is_current ? 'current-page-link' : ''; ?>"><?php echo esc_html(get_the_title($pg)); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
            <?php endif;
            } elseif (!empty($content)) {
                echo do_shortcode($content);
            }
            ?>
        </div>
    </div><?php return ob_get_clean();
        }
        add_shortcode('accordion_title', 'shortcode_accordion_title');

        include get_stylesheet_directory() . '/activities-post-type.php';
        include get_stylesheet_directory() . '/activities-shortcode.php';