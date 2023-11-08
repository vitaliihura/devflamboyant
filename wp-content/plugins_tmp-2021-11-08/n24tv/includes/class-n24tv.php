<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    N24TV
 * @subpackage N24TV/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    N24TV
 * @subpackage N24TV/includes
 * @author     Your Name <email@example.com>
 */
class N24TV {

    const LOG_DEBUG = false;
    const LOG_ERROR = true;

    // full DB refresh
    static public $refresh_event = 'n24tv_refresh_event';
    // hourly updates
    static public $hourly_event = 'n24tv_hourly_event';
    // update posts
    static public $update_event = 'n24tv_update_event';

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      N24TV_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Current URI
     */
    protected $uri;
    /**
     * Try to measure time
     */
    protected $start;

    static public function __log($msg, $type = 'TEST') {
        return;
        file_put_contents(__DIR__ . '/../log/n24tv.log', 'TEST: ' . $msg . "\n", FILE_APPEND);
    }

    /**
     * Log 
     */
    static public function log($msg, $type = 'DEBUG'){
        // return;
        //file_put_contents(__DIR__ . '/../log/n24tv.log', 'debug: ' . $msg . "\n", FILE_APPEND);
        if (
            (self::LOG_DEBUG && $type == 'DEBUG') ||
            (self::LOG_ERROR && $type == 'ERROR')
           ){

            file_put_contents(__DIR__ . '/../log/n24tv.log', date('Y-m-d H:i:s') . ': ' . $type . ': ' . $msg . "\n", FILE_APPEND);
        }
    }

    /**
     * Schedule event that should run as soon as possible.
     */
    static public function schedule_event($event){
        if (! wp_next_scheduled( $event ) ){
            self::log('Scheduling event into WP Cron: ' . $event, 'ERROR');
            wp_schedule_single_event( time(), $event );
        }
        else {
            self::log('Event: ' . $event . ' already scheduled', 'ERROR');
        }
    }

    static public function get_categories(){
        static $arrCache = NULL;

        if ($arrCache === NULL){
            $arrCache = array();
            $Redis = N24TV_Redis::getInstance();
            $ret = $Redis->get_categories();
            if (is_array($ret)){
                $arrCache = $ret;
            }
        }
        return $arrCache;
    }

    /**
     * Our main method to retreive top posts
     *
     * If redis or redis key is not available, simply don't do anything. We'll get that via crontab as soon as possible.
     */
    static public function get_top_posts($category_id = 0, $count = 5){
        static $arrCache = array();
        if (isset($arrCache[$category_id]) && count($arrCache[$category_id]) >= $count){
            return array_slice($arrCache[$category_id], 0, $count);
        }
        $ret = array();
        $Redis = N24TV_Redis::getInstance();
        if ($Redis->isConnected()){
            $ret = $Redis->get_top_posts($category_id);
            if (!is_array($ret) || count($ret) == 0){
                self::log('Top posts not available in Redis', 'ERROR');

                self::schedule_event(self::$refresh_event);

                $ret = array();
            }
            else {
                $arrCache[$category_id] = $ret;
            }
        }
        return array_slice($ret, 0, $count);
    }

    /**
     * Out main method to retreive latest posts
     */
    static public function get_latest_posts($category_id = 0, $count = 4){
        static $arrCache = array();
        if (isset($arrCache[$category_id]) && count($arrCache[$category_id]) >= $count){
            return array_slice($arrCache[$category_id], 0, $count);
        }
        $ret = array();
        $Redis = N24TV_Redis::getInstance();
        if ($Redis->isConnected()){
            $ret = $Redis->get_latest_posts($category_id, $count);
            if (!is_array($ret) || count($ret) == 0){
                self::log('Latest posts not available in Redis', 'ERROR');

                self::schedule_event(self::$refresh_event);
                
                $ret = array();
            }
            else {
                /*
                usort($ret, function($a, $b){
                    if ($a['DateTime'] == $b['DateTime']) return 0;
                    return ($a['DateTime'] < $b['DateTime']) ? 1 : -1;
                });
                */
                $arrCache[$category_id] = $ret;
            }
        }
        return $ret;
    }

