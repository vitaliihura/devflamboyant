<?php

class Notix_Utils {

    /* PUSH */

    public static function make_push_request($post_id) {
        $thumbnail_image = '';
        $large_image = '';

        if (has_post_thumbnail($post_id)) {
            $post_thumbnail_id = get_post_thumbnail_id($post_id);
            $thumbnail_sized_images_array = wp_get_attachment_image_src($post_thumbnail_id, array(192, 192), true);
            $large_sized_images_array = wp_get_attachment_image_src($post_thumbnail_id, 'large', true);

            $thumbnail_image = $thumbnail_sized_images_array[0];
            $large_image = $large_sized_images_array[0];
        }

        $title = get_bloginfo('name');
        $text = get_the_title($post_id);

        $audiences = [];

        $fields = array(
            'message' => array(
                'title' => $title,
                'text' => $text,
                'icon' => $thumbnail_image,
                'image' => $large_image,
                'url' => get_post_permalink($post_id),
            ),
            'target' => array(
                'audience' => $audiences,
            ),
        );

        return array(
            'headers' => array(
                'content-type' => 'application/json;charset=utf-8',
                'Authorization-Token' => esc_attr(get_option(Notix::$NOTIX_API_TOKEN_SETTINGS_KEY)),
            ),
            'body' => wp_json_encode($fields),
            'timeout' => 3,
        );
    }

    /* SETTINGS */

    public function register_setting($key, $name) {
        add_settings_field(
            $key,
            $name,
            array($this, 'notix_render_settings_field'),
            'notix_general_settings',
            'notix_general_section',
            array(
                'type' => 'input',
                'subtype' => 'text',
                'id' => $key,
                'name' => $key,
                'required' => 'true',
                'get_options_list' => '',
                'value_type' => 'normal',
                'wp_data' => 'option'
            )
        );

        register_setting('notix_general_settings', $key);
    }

    public function notix_render_settings_field($args)
    {
        if ($args['wp_data'] == 'option') {
            $wp_data_value = get_option($args['name']);
        } elseif ($args['wp_data'] == 'post_meta') {
            $wp_data_value = get_post_meta($args['post_id'], $args['name'], true);
        }

        $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;

        echo '<input type="' . esc_attr($args['subtype']) . '" id="' . esc_attr($args['id']) . '" "' . esc_attr($args['required']) . '" name="' . esc_attr($args['name']) . '" size="54" value="' . sanitize_text_field($value) . '" />';
    }
}