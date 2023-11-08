<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    N24TV
 * @subpackage N24TV/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    N24TV
 * @subpackage N24TV/admin
 * @author     Your Name <email@example.com>
 */
class N24TV_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    static public function log_post_changes($msg){
        return;
        $file = __DIR__ . '/../log/post_changes.log';
        file_put_contents($file, date('Y-m-d H:i:s') . ': ' . $msg . "\n", FILE_APPEND);
    }

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        if (isset($_GET['n24tv_action'])){
            N24TV::log('n24tv_action is set: ' . $_GET['n24tv_action']);
            switch($_GET['n24tv_action']){
                case 'update_top_posts':
                    N24TV::log('Force update top posts');
                    $count = $this->update_top_posts();
                    add_action('admin_notices', function(){
                        ?>
                        <div class="notice notice-success is-dismissible">
                            <p>Top posts updated</p>
                        </div>
                        <?php
                    });
                    break;
            }
        }

    }

    /**
     * Initialize the module 
     */
    public function init(){

        $this->register_settings();

    }

    /**
     * Register settings
     * @since 1.0.0
     */
    protected function register_settings(){

        //add_options_page('N24TV Page Title', 'N24TV Menu Title', 'manage_options', 'my24tv_menu', 'my24tv_func');

        register_setting('reading', 'n24tv_redis_host', array('type' => 'string', 'description' => 'Redis server IP/Host'));


        add_settings_section('n24tv_settings_section', 'N24TV Settings', function(){
            echo '';
        }, 'reading');

        add_settings_field('n24tv_redis_host', 'Redis host', function(){
            // get the value of the setting we've registered with register_setting()
            $setting = get_option('n24tv_redis_host');
            // output the field
            ?>
            <input type="text" name="n24tv_redis_host" value="<?= isset($setting) ? esc_attr($setting) : ''; ?>">
            <?php
        }, 'reading', 'n24tv_settings_section');
    }

    /**
     * Running hourly crontab
     *
     * Fired via n24tv_houlry_event hook
     *
     * We should run only updates that affect how users viewed posts
     * - view_count (based on visits)
     * - top_posts (based on visits)
     * - top_comments (based on new comments)
     */
    public function hourly_event(){
        N24TV::log(' ******** Running houlry event', 'ERROR');
        $this->process_redis_posts_view_count();
        $this->update_top_posts();
        $this->update_top_comments_posts();
        $hour = (int)date('H');
        // only allow updates to taxonomy data between 1-5 in the morning
        // you can force update posts taxonomy data via refresh_event
        if ($hour > 0 && $hour < 6){
            $this->update_categories();
            $this->update_posts_taxonomy_data();
        }
    }

    /**
     * Update all stored posts as something has changed
     */
    public function update_event(){
        N24TV::log(' ******** Running update event', 'ERROR');
        $this->update_latest_posts();
        $this->update_featured_posts();
        $this->update_top_posts();
        $this->update_top_comments_posts();
    }

    /**
     * Refresh event
     */
    public function refresh_event(){
        N24TV::log(' ******** Running FULL REFRESH event', 'ERROR');
        $this->hourly_event();
        $this->update_event();
        $this->update_posts_taxonomy_data();
    }

    public function update_categories(){
        N24TV::log(' *** Running update_categories');

        $ret = false;

        try {
            $Redis = N24TV_Redis::getInstance();
            if ($Redis->isConnected()){
                $categories = N24TV_PostView::get_categories();
                if (!empty($categories)){
                    return $Redis->write_categories($categories);
                }
            }
        } catch (Exception $e){
            N24TV::log('update_categories(): ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Update top posts from WPDB to redis
     *
     * This will load posts and evething, so it's slow. Run from crontab.
     */
    public function update_top_posts(){
        N24TV::log(' *** Running update_top_posts');

        $ret = false;
        N24TV::log('update 1', 'ERROR');

        try {
            $Redis = N24TV_Redis::getInstance();
            if ($Redis->isConnected()){

                $posts = N24TV_PostView::get_top_posts();

                $data = array();
                N24TV::log('update 1a: ' . count($posts), 'ERROR');

                foreach($posts as $post_id){
                    $post = N24TV_PostView::get_post_data($post_id);
                    N24TV::log('update 1b', 'ERROR');
                    if ($post !== false){
                        $data[] = $post;
                    }
                }
                if (!empty($data)){
                    N24TV::log('update 2', 'ERROR');
                    return $Redis->write_top_posts($data);
                }
                else {
                    N24TV::log('update 3', 'ERROR');
                    return false;
                }
            }
        } catch (Exception $e){
            N24TV::log('update_top_posts(): ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
        }

    }

    public function update_latest_posts(){
        N24TV::log(' *** Running update latest posts');

        $ret = false;

        try {
            $Redis = N24TV_Redis::getInstance();
            if ($Redis->isConnected()){

                $data = array();
                $categories = N24TV_PostView::get_latest_posts();
                foreach($categories as $cat_id => $posts){
                    $data[$cat_id] = array();
                    foreach($posts as $post_id){
                        N24TV::log('update_latest_posts: fetching post_id: ' . $post_id);
                        $post = N24TV_PostView::get_post_data($post_id);
                        if ($post !== false){
                            $data[$cat_id][] = $post;
                        }
                    }
                }

                if (!empty($data)){
                    return $Redis->write_latest_posts($data);
                }
                else {
                    return false;
                }

            }
            else {
                N24TV::log('Redis not available?!');
            }
        } catch (Exception $e){
            N24TV::log('update_latest_posts(): ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
        }
    }

    // update featured posts 
    public function update_featured_posts(){
        N24TV::log(' *** Running update featured posts');
        $ret = false;
        try {
            $Featured = N24TV_Featured::getInstance();
            return $Featured->update_posts();
        } catch (Exception $e){
            N24TV::log('update_featured_posts(): ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
        }
        return $ret;
    }

    public function update_top_comments_posts(){
        $ret = false;

        N24TV::log(' *** update_top_comments_posts: running');
        try {
            $Redis = N24TV_Redis::getInstance();
            if ($Redis->isConnected()){
                $data = array();
                $posts = N24TV_PostView::get_top_comments_posts();
                foreach($posts as $_p){
                    $post_id = $_p['ID'];
                    $comment_count = (int)$_p['CommentCount'];
                    $post = N24TV_PostView::get_post_data($post_id);
                    if ($post !== false){
                        $post['top_comment_count'] = $comment_count;
                        $data[] = $post;
                    }
                    else {
                        N24TV::log('update_top_comments_posts: Failed to fetch post: ' . $post_id . ': ' . print_r($post, true), 'ERROR');
                    }
                }
                return $Redis->write_top_comments_posts($data);

            }
        } catch (Exception $e){
            N24TV::log('update_top_comments_post: ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
            $ret = false;
        }
        return $ret;
    }

    /**
     * Update data about tags and authors
     *
     * We use this data to get related posts.
     *
     * @note This is rather slow.
     */
    public function update_posts_taxonomy_data(){
        $ret = false;
        N24TV::log(' *** update posts taxonomy data');
        try {
            $Redis = N24TV_Redis::getInstance();
            if ($Redis->isConnected()){
                $data = N24TV_PostView::get_posts_taxonomy_data();
                if (!is_array($data) || empty($data)){
                    throw new Exception('Failed to fetch taxonomy data from WPDB');
                }
                else {
                    return $Redis->write_posts_taxonomy_data($data);
                }
            }
        } catch (Exception $e){
            N24TV::log('update_posts_taxonomy_data: ' . $e->getMessage(), 'ERROR');
            $ret = false;
        }
        return $ret;
    }

    /**
     * Move Redis Post View Counts into WPDB
     *
     * 1. We fetch values from Redis
     *  1.a. Redis will copy the key and we're cleaning that up
     * 2. Attempt to copy data to WPDB
     * 3. Update redis with updated data
     */
    public function process_redis_posts_view_count(){
        $start = microtime(true);
        N24TV::log(' *** Running process redis posts view count');
        try {
            // check if redis is available at all
            $Redis = N24TV_Redis::getInstance();
            if ($Redis->isConnected()){
                $redis_data = $Redis->get_processing_posts_view_count();
                if (!is_array($redis_data)){
                    throw new Exception('Redis did not return an array for processing');
                }
                if (count($redis_data) > 0){
                    // convert post:ID keys to ID keys
                    $data = array();
                    foreach($redis_data as $key => $value){
                        $tmp = explode(':', $key);
                        if (count($tmp) == 2 && $tmp[0] == 'post' && $tmp[1] > 0){
                            $data[$tmp[1]] = $value;
                        }
                        else {
                            N24TV::log('process_redis_post_view_count: Invalid member returned from redis: ' . $key);
                        }
                    }
                    $processed = N24TV_PostView::update_multiple($data);
                    $data_count = count($data);
                    $processed_count = count($processed);
                    N24TV::log('Total posts view count: ' . $data_count . ' processed posts view count: ' . $processed_count);
                    if ($data_count == $processed_count){
                        $Redis->delete_processing_posts_view_key();
                    }
                    else {
                        $ret = $Redis->update_processing_posts_view_key($processed);
                        if ($ret != $processed_count){
                            throw new Exception('Failed to update processed redis posts view count: ret=' . $ret . ' processed_count=' . $processed_count);
                        }
                    }
                    return $processed_count;
                }
            }
            else {
                throw new Exception('process_redis_post_views_count: Redis connection not available');
            }
        } catch (Exception $e){
            N24TV::log('process_redis_post_views_count: ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
            return false;
        }
        N24TV::log('process_redis_posts_view_count: Done in ' . number_format(microtime(true) - $start, 5) . ' second(s)');
    }

    /**
     * Transition post status action hook
     *
     * If old status was publish and new status is something else, it means that post
     * is not longer published, so we need to remove it from cache.
     *
     * @param string $new_status
     * @param string $old_status
     * @param WP_Post $post
     */
    public function transition_post_status($new_status, $old_status, $post){
        // have we moved from publish to something else?!
        self::log_post_changes('transition_post_status: new_status=' . $new_status . ' old_status=' . $old_status . ' post=' . $post->ID);
        if ($old_status == 'publish' && $new_status != 'publish'){
            /**
             * @todo clear cache
             */
            self::log_post_changes('transition_post_status: !! POST STATUS HAS TRANSITIONED FROM PUBLISH TO ' . $new_status . ' !!!');
            N24TV::schedule_event(N24TV::$update_event);
        }
    }

    public function publish_future_post($post_id){
        self::log_post_changes('publish_future_post: post_id=' . $post_id);
        self::log_post_changes(' !!! schedule update event !!! ');
        N24TV::schedule_event(N24TV::$update_event);
    }

    /**
     * Save post action hook.
     *
     * This will catch only published post. Any changes.
     *
     * @param int $post_id The post ID.
     * @param post $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function save_post($post_id, $post, $update){
        self::log_post_changes('save_post: post_id: ' . $post_id . ' post=' . $post->ID . ' update=' . ($update === true ? 'YES' : 'NO'));
        if (get_post_status($post_id) != 'publish'){
            return;
        }
        /**
         * post was updated or created. We don't care really. It's published.
         *
         * @todo Clear cache
         */
        self::log_post_changes('save_post: !! POST IS PUBLISHED AND IT WAS CHANGED !!');
        N24TV::schedule_event(N24TV::$update_event);
    }

    /**
     * Admin menu hook
     */
    public function admin_menu(){
        require(__DIR__ . '/partials/plugin-n24tv-display.php');
        add_posts_page('Vrstni red novic', 'Vrstni red novic', 'publish_posts', 'n24tv-featured-posts', 'n24tv_admin_featured_posts_display');
    }

    /**
     * Change admin view posts column
     */
    public function manage_posts_columns($defaults){
        $defaults['n24tv_views'] = 'Views';
        return $defaults;
    }

    public function manage_posts_custom_column($column_name, $post_id){
        if($column_name == 'n24tv_views'){
            /**
             * Attempt to get post view count
             */
            try {

                $meta_count = (int)N24TV_PostView::get_post_view_count($post_id);
                $Redis = N24TV_Redis::getInstance();
                $redis_count = (int)$Redis->get_post_view_count($post_id);
                


                echo ($meta_count + $redis_count);

            } catch(Exception $e){
                N24TV::log('manage_posts_custom_column: ' . get_class($e) . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * AJAX action to get latest posts of category
     */
    public function ajax_get_latest_posts(){
        $cat_id = (int)($_POST['cat_id'] ?? 0);
        $title_like = ($_POST['title'] ?? '');
        $title_like = trim($title_like);

        $args = array(
        'numberposts' => 15,
        'offset' => 0,
        'category' => $cat_id,
        'orderby' => 'post_date',
        'order' => 'DESC',
        'include' => '',
        'exclude' => '',
        'meta_key' => '',
        'meta_value' =>'',
        'post_type' => 'post',
        'post_status' => 'publish, future, pending, private',
        'suppress_filters' => false
        );

        if (!empty($title_like)){
            $args['post_title_like'] = $title_like;
        }

        $recents = wp_get_recent_posts( $args, ARRAY_A );

        $ret = array();

        foreach($recents as $post){
            $ret[] = [
                'id'        => $post['ID'], 
                'title'     => $post['post_title'], 
                'status'    => $post['post_status']
            ];
        }

        echo json_encode($ret);

        wp_die();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook = NULL) {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in N24TV_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The N24TV_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        global $wp_scripts; // to get jQueryui-core Id

        if ($hook == 'posts_page_n24tv-featured-posts'){

            wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/n24tv-admin.css', array(), $this->version, 'all' );

            $ui = $wp_scripts->query('jquery-ui-core');
            // tell WordPress to load the Smoothness theme from Google CDN
            $protocol = is_ssl() ? 'https' : 'http';
            $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
            wp_enqueue_style('jquery-ui-smoothness', $url, false, null);
        }


    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook = NULL) {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in N24TV_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The N24TV_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        if ($hook == 'posts_page_n24tv-featured-posts'){
            wp_enqueue_script("jquery-ui-draggable");
            wp_enqueue_script("jquery-ui-sortable");
            wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/n24tv-admin.js', array( 'jquery' ), $this->version, true );
        }

    }

}
