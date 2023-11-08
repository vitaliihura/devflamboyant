<?php

class Notix
{
	public static $NOTIX_APP_ID_SETTINGS_KEY = 'notix_app_id_setting';
	public static $NOTIX_API_TOKEN_SETTINGS_KEY = 'notix_api_token_setting';
	public static $NOTIX_TAGS_NOTIFY_FEATURE_ENABLED = 'notix_tag_notify_feature_enabled_setting';
	public static $NOTIX_TAGS_NOTIFY_FEATURE_SUBSCRIBE_ELEMENT_SELECTOR = 'notix_tag_notify_feature_subscribe_element_selector';
	public static $NOTIX_AUTO_SEND_FEATURE_ENABLED = 'notix_auto_send_feature_enabled_setting';
	public static $NOTIX_ROOT_SW_FETURE_ENABLED = 'notix_root_sw_feature_enabled_setting';

    protected $loader;
    protected $notix;
    protected $version;
    protected $debug;

    public function __construct()
    {
        if (defined('NOTIX_VERSION')) {
            $this->version = NOTIX_VERSION;
        } else {
            $this->version = '1.2.0';
        }
        $this->notix = 'notix';
        $this->debug = getenv('NOTIX_PLUGIN_DEBUG')=="1";

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-notix-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-notix-i18n.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-notix-utils.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-notix-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-notix-public.php';

        $this->loader = new Notix_Loader();
    }

    private function set_locale()
    {
        $plugin_i18n = new Notix_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks()
    {
        $plugin_admin = new Notix_Admin($this->get_notix(), $this->get_debug(), $this->get_version(), new Notix_Utils());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    private function define_public_hooks()
    {
        $plugin_public = new Notix_Public($this->get_notix(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    public function run()
    {
        $this->loader->run();
    }

    public function get_notix()
    {
        return $this->notix;
    }

    public function get_debug()
    {
        return $this->debug;
    }

    public function get_loader()
    {
        return $this->loader;
    }

    public function get_version()
    {
        return $this->version;
    }
}
