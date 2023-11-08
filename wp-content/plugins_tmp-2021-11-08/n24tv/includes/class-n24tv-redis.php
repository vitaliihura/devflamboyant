<?php

/**
 * Our Redis handling class
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    N24TV
 * @subpackage N24TV/includes
 */

/**
 * Redis handling class
 *
 * This class provides all the functions we need for redis.
 *
 * @since      1.0.0
 * @package    N24TV
 * @subpackage N24TV/includes
 * @author     Your Name <email@example.com>
 */
class N24TV_Redis {

    /**
     * N24TV_Redis object instance
     */
    static private $Instance = NULL;

    /**
     * Our Redis connect instance
     *
     * null = no connection was tried
     * false = connection not available
     * object = Connection OK
     */
    static private $redis    = NULL;

    /**
     * List of stored posts in redis
     */
    static private $posts_key = 'n24tv_posts';
    /**
     * Each post is represented here as has
     * $post_key:ID
     */
    static private $post_key = 'n24tv_post';
    /**
     * List of stored categories
     */
    static private $categories_key = 'n24tv_categories';
    /**
     * Each category is represented as a set in $category_key:$id
     */
    static private $category_key = 'n24tv_category';
    /**
     * List of stored tags
     */
    static private $tags_key = 'n24tv_tags';
    /**
     * Each tag is represented as set in $tag_key:$tag
     */
    static private $tag_key = 'n24tv_tag';
    /**
     * List of stored authors
     */
    static private $authors_key = 'n24tv_authors';
    /**
     * Each author is represented as set in $author_tag:$author (id)
     */
    static private $author_key = 'n24tv_author';
    /**
     * Our active post views key with sorted set
     */
    static private $post_views_key = 'n24tv_post_views';
    /**
     * Our processing post views key with sorted set
     */
    static private $process_post_views_key = 'n24tv_process_post_views';
    /**
     * Out top posts key (simple set)
     *
     * Here are the Id's. We then fetch hash from post:id
     */
    static private $top_posts_key = 'n24tv_top_posts';
    /**
     * Latest posts key (simple set)
     * Here are the Id's. We then fetch from post:id key
     */
    static private $latest_posts_key = 'n24tv_latest_posts';
    /**
     * Latest posts categories keys (simple set)
     */
    static private $latest_posts_categories_key = 'n24tv_latest_posts_categries';
    /**
     * Featured posts
     */
    static public $featured_posts_key = 'n24tv_featured_posts';
    /**
     * Featured posts categories keys (simple set)
     *
     * If Numeric it's category Id, otherwise it's a custom set
     */
    static public $featured_posts_categories_key = 'n24tv_featured_posts_categories';
    /**
     * Most comments on posts in last 7 days
     */
    static public $top_comments_posts_key = 'n24tv_top_comments_posts';

    /**
     * Get instance
     */
    static public function getInstance(){
        if (self::$Instance === NULL){
            self::$Instance = new self;
            self::$Instance->connect();
        }
        return self::$Instance;
    }

    static protected function log($msg, $type = 'DEBUG'){
        // file_put_contents(__DIR__ . '/../log/n24tv.log', date('Y-m-d H:i:s') . ': ' . $type . ': ' . $msg . "\n", FILE_APPEND); // TODO: remove this - Ziga
        return N24TV::log('REDIS: ' . $msg, $type);
    }

    public function __construct(){
        $this->connect();
    }

    public function isConnected(){
        return (class_exists('Redis') && self::$redis instanceof Redis);
    }

