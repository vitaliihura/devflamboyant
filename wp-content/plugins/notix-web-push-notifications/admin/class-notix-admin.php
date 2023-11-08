<?php

class Notix_Admin
{
    private static $NOTIX_API_URL = 'https://enot.fyi/api/wordpress/send';

    private $notix;
    private $debug;
    private $version;
    private $utils;

    public function __construct($notix, $debug, $version, $utils)
    {
        $this->notix = $notix;
        $this->debug = $debug;
        $this->version = $version;
        $this->utils = $utils;

        add_action('admin_menu', array($this, 'add_plugin_admin_menu'), 9);
        add_action('admin_init', array($this, 'register_and_build_fields'));

        add_action('add_meta_boxes', array($this, 'inject_post_notix_send'));
        add_action('save_post', array(__CLASS__, 'save_post'));

        add_action('post_updated', array($this, 'post_clear_flags'));

        add_action('wp_ajax_send_function',  array($this, 'send_function'));

        add_action('publish_future_post', array($this, 'notix_send_push'));
    }

    public function add_plugin_admin_menu()
    {
        add_menu_page(
            $this->notix,
            'Notix',
            'administrator',
            $this->notix,
            array($this, 'display_plugin_admin_settings'),
            plugin_dir_url(__FILE__) . 'img/menu-icon.png',
            26
        );


        if ($this->debug) {
            add_submenu_page(
                $this->notix,
                'Logs',
                'Logs',
                'administrator',
                'notix-logs',
                array($this, 'display_plugin_admin_settings_log')
            );
        }
    }

    public function display_plugin_admin_settings()
    {
        if (isset($_GET['error_message'])) {
            add_action('admin_notices', array($this, 'notix_settings_messages'));
            do_action('admin_notices', $_GET['error_message']);
        }
        require_once 'partials/' . $this->notix . '-admin-settings-display.php';
    }

    public function display_plugin_admin_settings_log()
    {
        require_once 'partials/' . $this->notix . '-admin-settings-log-display.php';
    }

    public function notix_settings_messages($error_message)
    {
        switch ($error_message) {
            case '1':
                $message = __('There was an error adding this setting. Please try again.');
                $err_code = 'notix_tag_setting';
                $setting_field = 'notix_tag_setting';
                break;
        }
        $type = 'error';

        add_settings_error($setting_field, $err_code, $message, $type);
    }

    public function register_and_build_fields()
    {
        add_settings_section('notix_general_section', '', array($this, 'notix_display_general_account'), 'notix_general_settings');

        $this->utils->register_setting(Notix::$NOTIX_APP_ID_SETTINGS_KEY, 'Notix App ID');
        $this->utils->register_setting(Notix::$NOTIX_API_TOKEN_SETTINGS_KEY, 'Notix API Token');
	    $this->utils->register_setting(Notix::$NOTIX_AUTO_SEND_FEATURE_ENABLED, 'Notix Auto Send feature');
	    $this->utils->register_setting(Notix::$NOTIX_ROOT_SW_FETURE_ENABLED, 'Notix service worker uploaded in site root directory feature');
        $this->utils->register_setting('notix_error_notices', 'Notix API Token');
    }

    public function notix_display_general_account()
    {
        echo '<p>These settings apply to all Notix functionality.</p>';
    }

    public function inject_post_notix_send()
    {
        add_meta_box(
            'notix_send_checkbox',
            'Notix',
            array($this, 'render_notix_send_checkbox'),
            'post',
            'side',
            'high'
        );

        $post_types = Notix_Admin::get_available_post_types();
	    foreach ($post_types  as $post_type) {
		    add_meta_box(
			    'notix_send_checkbox',
			    'Notix',
			    array( __CLASS__, 'render_notix_send_checkbox'),
			    $post_type,
			    'side',
			    'high'
		    );
	    }
    }

    public function send_function() {
        if (isset($_POST['post_id'])) {
	        Notix_Admin::notix_send_push($_POST['post_id'], true);
        }
	    wp_die();
    }