    static public function get_top_comments_posts($count = 5){
        static $arrCache = NULL;
        if (is_array($arrCache)){
            return array_slice($arrCache[$count], 0, $count);
        }
        $ret = array();
        $Redis = N24TV_Redis::getInstance();
        if ($Redis->isConnected()){
            self::log('get_top_comments_posts: Fetching ' . $count);
            $data = $Redis->get_top_comments_posts();
            if (!is_array($data) || empty($data)){
                // attempt to return latest posts
                return self::get_latest_posts(0, $count);
            }
            
            $arrCache = $data;
            return array_slice($data, 0, $count);
        }
        return $ret;
    }

    /**
     * Our main method to retreive featured posts
     */
    static public function get_featured_posts($category_id, $count = 5){
        $ret = array();

        $Featured = N24TV_Featured::getInstance();

        $data = $Featured->get($category_id);
        if (!is_array($data) || empty($data)){
            // attempt to return latest posts
            $category_id = (is_numeric($category_id) ? $category_id : 0);
            return self::get_latest_posts($category_id, $count);
        }
        if (count($data) < $count){
            $f_category_id = (is_numeric($category_id) ? $category_id : 0);
            $latest_posts = self::get_latest_posts($f_category_id, $count);
            foreach($latest_posts as $post){
                $data[] = $post;
            }
        }
        return array_slice($data, 0, $count);
    }

    /**
     * Get featured posts (only Ids)
     */
    static public function get_featured_posts_ids($category_id, $count = 5){
        $posts = self::get_featured_posts($category_id, $count);
        $ret = array();
        if (is_array($posts) && count($posts) > 0){
            foreach($posts as $_post){
                $ret[] = $_post['id'];
            }
        }
        return $ret;
    }

    static public function get_related_posts_by_tags($tags, $skip_post_id = null, $count = 5){
        $ret = array();

        if (!is_array($tags) || empty($tags)){
            return self::get_latest_posts(0, $count);
        }

        $Redis = N24TV_Redis::getInstance();
        if ($Redis->isConnected()){
            $posts = $Redis->get_related_posts_by_tags($tags, $skip_post_id, $count);
            if (!is_array($posts) || empty($posts)){
                return self::get_latest_posts(0, $count);
            }
            $ret = $posts;
        }
        return $ret;
    }

    static public function get_related_posts_by_author($author_id, $skip_post_id = NULL, $tags = NULL, $count = 5){
        $ret = array();
        $author_id = (int)$author_id;

        if (empty($author_id)){
            return self::get_latest_posts(0, $count);
        }

        $Redis = N24TV_Redis::getInstance();
        if ($Redis->isConnected()){
            $posts = $Redis->get_related_posts_by_author($author_id, $skip_post_id, $tags, $count);
            if (!is_array($posts) || empty($posts)){
                return self::get_latest_posts(0, $count);
            }
            $ret = $posts;
        }
        return $ret;
    }

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->uri = (isset($_SERVER) && is_array($_SERVER) && isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'NOT_SET');
        if ($this->uri == '/wp-admin/admin-ajax.php'){
            $this->uri .= " ACTION: " . (isset($_POST['action']) ? $_POST['action'] : 'NONE');
        }
        $this->start = microtime(true);

        $this->plugin_name = 'n24tv';
        $this->version = '1.0.9';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - N24TV_Loader. Orchestrates the hooks of the plugin.
     * - N24TV_i18n. Defines internationalization functionality.
     * - N24TV_Admin. Defines all hooks for the admin area.
     * - N24TV_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-n24tv-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-n24tv-i18n.php';

        /**
         * The class responsible for all our Redis functions
         */
        require_once plugin_dir_path( dirname(__FILE__) ) . 'includes/class-n24tv-redis.php';
        /**
         * The class responsible for post view counts in WP postmeta.
         */
        require_once plugin_dir_path( dirname(__FILE__) ) . 'includes/class-n24tv-postview.php';
        /**
         * The class responsible for handling featured posts
         */
        require_once plugin_dir_path( dirname(__FILE__) ) . 'includes/class-n24tv-featured.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-n24tv-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-n24tv-public.php';