    public function debug_keys(){
        if ($this->isConnected()){
            $redis = self::$redis;
            $keys = array(
                self::$post_views_key           => 'Current post view count cache', 
                self::$process_post_views_key   => 'Processing post view count cache',
                self::$categories_key           => 'All categories stored', 
                self::$top_posts_key            => 'Top posts set', 
                self::$latest_posts_key         => 'Latest posts set',
                self::$top_comments_posts_key   => 'Top commented posts in last 7 days',
                self::$posts_key                => 'All posts data stored in Redis',
                self::$tags_key                 => 'All tags stored (for related search)',
                self::$authors_key              => 'All authors stored (for related search)'
            );
            $ret = array();
            foreach($keys as $key => $descr){
                // difference between set and sorted set
                switch($key){
                    case self::$post_views_key:
                    case self::$process_post_views_key:
                        $rdata = $redis->zrange($key, 0, -1, true);
                        $data = array('count' => count($rdata), 'sum' => 0, 'posts' => array(), 'descr' => $descr);
                        foreach($rdata as $post_id_key => $count){
                            $data['posts'][$post_id_key] = $count;
                            $data['sum'] += $count;
                        }
                        $ret[$key] = $data;
                        break;
                    case self::$categories_key:
                    case self::$tags_key:
                    case self::$authors_key:
                    case self::$posts_key:
                        $rdata = $redis->smembers($key);
                        $data = array('count' => count($rdata), 'posts' => array(), 'descr' => $descr);
                        $ret[$key] = $data;
                        break;
                    case self::$top_comments_posts_key:
                    case self::$top_posts_key:
                        $rdata = $redis->smembers($key);
                        $data = array('count' => count($rdata), 'posts' => array(), 'descr' => $descr);
                        foreach($rdata as $member){
                            $post = $this->get_post($member);
                            $data['posts'][$member] = $post;
                            uasort($data['posts'], function($a, $b){
                                if ($a['top_comment_count'] == $b['top_comment_count']) return 0;
                                return ($a['top_comment_count'] >  $b['top_comment_count'] ? -1 : 1);
                            });
                        }
                        $ret[$key] = $data;
                        break;
                    case self::$latest_posts_key:
                        $cat_ids_key = self::$latest_posts_categories_key;
                        $cat_ids = $redis->smembers($cat_ids_key);
                        $ret[$cat_ids_key] = array('count' => count($cat_ids), 'descr' => 'List of categories for top posts');

                        foreach(array(0) as $cat_id){
                            $top_posts_key = self::$latest_posts_key . ':' . $cat_id;
                            $top_posts = $redis->smembers($top_posts_key);
                            $posts = array();
                            if ($cat_id == 0){
                                foreach($top_posts as $member){
                                    $post = $this->get_post($member);
                                    $posts[$member] = $post;
                                }
                            }
                            $data = array('count' => count($top_posts), 'posts' => $posts, 'descr' => 'Top posts for category: ' . $cat_id);
                            $ret[$top_posts_key] = $data;
                        }

                        /*
                        $rdata = $redis->smembers($key);
                        $data = array('count' => count($rdata), 'posts' => array(), 'descr' => $descr);
                        foreach($rdata as $member){
                            $post_key = 'post:' . $member;
                            $post = $redis->hgetall($post_key);
                            $data['posts'][$member] = $post;
                        }
                        $ret[$key] = $data;
                        */
                        break;
                }
            }
            return $ret;
        }
        return array();
    }