    public static function render_notix_send_checkbox($post)
    {
        wp_nonce_field('notix_metabox', 'notix_metabox_nonce');

        $sendButtonLabel = "For this post - the push notifications was not launched";
        $sendButtonText = "Send push";

        $postPublished = get_post_status($post) && get_post_status($post) == "publish";

        $postStatusSend = get_post_meta($post->ID, 'notix_push_sended');
        $isSended = isset($postStatusSend) && is_array($postStatusSend) && count($postStatusSend) > 0 && $postStatusSend[0] == "true";

	    $isAutoSendEnabledCheckboxStatus = esc_attr(get_option(Notix::$NOTIX_AUTO_SEND_FEATURE_ENABLED)) == "on";

        $postSendPushChecked = get_post_meta($post->ID, 'notix_send_checkbox');
	    $isChecked = isset($postSendPushChecked) && is_array($postSendPushChecked) && count($postSendPushChecked) > 0 && $postSendPushChecked[0] == "on";

        $postIsFuture = get_post_status($post) && get_post_status($post) == 'future';

        $currentCheckboxStatus = ($isAutoSendEnabledCheckboxStatus || ($isChecked && $postIsFuture)) ? 'checked': "";

        if ($isSended) {
	        $sendButtonLabel = "The push notifications was launched for this post";
	        $sendButtonText = "Re-Send push";
        }

        ?>
        <input type="button" style="<?php if (!$postPublished) echo 'display: none;' ?>" name="notix_send_button" id="notix_send_button" value="<?php echo $sendButtonText; ?>" />
        <label for="notix_send_button" id="notix_send_button_label"  style="<?php if (!$postPublished) { echo 'display: none;'; } else {echo 'display: block;'; } ?>"><?php echo $sendButtonLabel; ?></label>
        <input type="checkbox"  id="notix_send_checkbox" style="<?php if ($postPublished) echo 'display: none;' ?>" <?php echo $currentCheckboxStatus?> name="notix_send_checkbox"/>
        <label for="notix_send_checkbox" id="notix_send_checkbox_label"  style="<?php if ($postPublished) echo 'display: none;' ?>">Send push</label>

        <script>
            document.addEventListener('DOMContentLoaded',()=>{
                document.querySelector("input#notix_send_checkbox").addEventListener('click',()=>{
                    let checkbox = document.querySelector('input#notix_send_checkbox');
                    if (checkbox && checkbox.checked) {
                        let label = document.querySelector("label#notix_send_button_label");
                        if (label) {
                            label.innerText="The push notifications was launched for this post"
                        }

                        let button = document.querySelector("input#notix_send_button");
                        if (button) {
                            button.value="Re-Send push"
                        }
                    } else {
                        let label = document.querySelector("label#notix_send_button_label");
                        if (label) {
                            label.innerText="For this post - the push notifications was not launched"
                        }

                        let button = document.querySelector("input#notix_send_button");
                        if (button) {
                            button.value="Send push"
                        }
                    }
                });

                document.querySelector("input#notix_send_button").addEventListener('click',
                    (e) => {
                        let button = document.querySelector("input#notix_send_button");
                        if (button) {
                            button.disabled=true;
                        }

                        jQuery.ajax({
                            method: 'post',
                            url: '<?php echo  admin_url('admin-ajax.php') ?>',
                            data: {
                                action: 'send_function',
                                post_id: <?php echo $post->ID?>,
                                notix_send_button_clicked: 'on'
                            }
                        }).done(function (msg) {
                            let label = document.querySelector("label#notix_send_button_label");
                            if (label) {
                                label.innerText="Now sended!"
                                label.style.color = ""
                            }

                            let button = document.querySelector("input#notix_send_button");
                            if (button) {
                                button.value="Re-Send push"
                                button.disabled=false;
                            }
                        }).catch(function (msg) {
                            let label = document.querySelector("label#notix_send_button_label");
                            if (label) {
                                label.innerText="Send failed!"
                                label.style.color = "red"
                            }

                            let button = document.querySelector("input#notix_send_button");
                            if (button) {
                                button.value="Try Re-Send push"
                                button.disabled=false;
                            }
                        });

                        e.preventDefault();
                    }
                );

                function update_meta_box() {
                    let checkbox = document.querySelector("input#notix_send_checkbox");
                    if (checkbox) {
                        checkbox.checked = false;
                        checkbox.style.display = 'none';
                    }

                    let checkboxLabel = document.querySelector("label#notix_send_checkbox_label");
                    if (checkboxLabel) {
                        checkboxLabel.style.display = 'none';
                    }

                    let button = document.querySelector("input#notix_send_button");
                    if (button) {
                        button.style.display = 'block';
                    }

                    let buttonLabel = document.querySelector("label#notix_send_button_label");
                    if (buttonLabel) {
                        buttonLabel.style.display = 'block';
                    }
                }
                let dispatch = wp.data.dispatch( 'core/edit-post' );
                let oldMetaBoxUpdatesSuccess = dispatch.metaBoxUpdatesSuccess;
                dispatch.metaBoxUpdatesSuccess = function(...args) {
                    update_meta_box();
                    return oldMetaBoxUpdatesSuccess.apply(this, args);
                }
            });
        </script>

		<?php
    }

