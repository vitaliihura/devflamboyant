<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    N24TV
 * @subpackage N24TV/includes
 */

/**
 * Fired during plugin activation.
 *
 * Featured posts implementation
 *
 * @since      1.0.0
 * @package    N24TV
 * @subpackage N24TV/includes
 * @author     Your Name <email@example.com>
 */
class N24TV_Featured {

    const DATA_FILE = __DIR__ . '/../data/featured.1.data';

    static private $Instance = null;

    protected $categories = null;
    protected $data = null;
    protected $posts = null;

    static public function log($msg, $type = 'DEBUG'){
        N24TV::log('FEATURED: ' . $msg, $type);
    }

    static public function getInstance(){
        if (self::$Instance === NULL){
            self::$Instance = new self;
        }
        return self::$Instance;
    }

    protected function __construct(){

    }

    public function get($category_id){
        $int_cat_id = $this->get_int_category_id($category_id);
        if ($int_cat_id === false){
            return false;
        }
        $ret = array();
        foreach($this->data[$int_cat_id] as $post_id){
            if (isset($this->posts[$post_id]) && $this->posts[$post_id]['status'] == 'publish'){
                $post = $this->posts[$post_id];
                $post['DateTime'] = new DateTime($post['date']);
                $post['tags'] = explode(',', $post['tags']);
                $post['categories'] = explode(',', $post['categories']);
                $ret[] = $post;
            }
        }
        return $ret;
    }

    // get simple data for presentation
    public function get_data(){
        $this->load();
        $ret = array();
        foreach($this->categories as $int_cat_id => $cat_id){
            $cdata = array('category_id' => $cat_id, 'posts' => array());
            foreach($this->data[$int_cat_id] as $post_id){
                $cdata['posts'][$post_id] = $this->posts[$post_id];
            }
            $ret[] = $cdata;
        }
        return $ret;
    }

    public function category_exists($cat_id){
        $cat_id = trim($cat_id);
        if (empty($cat_id))
            return true;
        
        $this->load();

        foreach($this->categories as $_cat_id){
            if ($_cat_id == $cat_id){
                return true;
            }
        }
        return false;
    }

    public function get_int_category_id($cat_id){
        $this->load();

        $cat_id = trim($cat_id);
        if (empty($cat_id)){
            return false;
        }

        foreach($this->categories as $_int_cat_id => $_cat_id){
            if ($_cat_id == $cat_id){
                return $_int_cat_id;
            }
        }

        return false;
    }

    public function update_posts(){
        $this->load();
        if (!empty($this->posts)){
            foreach($this->posts as $post_id => $post){
                $post_data = N24TV_PostView::get_post_data($post_id);
                if ($post_data === false){
                    unset($this->posts[$post_id]);
                }
                else {
                    $this->posts[$post_id] = $post_data;
                }
            }
            return $this->write();
        }
    }

    public function update($cat_id, $posts){
        try {
            $cat_id = trim($cat_id);
            if (empty($cat_id) || !is_array($posts) || empty($posts))
                throw new Exception('update: Category id not valid or empty posts');

            $int_cat_id = $this->get_int_category_id($cat_id); // this will also load the file if not loaded
            if ($int_cat_id === false){
                // add 
                $this->categories[] = $cat_id;
                $int_cat_id = count($this->categories) - 1;
            }

            $post_ids = array();
            foreach($posts as $id){
                $id = (int)$id;
                if (empty($id))
                    continue;
                $post_data = N24TV_PostView::get_post_data($id);
                if ($post_data !== false){
                    $this->posts[$id] = $post_data;
                    $post_ids[] = $id;
                }
                else {
                    self::log('update: No post data for post id: ' . $id, 'ERROR');
                }
            }

            if (empty($post_ids)){
                throw new Exception('update: Post Ids array is empty');
            }

            $this->data[$int_cat_id] = $post_ids;
            return $this->write();
        } catch (Exception $e){
            self::log($e->getMessage(), 'ERROR');
            return false;
        }
    }

    public function delete($cat_id){
        try {
            $cat_id = trim($cat_id);
            if (empty($cat_id))
                throw new Exception('Category Id not provided');
            $int_cat_id = $this->get_int_category_id($cat_id);
            if ($int_cat_id === false){
                throw new Exception('Category does not exist');
            }
            else {
                unset($this->categories[$int_cat_id]);
                return $this->write();
            }
        } catch (Exception $e){
            self::log($e->getMessage(), 'ERROR');
            return false;
        }
    }

    protected function load(){
        // load was already attempted
        if (is_array($this->categories)){
            return;
        }

        $this->categories = array(); // notice that we tried to load

        $file = self::DATA_FILE;
        if (!file_exists($file)){
            self::log('load(): File: ' . $file . ' does not exist. Skipping ... ');
            return;
        }
        else {
            $serialized_data = file_get_contents($file);
            $data = unserialize($serialized_data);
            if (is_array($data) && 
                isset($data['categories']) && is_array($data['categories']) &&  
                isset($data['data']) && is_array($data['data']) &&  
                isset($data['posts']) && is_array($data['posts'])){

                $this->categories = $data['categories'];
                $this->data = $data['data'];
                $this->posts = $data['posts'];
            }
            else {
                self::log('load(): unserialize() failed. File=' . $file, 'ERROR');
            }
        }
    }

    public function reload(){
        $this->categories = null;
    }

    protected function write(){
        $file = self::DATA_FILE;
        // check if load was performed and if there is anything inside

        if ($this->categories === NULL){
            // no load performed
            return true;
        }

        if (empty($this->categories)){ // was load performed?
            //unlink($file);
            $this->categories = $this->posts = $this->data = null;
            // nothing to write, we removed the file
            return true;
        }

        /**
         * Before rewrite, let's reorder the keys to start from 0.
         */
        $old_cat = $this->categories;
        $old_data = $this->data;

        $this->categories = array();
        $this->data = array();

        $new_int_cat_id = 0;
        foreach($old_cat as $int_cat_id => $cat_id){
            $this->categories[$new_int_cat_id] = $cat_id;
            $this->data[$new_int_cat_id] = $old_data[$int_cat_id];
            ++$new_int_cat_id;
        }

        if (file_exists($file) && !is_writable($file))
            throw new Exception('File: ' . basename($file) . ' is not writable');

        $data = array(
                    'categories' => $this->categories, 
                    'data'       => $this->data, 
                    'posts'      => $this->posts
                );

        $ret = file_put_contents($file, serialize($data));

        return ($ret === false ? false : true);
    }
}
