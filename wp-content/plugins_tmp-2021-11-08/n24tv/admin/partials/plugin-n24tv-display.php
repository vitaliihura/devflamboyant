<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    N24TV
 * @subpackage N24TV/admin/partials
 */
?>


<?php
if (isset($_POST['n24tv-admin-featured-publish'])){

    $F = N24TV_Featured::getInstance();

    $cats = $_POST['n24tv_featured_posts'];

    $old_data = $F->get_data();

    if (is_array($cats)){
        foreach($cats as $cat_id => $posts){
            foreach($old_data as $int_cat_id => $d){
                if ($d['category_id'] == $cat_id){
                    unset($old_data[$int_cat_id]);
                }
            }
            $F->update($cat_id, $posts);
        }
    }

    foreach($old_data as $d){
        $F->delete($d['category_id']);
    }

}
?>

<?php

function n24tv_admin_get_latest_posts(){
    $args = array(
    'numberposts' => 20,
    'offset' => 0,
    'category' => 0,
    'orderby' => 'post_date',
    'order' => 'DESC',
    'include' => '',
    'exclude' => '',
    'meta_key' => '',
    'meta_value' =>'',
    'post_type' => 'post',
    'post_status' => 'publish, future, pending, private',
    'suppress_filters' => true
    );

    return wp_get_recent_posts( $args, ARRAY_A );
}

function n24tv_admin_get_category_name($id){
    if (is_numeric($id)){
        $category = get_category($id);
        return $category->name;
    }
    else {
        return $id;
    }
}

function n24tv_admin_get_featured_posts(){
    $F = N24TV_Featured::getInstance();
    return $F->get_data();
}

function n24tv_admin_featured_posts_display(){
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Featured posts</h1>
    <form name="n24tv-admin-featured" action="<?=$_SERVER['REQUEST_URI']?>" method="post" id="n24v-admin-featured">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content" style="position: relative;">

                    <div id="n24tv-admin-wrap">
                        <!-- list of categories currently active and we can drop recent posts -->
                        <div id="n24tv-admin-left" style="display: flex; flex-direction: column;">
                            <?php
                            foreach(n24tv_admin_get_featured_posts() as $f){
                                $cat_id = $f['category_id'];
                                $posts = $f['posts'];
                            ?>
                            <div class="postbox n24tv-featured-category <?=n24tv_admin_get_category_name($cat_id)?>">
                                <h2>
                                    <?=n24tv_admin_get_category_name($cat_id)?>
<!-- removed by Z to avoid accidental deletions
                                    <a onClick="n24tv_admin_delete_element(jQuery(this).parents('.n24tv-featured-category'));">
                                        <span class="dashicons dashicons-trash" style="float: right;"></span>
                                    </a>
-->
                                </h2>
                                <div class="inside">
                                    <ul class="n24tv-sortable n24tv-post-list" data-id="<?=$cat_id?>">
                                    <?php
                                    foreach($posts as $_post){
                                    ?>
                                        <li>
                                            <input type=hidden name="n24tv_featured_posts[<?=$cat_id?>][]" value="<?=$_post['id'];?>"> 
                                            <?=$_post['title'];?> (<?=$_post['status']?>)
                                            <a onClick="n24tv_admin_delete_element(jQuery(this).parent('li'))"><span style="float: right;" class="dashicons dashicons-no-alt n24tv-remove-icon"></span></a>
                                            </li>
                                    <?php
                                    }
                                    ?>
                                    </ul>
                                </div>
                            </div>
                            <?php
                            }
                            ?>
                        </div>
                        <!-- list of latest categories we can drag from -->
                        <div id="n24tv-admin-right" class="postbox">
                            <h2>Latest posts</h2>
                            <div class="inside">
                                <div>
                                <?php wp_dropdown_categories( 'show_count=0&hierarchical=1&show_option_none=Any&id=n24tv_admin_featured_category_list' ); ?>
                                </div>
                                <div>
                                <input type="text" placeholder="Search title ..." id="n24tv_search_title" style="width:100%">
                                </div>
                                <ul class="n24tv-post-list" id="n24tv-admin-latest-posts">
                                    <!-- loaded via ajax -->
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        <div id="submitdiv" class="postbox">
                            <button type="button" class="handlediv button-link" aria-expanded="true">
                                <span class="screen-reader-text">Preklopi ploščo: Objavi</span>
                                <span class="toggle-indicator" aria-hidden="true">

                                </span>
                            </button>
                            <h2 class="hndle ui-sortable-handle">
                                <span>Actions</span>
                            </h2>
                            <div class="inside">
                                <div class="submitbox" id="submitpost">
                                    <div>
                                        <div class="misc-pub-section">
                                            <?php wp_dropdown_categories( 'show_count=0&hierarchical=1&show_option_none=Custom&id=n24tv_admin_category_list' ); ?>
                                            <button onClick="n24tv_admin_featured_add_category(); return false;" class="button">Add featured category</button>
                                        </div>
                                    </div>
                                    <div id="major-publishing-actions">
                                        <div id="publishing-action">
                                            <span class="spinner"></span>
                                            <input type="submit" name="n24tv-admin-featured-publish" id="n24tv-admin-featured-save" class="button button-primary button-large" value="Save">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<style>
.home_news {
  order: 1;
}

#n24tv-admin-left .home h2 {
  background-color: #337ab7;
  color: white;
}

#n24tv-admin-left .home h2:before {
  content: "🏠";
}

#n24tv-admin-left .home_news h2 {
  background-color: #d11;
  color: white;
}
</style>
<?php
}
?>