        $tmp = N24TV_Featured::getInstance();

        $this->loader = new N24TV_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the N24TV_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new N24TV_i18n();

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new N24TV_Admin( $this->get_plugin_name(), $this->get_version() );

        if (is_admin()){
            /**
             * Admin init action hook.
             *
             * It will register settings.
             */
            $this->loader->add_action( 'admin_init', $plugin_admin, 'init');
            /**
             * Configure admin menu
             */
            $this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu');

            /**
             * Enqueue CSS and Script
             *
             */
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

            /**
             * Modify view posts admin - add Views to columns
             */

            $this->loader->add_filter( 'manage_posts_columns', $plugin_admin, 'manage_posts_columns');
            $this->loader->add_action( 'manage_posts_custom_column', $plugin_admin, 'manage_posts_custom_column', 10, 2);

            /**
             * Enable post_title LIKE search in MySQL
             */
            $this->loader->add_filter( 'posts_where', $this, 'title_like_posts_where', 10, 2);

            /**
             * Enqueue the featured posts get_categories AJAX action
             */
            $this->loader->add_action( 'wp_ajax_n24tv_admin_get_latest_posts', $plugin_admin, 'ajax_get_latest_posts');
        }


        /**
         * Tap into wordpress to see if any posts hasve changed. We'll probably run update_event
         */
        $this->loader->add_action( 'publish_future_post', $plugin_admin, 'publish_future_post', 10, 1);
        $this->loader->add_action( 'transition_post_status', $plugin_admin, 'transition_post_status', 10, 3);
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_post', 10, 3);

        // refresh whole DB
        $this->loader->add_action( self::$refresh_event, $plugin_admin, 'refresh_event');
        // refresh posts (fired when we notice that post has changed/added)
        $this->loader->add_action( self::$update_event, $plugin_admin, 'update_event');
        // houlry event
        $this->loader->add_action( self::$hourly_event, $plugin_admin, 'hourly_event');

        // register shutdown to measure performance
        $this->loader->add_action( 'shutdown', $this, 'shutdown');

    }

    // enable search by post_title_like argument
    public function title_like_posts_where($where, &$wp_query){
        global $wpdb;
        if ( $post_title_like = $wp_query->get( 'post_title_like' ) ) {
            $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like( $post_title_like ) ) . '%\'';
        }
        return $where;
    }

    public function posts_where_limit_featured_posts($where, &$wp_query){
        global $wpdb;

        if (!is_admin() && is_category()){
            $cat_id = (int)get_query_var('cat');
            if ($cat_id > 0){
                $post_ids = self::get_featured_posts_ids($cat_id, 4);
                if (count($post_ids) > 0){
                    $where .= ' AND ' . $wpdb->posts . '.ID NOT IN (' . implode(',', $post_ids) . ') ';
                }
            }
        }
        return $where;
    }

    public function shutdown(){
        global $wpdb;
        $time = microtime(true) - $this->start;
        if ($time > 2.5){
            self::log('Total time for uri: ' . $this->uri . ': ' . number_format($time, 6) . ' second(s)', 'ERROR');
        }
        //self::log('COOKIE: ' . (isset($_COOKIE) ? print_r($_COOKIE, true) : 'NO COOKIE'), 'ERROR');
        /*
        $q = $wpdb->queries;
        self::log('q: ' .print_r($q, true), 'ERROR');
        */



    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new N24TV_Public( $this->get_plugin_name(), $this->get_version() );

        // fire page_view event via AJAX (only for non-logged in users)
        $this->loader->add_action( 'wp_ajax_nopriv_n24tv_post_view', $plugin_public, 'post_view');

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        // attempt to remove featured posts from category pages
        $this->loader->add_filter('posts_where', $this, 'posts_where_limit_featured_posts', 10, 2);

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    N24TV_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
