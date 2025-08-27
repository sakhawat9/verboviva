<?php
function register_activities_post_type()
{
    $labels = array(
        'name'                  => _x('Activities', 'Post type general name', 'verboviba'),
        'singular_name'         => _x('Activity', 'Post type singular name', 'verboviba'),
        'menu_name'             => _x('Activities', 'Admin Menu text', 'verboviba'),
        'name_admin_bar'        => _x('Activity', 'Add New on Toolbar', 'verboviba'),
        'add_new'               => __('Add New', 'verboviba'),
        'add_new_item'          => __('Add New Activity', 'verboviba'),
        'new_item'              => __('New Activity', 'verboviba'),
        'edit_item'             => __('Edit Activity', 'verboviba'),
        'view_item'             => __('View Activity', 'verboviba'),
        'all_items'             => __('All Activities', 'verboviba'),
        'search_items'          => __('Search Activities', 'verboviba'),
        'not_found'             => __('No activities found.', 'verboviba'),
        'not_found_in_trash'    => __('No activities found in Trash.', 'verboviba'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'activities', 'with_front' => false),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-buddicons-activity',
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
        'show_in_rest'       => true,

        // ✅ Add this to show taxonomy metaboxes in editor
        'taxonomies'         => array('level', 'domain', 'part-of-speech'),
    );

    register_post_type('activity', $args);
}

add_action('init', 'register_activities_post_type');

function register_activities_taxonomies() {
    $taxonomies = [
        'level' => [
            'singular'     => __('Level', 'textdomain'),
            'plural'       => __('Levels', 'textdomain'),
            'slug'         => 'level',
        ],
        'domain' => [
            'singular'     => __('Domain', 'textdomain'),
            'plural'       => __('Domains', 'textdomain'),
            'slug'         => 'domain',
        ],
        'part-of-speech' => [
            'singular'     => __('Part of Speech', 'textdomain'),
            'plural'       => __('Parts of Speech', 'textdomain'),
            'slug'         => 'part-of-speech',
        ]
    ];

    foreach ( $taxonomies as $taxonomy => $args ) {
        register_taxonomy( $taxonomy, 'activity', array(
            'labels' => array(
                'name'              => $args['plural'],
                'singular_name'     => $args['singular'],
                'search_items'      => sprintf( __('Search %s', 'textdomain'), $args['plural'] ),
                'all_items'         => sprintf( __('All %s', 'textdomain'), $args['plural'] ),
                'parent_item'       => sprintf( __('Parent %s', 'textdomain'), $args['singular'] ),
                'parent_item_colon' => sprintf( __('Parent %s:', 'textdomain'), $args['singular'] ),
                'edit_item'         => sprintf( __('Edit %s', 'textdomain'), $args['singular'] ),
                'update_item'       => sprintf( __('Update %s', 'textdomain'), $args['singular'] ),
                'add_new_item'      => sprintf( __('Add New %s', 'textdomain'), $args['singular'] ),
                'new_item_name'     => sprintf( __('New %s Name', 'textdomain'), $args['singular'] ),
                'menu_name'         => $args['plural'],
            ),
            'hierarchical'          => true,
            'public'                => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_rest'          => true,
            'rewrite'               => array( 'slug' => 'spanish/all-activities/' . $args['slug'], 'with_front' => false, ),
        ));
    }
}
add_action('init', 'register_activities_taxonomies');


function register_custom_taxonomy_fields_all() {
    // Enqueue media uploader scripts
    add_action('admin_enqueue_scripts', function() {
        wp_enqueue_media();
    });

    // Output JS for media uploader
    add_action('admin_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function($){
                var file_frame;
                $('#upload_custom_icon_button').on('click', function(e) {
                    e.preventDefault();
                    if (file_frame) {
                        file_frame.open();
                        return;
                    }

                    file_frame = wp.media.frames.file_frame = wp.media({
                        title: 'Select or Upload Icon',
                        button: { text: 'Use this icon' },
                        multiple: false
                    });

                    file_frame.on('select', function() {
                        var attachment = file_frame.state().get('selection').first().toJSON();
                        $('#custom_icon_id').val(attachment.id);
                        $('#custom_icon_preview').attr('src', attachment.url).show();
                    });

                    file_frame.open();
                });
            });
        </script>
        <?php
    });

    // Hook into all taxonomies dynamically
    $taxonomies = get_taxonomies([], 'names');

    foreach ($taxonomies as $taxonomy) {

        // Add form field (Add New)
        add_action("{$taxonomy}_add_form_fields", function() {
            ?>
            <div class="form-field">
                <label for="custom_icon_id"><?php _e('Custom Icon', 'textdomain'); ?></label>
                <input type="hidden" name="custom_icon_id" id="custom_icon_id" value="">
                <img id="custom_icon_preview" src="" style="max-width:100px; display:none;" />
                <br><button class="button" type="button" id="upload_custom_icon_button"><?php _e('Upload / Select Icon', 'textdomain'); ?></button>
                <p class="description"><?php _e('Upload an image icon (SVG, PNG, JPG).', 'textdomain'); ?></p>
            </div>
            <div class="form-field">
                <label for="custom_color"><?php _e('Custom Color', 'textdomain'); ?></label>
                <input type="text" name="custom_color" id="custom_color" value="">
                <p class="description"><?php _e('Enter a HEX or named color for this term.', 'textdomain'); ?></p>
            </div>
            <?php
        });

        // Edit form field (Edit Term)
        add_action("{$taxonomy}_edit_form_fields", function($term) {
            $image_id = get_term_meta($term->term_id, 'custom_icon_id', true);
            $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
            $custom_color = get_term_meta($term->term_id, 'custom_color', true);
            ?>
            <tr class="form-field">
                <th scope="row"><label for="custom_icon_id"><?php _e('Custom Icon', 'textdomain'); ?></label></th>
                <td>
                    <input type="hidden" name="custom_icon_id" id="custom_icon_id" value="<?php echo esc_attr($image_id); ?>">
                    <img id="custom_icon_preview" src="<?php echo esc_url($image_url); ?>" style="max-width:100px; <?php echo $image_url ? '' : 'display:none;'; ?>" />
                    <br><button class="button" type="button" id="upload_custom_icon_button"><?php _e('Upload / Select Icon', 'textdomain'); ?></button>
                    <p class="description"><?php _e('Upload or change icon image.', 'textdomain'); ?></p>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row"><label for="custom_color"><?php _e('Custom Color', 'textdomain'); ?></label></th>
                <td>
                    <input type="text" name="custom_color" id="custom_color" value="<?php echo esc_attr($custom_color); ?>">
                    <p class="description"><?php _e('Enter a HEX or named color for this term.', 'textdomain'); ?></p>
                </td>
            </tr>
            <?php
        });

        // Save fields on create/edit
        add_action("created_{$taxonomy}", 'save_custom_taxonomy_field_global', 10, 2);
        add_action("edited_{$taxonomy}", 'save_custom_taxonomy_field_global', 10, 2);
    }
}
add_action('admin_init', 'register_custom_taxonomy_fields_all');

function save_custom_taxonomy_field_global($term_id) {
    if (isset($_POST['custom_icon_id'])) {
        update_term_meta($term_id, 'custom_icon_id', intval($_POST['custom_icon_id']));
    }

    if (isset($_POST['custom_color'])) {
        update_term_meta($term_id, 'custom_color', sanitize_text_field($_POST['custom_color']));
    }
}