    public static function save_post($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        $post = get_post($post_id);
	    $post_types = Notix_Admin::get_available_post_types();

	    if ($post->post_type === 'wdslp-wds-log') {
		    // Prevent recursive post logging
		    return $post_id;
	    }

        if (!isset($_POST['post_status']) || $_POST['post_status'] !== 'publish' && !in_array($post->post_type, $post_types)) {
            return $post_id;
        }

        if (!isset($_POST['notix_metabox_nonce'])) {
            return $post_id;
        }

        if (isset($_POST['post_id_sended']) && $_POST['post_id_sended'] == $post_id) {
	        return $post_id;
        }

        if (!wp_verify_nonce((isset($_POST['notix_metabox_nonce']) ?
            sanitize_text_field($_POST['notix_metabox_nonce']) :
            ''
        ), 'notix_metabox')) {
            return $post_id;
        }

        if (array_key_exists('notix_send_checkbox', $_POST)) {
            update_post_meta($post_id, 'notix_send_checkbox', wp_kses_post( $_POST['notix_send_checkbox']));
        } else {
            update_post_meta($post_id, 'notix_send_checkbox', 'off');
        }

	    $_POST['post_id_sended'] = $post_id;

	    if ($_POST['post_status'] == 'future') {
		    return $post_id;
	    }

	    Notix_Admin::notix_send_push($post_id);
    }

    public static function notix_send_push($post_id, $isButtonSend = false)
    {
        $postMetaCheckbox = get_post_meta($post_id, 'notix_send_checkbox');
	    if ($postMetaCheckbox && is_array($postMetaCheckbox)) {
		    $postMetaCheckbox = $postMetaCheckbox[0];
	    }

        $bySendButton = isset($_POST['notix_send_button_clicked']) && $_POST['notix_send_button_clicked'] === 'on';
        $hasCheckedToSend = (isset($_POST['notix_send_checkbox']) && $_POST['notix_send_checkbox'] === 'on') ||
                            (isset($postMetaCheckbox) && $postMetaCheckbox === 'on');

        if (!$bySendButton && !$hasCheckedToSend) {
            return $post_id;
        }

        $post = get_post($post_id);

	    $post_types = Notix_Admin::get_available_post_types();

        if ($post->post_status !== 'publish' || !in_array($post->post_type, $post_types)) {
            return $post_id;
        }

        $isSendedPost = get_post_meta($post_id, 'notix_push_sended');

        if ($isSendedPost && is_array($isSendedPost)) {
	        $isSendedPost = $isSendedPost[0];
        }

        if (!$isButtonSend && $isSendedPost === 'true') {
            return $post_id;
        }

        $request = Notix_Utils::make_push_request($post_id);

        $response = wp_remote_post(self::$NOTIX_API_URL . '?app=' . esc_attr(get_option(Notix::$NOTIX_APP_ID_SETTINGS_KEY)), $request);

        $log = [
            "postId" => $post_id,
            "request" => json_encode($request),
            "response" => json_encode($response),
        ];

        update_option('notix_error_notices', $log);
        update_post_meta($post_id, 'notix_push_sended', 'true');

	    if (is_wp_error($response)) {
		    wp_send_json_error("failed", 500);
	    }
    }

	public static function get_available_post_types()
	{
		$args = array(
			'public' => true,
			'_builtin' => false,
		);
		$output = 'names';
		$operator = 'and';
		$post_types = [];
		$available_post_types = get_post_types($args, $output, $operator);

		foreach ($available_post_types  as $post_type) {
			if ($post_type) array_push($post_types, $post_type);
		}

        // Common post types
		array_push($post_types, 'post');

		return $post_types;
	}

    public function post_clear_flags($post_id)
    {
        update_post_meta($post_id, 'notix_push_sended', 'false');
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->notix, plugin_dir_url(__FILE__) . 'css/notix-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->notix, plugin_dir_url(__FILE__) . 'js/notix-admin.js', array('jquery'), $this->version, false);
    }
}
