<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    N24TV
 * @subpackage N24TV/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    N24TV
 * @subpackage N24TV/public
 * @author     Your Name <email@example.com>
 */
class N24TV_Public {

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

    /**
     * Redis instance
     */


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

    /**
     * AJAX Post View event
     *
     * Register post view in Redis
     */
    public function post_view(){
        check_ajax_referer('n24tv_ajax_post_view_nonce');
        $id = (isset($_POST['id']) ? $_POST['id'] : 0);
        if ($id > 0){

            try {
                $Redis = N24TV_Redis::getInstance();
                $Redis->post_view_count($id);
            } catch (Exception $e){
                N24TV::log('AJAX Post view error: ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
            }

            // reply with new nonce
            echo json_encode(
                array(
                    'nonce' => wp_create_nonce( 'n24tv_ajax_post_view_nonce' )
                )
            ); 
        }
        wp_die();
    }

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

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


		//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/n24tv-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

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
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/n24tv-public.js', array( 'jquery' ), $this->version, true );
        wp_localize_script( $this->plugin_name, 'n24tv_ajax',
            array( 
                'url'   => admin_url( 'admin-ajax.php' ),
                'id'    => (is_single() ? get_the_id() : 0),
                'nonce' => (is_single() ? wp_create_nonce( 'n24tv_ajax_post_view_nonce' ) : false)
            )
        );

	}

}