    /**
     * Attempt to connect to Redis
     *
     * @todo Get connection details from settings
     */
    public function connect(){
        if (self::$redis === NULL){
            /** 
             * @todo Get from settings
             */
            $host = 'redis';
            $port = 6379;
            $db = 1;

            self::log('Fetching new Redis instance');
            self::$redis = false;
            if (class_exists('Redis')){
                try {
                    self::log('Constructing Redis object');
                    $Redis = new Redis;
                    self::log('Connecting to ' . $host . ':' . $port . ' db=' . $db);
                    if ($Redis->pconnect($host, $port)){
                        self::log('Connected to Redis: ' . $host . ':' . $port);
                        if ($Redis->select(1)){
                            self::log('Database selected: id=' . $db);
                            self::$redis = $Redis;
                            self::log('Redis connected successfuly created');
                        }
                        else {
                            throw new Exception('Redis select DB failed: db=' . $db);
                        }
                    }
                    else {
                        throw new Exception('Redis connection failed to: ' . $host . ':' . $port);
                    }
                } catch (Exception $e){
                    self::log('Exception while creating Redis: ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
                }
            }
            else {
                self::log('Redis class does not exist', 'ERROR');
            }
        }
        self::log('Redis connection: ' . (self::$redis === false ? 'FALSE' : get_class(self::$redis)));
    }

    /** 
     * this will insert/update post data key
     *
     * key: post:id: hash
     * key: n24tv_posts simple set with list of stored posts
     */
    protected function write_post($post){
        try {
            if ($this->isConnected()){
                $redis = self::$redis;
                if (!is_array($post))
                    throw new Exception('Provided post is not an array: ' . print_r($post, true));
                if (empty($post) || !isset($post['id']))
                    throw new Exception('Provided post array is empty or is missing id key');
                $post_id = (int)$post['id'];
                if (empty($post_id))
                    throw new Exception('Provided post array holds invalid post id: ' . $post_id);

                $key = self::$post_key . ':' . $post_id;
                if ($redis->hmSet($key, $post) === false){
                    throw new Exception('Failed to write post: ' . $post_id . ' to key: ' . $key);
                }
                $key = self::$posts_key;
                /**
                 * @todo: Check the return value, it's confusing in documentation
                 */
                $ret = $redis->sAdd($key, $post_id);
                return true;
            }
        } catch (Exception $e){
            self::log('write_post: ERROR: ' . $e->getMessage(), 'ERROR');
            return false;
        }
        return false;
    }

    public function get_post($id){
        static $arrCache = array();
        if (isset($arrCache[$id])){
            return $arrCache[$id];
        }
        $ret = array();
        try {
            if ($this->isConnected()){
                $redis = self::$redis;
                $id = (int)$id;
                if (empty($id))
                    throw new Exception('Provided post Id is not valid: ' . $id);
                $key = self::$post_key . ':' . $id;
                $ret = $redis->hGetAll($key);
                if (!empty($ret)){
                    $ret['tags'] = explode(',', $ret['tags']);
                    $ret['categories'] = explode(',', $ret['categories']);
                    $ret['DateTime'] = new DateTime($ret['date']);
                    $arrCache[$id] = $ret;
                }
            }
        } catch (Exception $e){
            self::log('get_post: ERROR: ' . $e->getMessage(), 'ERROR');
        }
        return $ret;
    }

    protected function write_tag($tag, $post_ids){
        try {
            if ($this->isConnected()){
                $redis = self::$redis;
                $key = self::$tag_key . ':' . $tag;
                $tag = trim($tag);
                if (empty($tag))
                    throw new Exception('Provided tag is empty');
                if (!is_array($post_ids))
                    throw new Exception('Provided post_ids is not an array');
                if (count($post_ids) < 2){
                    throw new Exception('Tag: ' . $tag . ' has only ' . count($post_ids) . ' post(s) related');
                }
                $call_array = array($key) + $post_ids;
                $ret = call_user_func_array(array($redis, 'sadd'), $call_array);

                $key = self::$tags_key;
                $ret = $redis->sadd($key, $tag);
                return true;
            }
        } catch (Exception $e){
            self::log('write_tag: ERROR: ' . $e->getMessage(), 'ERROR');
            return false;
        }
        return false;
    }

    protected function write_author($author, $post_ids){
        try {
            if ($this->isConnected()){
                $redis = self::$redis;
                $key = self::$author_key . ':' . $author;
                $author = (int)$author;
                if (empty($author))
                    throw new Exception('Provided author is empty');
                if (!is_array($post_ids)){
                    throw new Exception('Provided post_ids is not an array: ' . $post_ids);
                }
                if (count($post_ids) < 2){
                    throw new Exception('Author: ' . $author . ' has only ' . count($post_ids) . ' post(s) related');
                }
                $call_array = array($key) + $post_ids;
                $ret = call_user_func_array(array($redis, 'sadd'), $call_array);

                $key = self::$authors_key;
                $ret = $redis->sadd($key, $author);
                return true;
            }
        } catch (Exception $e){
            self::log('write_author: ERROR: ' . $e->getMessage(), 'ERROR');
            return false;
        }
        return false;
    }

    public function get_category($id){
        $id = (int)$id;
        if ($id == 0)
            throw new Exception('Provided category Id is not valid: ' . $id);
        if ($this->isConnected()){
            try {
                $redis = self::$redis;
                $key = self::$category_key . ':' . $id;
                $ret = $redis->hGetAll($key);
                if (!empty($ret) && !empty($ret['children'])){
                    $ret['children'] = explode(',', $ret['children']);
                }
                return $ret;
            } catch (Exception $e){
                self::log('get_category(): ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
                return false;
            }
        }
        return false;
    }

    public function get_categories(){
        $ret = array();
        if ($this->isConnected()){
            try {
                $redis = self::$redis;
                $key = self::$categories_key;
                foreach($redis->smembers($key) as $cat_id){
                    $ret[$cat_id] = $this->get_category($cat_id);
                }
            } catch (Exception $e){
                self::log('get_categories(): ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
                return false;
            }
        }
        return $ret;
    }

    public function write_category($category){
        $ret = false;
        if (!is_array($category))
            throw new Exception('Provided category is not an array');
        if (!isset($category['id']))
            throw new Exception('Provided category array is missing id key');
        if ($this->isConnected()){
            $redis = self::$redis;
            $id = (int)$category['id'];
            $key = self::$category_key . ':' . $id;
            $category['children'] = implode(',', $category['children']);
            if ($redis->hmSet($key, $category) === false){
                throw new Exception('Failed to write category: ' . $id . ' to key: ' . $key);
            }
            return true;
        }
        return false;
    }

    public function write_categories($categories){
        if ($this->isConnected()){
            try {
                $redis = self::$redis;

                $category_ids = array();

                foreach($categories as $cat){
                    if ($this->write_category($cat)){
                        $category_ids[] = $cat['id'];
                    }
                }

                $redis->delete(self::$categories_key);
                if (count($category_ids) > 0){
                    $call_array = array(self::$categories_key) + $category_ids;
                    $ret = call_user_func_array(array($redis, 'sadd'), $call_array);
                }
                return true;
            } catch (Exception $e){
                self::log('write_categories(): ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
                return false;
            }
        }
        return false;
    }

    /**
     * Write top posts to Redis
     *
     * @todo post:id keys do not get deleted!!
     */
    public function write_top_posts($posts){
        self::log('Writing new top posts to redis: data: ' . print_r($posts, true));
        if ($this->isConnected()){
            $redis = self::$redis;
            $key = self::$top_posts_key;

            $post_ids = array();

            // write hashes of posts
            foreach($posts as $post){

                if ($this->write_post($post) === false){
                    //self::log('Failed to set post data on key: ' . $post_key, 'ERROR');
                }
                else {
                    //self::log('Post key successfuly set: ' . $post_key);
                    $post_ids[] = $post['id'];
                }
            }
            $count = 0;
            if (count($post_ids) > 0){
                self::log('Replacing data in key: ' . $key . ' with ' . print_r($post_ids, true));
                $redis->delete($key);
                foreach($post_ids as $post_id){
                    $ret = $redis->sAdd($key, $post_id);
                    if ($ret == 0){
                        self::log('write_top_posts: Failed to add post id into key: ' . $key, 'ERROR');
                    }
                    else {
                        ++$count;
                    }
                }
            }
            return $count;
        }
        return false;
    }

    public function write_top_comments_posts($posts){
        self::log('Writing new top comments posts: data: ' . print_r($posts, true));
        if ($this->isConnected()){
            $redis = self::$redis;
            $key = self::$top_comments_posts_key;

            $post_ids = array();

            foreach($posts as $post){
                if ($this->write_post($post) === false){
                    //self::log('write_top_comments_posts: Failed to set post data on key: ' . $post_key, 'ERROR');
                }
                else {
                    $post_ids[] = $post['id'];
                }
            }
            $count = 0;
            if (count($post_ids) > 0){
                self::log('Replacing data in key: ' . $key . ' with ' . print_r($post_ids, true));
                $redis->delete($key);
                foreach($post_ids as $post_id){
                    $ret = $redis->sAdd($key, $post_id);
                    if ($ret == 0){
                        self::log('write_top_comments_posts: Failed to add post id into key: ' . $key, 'ERROR');
                    }
                    else {
                        ++$count;
                    }
                }
            }
            else {
                $redis->del($key);
            }
            return $count;
        }
        return false;
    }

    /**
     * Write latest posts to Redis
     *
     * @todo post:id keys do not get deleted!
     */
    public function write_latest_posts($categories){
        if ($this->isConnected()){
            $redis = self::$redis;
            $key = self::$latest_posts_key;
            $category_ids = array();
            $count = 0;
            foreach($categories as $cat_id => $posts){
                $post_ids = array();
                // write hashes of posts
                foreach($posts as $post){
                    if ($this->write_post($post) === false){
                        //self::log('Failed to set post data on key: ' . $post_key, 'ERROR');
                    }
                    else {
                        $post_ids[] = $post['id'];
                    }
                }
                if (count($post_ids) > 0){
                    $cat_key = $key . ':' . $cat_id;
                    self::log('Replacing data in key: ' . $cat_key . ' with ' . print_r($post_ids, true));
                    $redis->delete($cat_key);
                    foreach($post_ids as $post_id){
                        $ret = $redis->sAdd($cat_key, $post_id);
                        if ($ret == 0){
                            self::log('write_latest_posts: Failed to add post id into key: ' . $key, 'ERROR');
                        }
                        else {
                            $category_ids[$cat_id] = $cat_id;
                            ++$count;
                        }
                    }
                }
            }
            if (count($category_ids)){
                $key = self::$latest_posts_categories_key;
                $redis->delete($key);
                foreach($category_ids as $cat_id){
                    self::log('Adding category_id=' . $cat_id . ' to key: ' . $key);
                    $redis->sAdd($key, $cat_id);
                }
            }
            return $count;
        }
        return false;
    }

    public function write_posts_taxonomy_data($data){
        if ($this->isConnected()){
            $posts = array();
            if (isset($data['tags']) && is_array($data['tags'])){
                foreach($data['tags'] as $tag => $tag_posts){
                    $tag = trim($tag);
                    if (!empty($tag) && count($tag_posts) > 1){
                        $ret = $this->write_tag($tag, $tag_posts);
                        if ($ret === false){
                            self::log('write_post_taxonomy_data: tag: Failed to write tag: ' . $tag . ' with ' . count($tag_posts) . ' post(s)');
                        }
                        else {
                            foreach($tag_posts as $post_id){
                                $posts[$post_id] = $post_id; // so we'll store them when we finish
                            }
                        }
                    }
                }
            }
            if (isset($data['authors']) && is_array($data['authors'])){
                foreach($data['authors'] as $author => $author_posts){
                    $author = (int)$author;
                    if ($author > 0 && count($author_posts) > 1){
                        $ret = $this->write_author($author, $author_posts);
                        if ($ret === false){
                            self::log('write_post_taxonomy_data: author: Failed to write author: ' . $author . ' with ' . count($author_posts) . ' post(s)');
                        }
                        else {
                            foreach($author_posts as $post_id){
                                $posts[$post_id] = $post_id;
                            }
                        }
                    }
                }
            }

            if (count($posts) > 0){
                foreach($posts as $post_id){
                    $post_data = N24TV_PostView::get_post_data($post_id);
                    if ($post_data !== false){
                        if ($this->write_post($post_data) === false){
                            self::log('write_post_taxonomy_data: posts: Failed to write post: ' . (isset($post['id']) ? $post['id'] : 'na'));
                        }
                    }
                }
            }

            return true;
        }
        return false;
    }

    /**
     * Attempt to retreive top posts from redis
     */
    public function get_top_posts(){
        try {
            if ($this->isConnected()){
                $redis = self::$redis;
                $key = self::$top_posts_key;
                $ret = $redis->smembers($key);
                if (empty($ret)){
                    self::log('No members in set: ' . $key . '. Returning false');
                    return false;
                }
                else {
                    // received members
                    self::log('Received sMembers from key: ' . $key . ' members: ' . print_r($ret, true));
                    // now fetch post hash
                    $posts = $ret;
                    $ret = [];
                    foreach($posts as $post_id){
                        $post_data = $this->get_post($post_id);
                        if ($post_data !== false){
                            $ret[] = $post_data;
                        }
                        else {
                            self::log('No post data available for: ' . $post_id, 'ERROR');
                        }
                    }
                    if (count($posts) > 0){
                        self::log('Returning top posts: ' . print_r($ret, true));
                        return $ret;
                    }
                    else {
                        self::log('Failed to retreive atleast one top post. Returning false', 'ERROR');
                        return false;
                    }
                }
            }
        } catch (Exception $e){
            self::log('get_top_posts: ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
        }
        return false;
    }

    public function get_top_comments_posts(){
        try {
            if ($this->isConnected()){
                $redis = self::$redis;
                $key = self::$top_comments_posts_key;
                $ret = $redis->smembers($key);
                if (empty($ret)){
                    self::log('No members in set: ' . $key . '. Returning false');
                    return false;
                }
                else {
                    // received members
                    self::log('Received sMembers from key: ' . $key . ' members: ' . print_r($ret, true));
                    // now fetch post hash
                    $posts = $ret;
                    $ret = [];
                    foreach($posts as $post_id){
                        $post_data = $this->get_post($post_id);
                        if ($post_data !== false) {
                            $ret[] = $post_data;
                        }
                        else {
                            self::log('No post data available for id: ' . $post_id, 'ERROR');
                        }
                    }
                    if (count($posts) > 0){
                        usort($ret, function($a, $b){
                            if ($a['top_comment_count'] == $b['top_comment_count']) return 0;
                            return ($a['top_comment_count'] >  $b['top_comment_count'] ? -1 : 1);
                        });
                        self::log('Returning top posts: ' . print_r($ret, true));
                        return $ret;
                    }
                    else {
                        self::log('Failed to retreive atleast one top post. Returning false');
                        return false;
                    }
                }
            }
        } catch (Exception $e){
            self::log('get_top_posts: ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
        }
        return false;        
    }

    /**
     * Attempt to retreive top posts from redis
     *
     * This will always retreive only the published posts!
     */
    public function get_latest_posts($category_id = 0, $count = 4){
        try {
            if ($this->isConnected()){
                $redis = self::$redis;
                $key = self::$latest_posts_key . ':' . $category_id;
                $ret = $redis->smembers($key);
                if (empty($ret)){
                    self::log('No members in set: ' . $key);
                    if ($category_id > 0){
                        // try the .0 key
                        $key = self::$latest_posts_key . ':0';
                        $ret = $redis->smembers($key);
                        if (empty($ret)){
                            self::log('No members in set: ' . $key . '. Returning false');
                            return false;
                        }
                    }
                    else {
                        self::log('No member found in set: ' . $key . '. Returning false');
                        return false;
                    }
                }

                // received members
                self::log('Received sMembers from key: ' . $key . ' members: ' . print_r($ret, true));
                // now fetch post hash
                $posts = $ret;
                $ret = [];
                foreach($posts as $post_id){

                    $post_data = $this->get_post($post_id);
                    if ($post_data !== false){
                        $ret[] = $post_data;
                    }
                    /*
                    if (count($ret) >= $count)
                        break;
                    */
                }
                if (count($posts) > 0){
                    usort($ret, function($a, $b){
                        if ($a['DateTime'] == $b['DateTime']) return 0;
                        return ($a['DateTime'] < $b['DateTime']) ? 1 : -1;
                    });
                    //self::log('Returning latest posts: ' . print_r($ret, true));
                    return array_slice($ret, 0, $count);
                }
                else {
                    self::log('Failed to retreive atleast one latest post. Returning false');
                    return false;
                }
            }
        } catch (Exception $e){
            self::log('get_latest_posts: ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
        }
        return false;
    }

    public function get_related_posts_by_tags($tags, $skip_post_id = null, $count = 5){
        $ret = false;


        if ($this->isConnected() && is_array($tags) && count($tags) > 0 && $count > 0){
            $redis = self::$redis;
            $search_tags_count = count($tags);

            // SINTER tag1 tag2 ... returns posts that are tagged by both tags
            // SUNION tag1 tag2 ... return posts that are tagged by either tags
            $keys = array();
            foreach($tags as $tag){
                $keys[] = self::$tag_key . ':' . $tag;
            }

            $union_post_ids = $redis->sunion($keys);
            if (count($union_post_ids) == 0){
                return false;
            }
            $tmp = $union_post_ids;
            $posts = array();
            foreach($tmp as $post_id){
                if ($post_id == $skip_post_id)
                    continue;
                $post = $this->get_post($post_id);
                if ($post !== false){
                    /**
                     * Relavance of the post based on search
                     *
                     * How many of all tags are matched
                     */
                    $matched_tags = array_intersect($tags, $post['tags'] ?? []);
                    $relevance = count($matched_tags) / $search_tags_count;
                    //self::log('Search: ' . count($tags) . ' matches: ' . implode(', ', $matched_tags) . ' relevance: ' . number_format($relevance, 6));
                    $post['relevance'] = $relevance;
                    $posts[] = $post;
                }
            }

            $posts = $this->_sort_related_posts($posts);

            if (count($posts) > $count){
                $ret = array_slice($posts, 0, $count);
            }
            else {
                $ret = $posts;
            }
        }

        return $ret;
    }

    private function _sort_related_posts($posts){

        if (count($posts) < 2){
            return $posts;
        }
        $sort = array();
        foreach($posts as $k => $v){
            $sort['relevance'][$k] = $v['relevance'];
            $sort['DateTime'][$k] = $v['DateTime'];
        }

        array_multisort($sort['relevance'], SORT_DESC, $sort['DateTime'], SORT_DESC, $posts);

        return $posts;
    }

    public function get_related_posts_by_author($author_id, $skip_post_id, $tags = NULL, $count = 5){
        $ret = false;
        $author_id = (int)$author_id;
        if ($this->isConnected() && !empty($author_id) && $count > 0){
            $redis = self::$redis;

            // first, let's fetch all the articles of the author, which is pretty straight forward
            $key = self::$author_key . ':' . $author_id;
            $author_post_ids = $redis->smembers($key);

            $search_tags_count = (is_array($tags) ? count($tags) : 0);

            $posts = array();
            /**
             * Now, we have to load all posts_key and check tag relevance if tags were provided.
             */
            foreach($author_post_ids as $post_id){
                if ($post_id == $skip_post_id){
                    continue;
                }
                $post = $this->get_post($post_id);
                if (is_array($post) && isset($post['tags']) && is_array($post['tags'])){
                    $relevance = 0;
                    if ($search_tags_count > 0){
                        $matched_tags = array_intersect($tags, $post['tags']);
                        $relevance = count($matched_tags) / $search_tags_count;
                    }
                    $post['relevance'] = $relevance;
                    $posts[] = $post;
                }
            }
            $posts = $this->_sort_related_posts($posts);

            if (count($posts) > $count){
                $ret = array_slice($posts, 0, $count);
            }
            else {
                $ret = $posts;
            }
        }
        return $ret;
    }

    /**
     * Register post view count in redis
     */
    public function post_view_count($post_id){
        $post_id = (int)$post_id;
        $start = microtime(true);
        $ret = false;

        if ($post_id > 0 && $this->isConnected()){
            try {
                $key = self::$post_views_key;
                self::log('post_view(' . $post_id . '): Register new post view count in key: ' . $key);
                $redis = self::$redis;
                $ret = $redis->zIncrBy($key, 1, 'post:' . $post_id);
                if ($ret < 1){
                    throw new Exception('zIncrBy:' . $key . ' failed: ret: ' . $ret);
                }
                else {
                    self::log('post_view(' . $post_id . ') count updated to: ' . $ret . ' in ' . number_format(microtime(true) - $start, 5) . 's');
                }
            } catch (Exception $e){
                self::log('post_view(' . $post_id . '): Error: ' . get_class($e) . ': ' . $e->getMessage(), 'ERROR');
            }

        }

        // if it took more that 1ms, notify via ERROR log
        if (microtime(true) - $start > 0.001){
            self::log('post_view(' . $post_id . '): took: ' . number_format(microtime(true)-$start, 5) . ' second(s)!!!!', 'ERROR');
        }

        return $ret;
    }

    /**
     * Get post view count for single post
     */
    public function get_post_view_count($post_id){
        $post_id = (int)$post_id;
        $ret = 0;
        if ($post_id > 0 && $this->isConnected()){
            $redis = self::$redis;
            $key = self::$post_views_key;
            $member = 'post:' . $post_id;

            $score = $redis->zScore($key, $member);

            $ret = (int)$score;

        }
        return $ret;
    }

    /**
     * Get current posts view count
     */
    public function get_posts_view_count(){
        $ret = array();
        if ($this->isConnected()){
            $key = self::$post_views_key;
            self::log('posts_view_count: Fetching from key: ' . $key);
            $redis = self::$redis;
            $ret = $redis->zRange($key, 0, -1, true);
            if (is_array($ret)){
                self::log('posts_view_count: success: ' . print_r($ret, true));
            }
            else {
                self::log('posts_view_count: Failed: ' . print_r($ret, true));
                $ret = array();
            }
        }
        return $ret;
    }

    /**
     * Get current posts view count for processing
     *
     * This method will check if process_post_views_key is present.
     * If process_post_views_key is not present, we simply rename the 
     * posts_view_key to process_post_views_key and return it's value.
     *
     * If process_post_views_key is available, it means that we failed to 
     * update all in our WPDB post view database.
     */
    public function get_processing_posts_view_count(){
        $ret = [];
        if ($this->isConnected()){
            $process_key = self::$process_post_views_key;
            $key = self::$post_views_key;
            $redis = self::$redis;

            self::log('processing_posts_view_count: Fetching key=' . $key . ' process_key=' . $process_key);

            if ($redis->exists($process_key)){
                self::log('processing_posts_view_count: Process key is available!');
                $ret = $redis->zRange($process_key, 0, -1, true);
                if (!is_array($ret)){
                    self::log('processing_posts_view_count: process key zRange did not return array: ' . print_r($ret, true), 'ERROR');
                    $ret = [];
                }
            }
            else {
                self::log('processing_posts_view_count: Process key is NOT available. Rename ' . $key . ' to ' . $process_key);
                if ($redis->rename($key, $process_key) === false){
                    self::log('processing_posts_view_count: Failed to rename key ' . $key . ' to ' . $process_key);
                }
                else {
                    $ret = $redis->zRange($process_key, 0, -1, true);
                    if (!is_array($ret)){
                        self::log('processing_posts_view_count: process key zRange did not return array after key rename: ' . print_r($ret, true));
                        $ret = [];
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Delete processing posts view count key
     */
    public function delete_processing_posts_view_key(){
        $ret = false;
        if ($this->isConnected()){
            $key = self::$process_post_views_key;
            $redis = self::$redis;
            $ret = $redis->delete($key);
            self::log('Delete process posts view key: ' . $key . ': ' . ($ret > 0 ? 'success' : 'FAILED'), ($ret > 0 ? 'DEBUG' : 'ERROR'));
        }
        return $ret;
    }

    /**
     * Update processing posts view count key
     *
     * We delete the sorted set members found in data (post_id, post_id, ...)
     *
     * @return int Successsfuly deleted count.
     */
    public function update_processing_posts_view_key($data){
        $count = 0;
        if ($this->isConnected()){
            $key = self::$process_post_views_key;
            $redis = self::$redis;
            foreach($data as $post_id){
                $member = 'post:' . $post_id;
                if ($redis->zDelete($key, $member)  < 1){
                    self:log('Failed to delete member: ' . $member . ' for key: ' . $key, 'ERROR');
                }
                else {
                    ++$count;
                }
            }
        }
        return $count;
    }

}
