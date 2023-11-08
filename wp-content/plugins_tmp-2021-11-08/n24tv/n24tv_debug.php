<?php
if ( !defined('ABSPATH') ) {
    /** Set up WordPress environment */
    require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );
}

if (!current_user_can('publish_posts')){
    die("No permissions to access this page");
}

if (!class_exists('N24TV')){
    die("Plugin not activated!");
}

$N24TV = new N24TV;
$N24TV_Redis = N24TV_Redis::getInstance();
$N24TV_Admin = new N24TV_Admin( $N24TV->get_plugin_name(), $N24TV->get_version() );
$N24TV_Featured = N24TV_Featured::getInstance();

$action_message = (isset($_GET['action_message']) ? $_GET['action_message'] : null);
$action_status = (isset($_GET['action_status']) ? $_GET['action_status'] : NULL);
$action = (isseT($_GET['action']) ? $_GET['action'] : NULL);
switch($action){
    case 'full-refresh':
        $ret = $N24TV_Admin->refresh_event();
        $action_status = 'success';
        $action_message = 'Refresh event processed';
        break;
    case 'reset-categories':
        $ret = $N24TV_Admin->update_categories();
        if ($ret === false){
            $action_status = 'danger';
            $action_message = 'Failed to update categories!';
        }
        else {
            $action_status = 'success';
            $action_message = 'Categories updated';
        }        
        break;
    case 'reset-top-posts':
        $ret = $N24TV_Admin->update_top_posts();
        if ($ret === false){
            $action_status = 'danger';
            $action_message = 'Failed to update top posts!';
        }
        else {
            $action_status = 'success';
            $action_message = 'Top posts updated';
        }
        break;
    case 'reset-latest-posts':
        $ret = $N24TV_Admin->update_latest_posts();
        if ($ret === false){
            $action_status = 'danger';
            $action_message = 'Failed to update latest posts';
        }
        else {
            $action_status = 'success';
            $action_message = 'Latest posts updated';
        }
        break;
    case 'process-post-views':
        $ret = $N24TV_Admin->process_redis_posts_view_count();
        if ($ret === false){
            $action_status = 'danger';
            $action_message = 'Failed to process post views';
        }
        else {
            $action_status = 'success';
            $action_message = 'Post view count processed';
        }
        break;
    case 'reset-top-comments':
        $ret = $N24TV_Admin->update_top_comments_posts();
        if ($ret === false){
            $action_status = 'danger';
            $action_message = 'Failed to process top comments posts';
        }
        else {
            $action_status = 'success';
            $action_message = 'Top commented posts processed successfuly';
        }
        break;
    case 'reset-related':
        $ret = $N24TV_Admin->update_posts_taxonomy_data();
        if ($ret === false){
            $action_status = 'danger';
            $action_message = 'Failed to update related data';
        }
        else {
            $action_status = 'success';
            $action_message = 'Related data successfuly updated';
        }            
        break;
    case 'test-related':
        $post_id = (isset($_GET['related_post_id']) ? $_GET['related_post_id'] : null);
        try {
            if (empty($post_id)){
                throw new Exception('Provided post Id is not valid');
            }
            $start = microtime(true);
            $post = $N24TV_Redis->get_post($post_id);
            if (empty($post))
                throw new Exception('Post not found in Redis: ' . $post_id);


            $by_tags = N24TV::get_related_posts_by_tags($post['tags'], $post_id, 5);
            $by_author = N24TV::get_related_posts_by_author($post['author'], $post_id, $post['tags'], 5);
            $test_related_data = array('post' => $post, 'by_tags' => $by_tags, 'by_author' => $by_author, 'time' => microtime(true) - $start);

            $action_status = 'success';
            $action_message = 'Related data received';
            $action = NULL; // do not reload
        } catch (Exception $e){
            $action_status = 'danger';
            $action_message = $e->getMessage();
        }
        break;
    case 'add-featured':
        $ret = $N24TV_Featured->update($_GET['featured_cat_id'], explode(',', $_GET['featured_post_ids']));
        if ($ret === false){
            $action_status = 'danger';
            $action_message = 'Failed to write featured posts';
        }
        else {
            $action_status = 'success';
            $action_message = 'Featured posts updated';
        }
        break;
    case 'del-featured':
        $ret = $N24TV_Featured->delete($_GET['featured_cat_id']);
        if ($ret === false){
            $action_status = 'danger';
            $action_message = 'Failed to delete featured posts for category';
        }
        else {
            $action_status = 'success';
            $action_message = 'Featured posts for category deleted';
        }
        break;
    case 'reset-featured':
        $ret = $N24TV_Admin->update_featured_posts();
        if ($ret === false){
            $action_status = 'danger';
            $action_message = 'Failed to update featured posts';
        }
        else {
            $action_status = 'success';
            $action_message = 'Featured posts for updated';
        }
        break;
}
if ($action !== NULL){
    header("Location: " . $_SERVER['SCRIPT_NAME'] . '?action_message=' . urlencode($action_message) . '&action_status=' . urlencode($action_status));
    die();
}

$redis_keys = $N24TV_Redis->debug_keys();
$cron = _get_cron_array();
$cron_data = array();
$gmt_time = microtime(true);
foreach($cron as $timestamp => $cronhooks){
    $scheduled = ($timestamp > $gmt_time);
    foreach($cronhooks as $hook => $keys){
        foreach($keys as $k => $v){
            $schedule = $v['schedule'];
            $run = false;
            if ($schedule != false){
                $run = true;
            }
            $cron_data[] = 
                array(
                    'hook'      => $hook,
                    'scheduled' => $scheduled,
                    'run'       => $run,
                    'hook'      => $hook,
                    'schedule'  => $schedule, 
                    'interval'  => (isset($v['interval']) ? $v['interval'] : 'none'),
                    'args'      => $v['args'],
                    'timestamp' => $timestamp
                );
        }
    }
}

?>
<html>
    <head>
        <meta charset="utf-8">
        <title>N24TV Debug</title>
        <link rel='stylesheet' id='n24tv-css'  href='/wp-content/themes/n24tv/style.css?ver=4.7.2' type='text/css' media='all' />
        <link rel='stylesheet' id='boostrap-css'  href='/wp-content/themes/n24tv/css/bootstrap.min.css?ver=4.7.2' type='text/css' media='all' />
    </head>
    <body>
        <form method=get>
            <div class="container">
                <div class="page-header">
                  <h1>N24TV Debug <small>Operations</small> <button name="action" value="full-refresh" class="btn btn-danger">Reload all</button></h1>
                </div>
                <?php
                if ($action_message){
                    echo '<div class="alert alert-' . $action_status . '">' . $action_message . '</div>';
                }
                ?>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    Redis status
                                </h3>
                            </div>
                            <div class="panel-body">
                                <?php
                                if ($N24TV_Redis->isConnected()){
                                    echo '<div class="alert alert-success">Redis connection is available</div>';
                                    ?>
                                    <ul class="list-group">
                                    <?php
                                    foreach($redis_keys as $key => $kdata){
                                        ?>
                                        <li class="list-group-item">
                                            <h4 class="list-group-item-heading"><?=$key?>
                                            <span class="badge">Count: <?=$kdata['count']?></span>
                                            <?php if (isset($kdata['sum'])){  ?>
                                            <span class="badge">Sum: <?=$kdata['sum']?></span>
                                            <?php }?>
                                            </h4>
                                            <p class="list-group-item-text">
                                            <?=$kdata['descr']?>
                                            </p>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    </ul>
                                    <?php
                                }
                                else {
                                    echo '<div class"alert alert-danger">Redis connection is NOT available</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    Post view count
                                </h3>
                            </div>
                            <div class="panel-body">
                                <ul class="list-group">
                                <?php
                                foreach($redis_keys['n24tv_post_views']['posts'] as $post => $count){
                                    echo '<li class="list-group-item">' . $post . '<span class="badge">' . $count . '</span></li>';
                                }
                                ?>
                                </ul>
                            </div>
                            <div class="panel-footer">
                                <button class="btn btn-default" name="action" value="process-post-views">Process</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    Top posts
                                </h3>
                            </div>
                            <div class="panel-body">
                                <ul>
                                <?php
                                foreach($redis_keys['n24tv_top_posts']['posts'] as $post){
                                    echo '<li><a href="' . $post['permalink'] . '" target=_blank>' . $post['title'] . '</a></li>';
                                }
                                ?>
                                </ul>
                            </div>
                            <div class="panel-footer">
                                <button class="btn btn-default" name="action" value="reset-top-posts">Reload</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    Latest posts
                                </h3>
                            </div>
                            <div class="panel-body">
                                <ul>
                                <?php
                                if (isset($redis_keys['n24tv_latest_posts:0'])){
                                    foreach($redis_keys['n24tv_latest_posts:0']['posts'] as $post){
                                        echo '<li><a href="' . $post['permalink'] . '" target=_blank>' . $post['title'] . '</a></li>';
                                    }
                                }
                                ?>
                                </ul>
                            </div>
                            <div class="panel-footer">
                                <button class="btn btn-default" name="action" value="reset-latest-posts">Reload</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    Featured posts
                                </h3>
                            </div>
                            <div class="panel-body">
                                <table class="table table-striped table-bordered table-hover table-condensed">
                                    <thead>
                                        <th>CatId/Name</th>
                                        <th>Posts</th>
                                        <th>Action</th>
                                    </thead>
                                    <tbody>
                                    <?php

                                    foreach($N24TV_Featured->get_data() as $cfdata){
                                        $cat_id = $cfdata['category_id'];
                                        $post_ids = array();
                                        foreach($cfdata['posts'] as $post_id => $_post){
                                            $post_ids[] = $post_id;
                                        }
                                    ?>
                                    <tr>
                                        <th><input class="form-control" value="<?=$cat_id?>" readonly /></th>
                                        <td><input class="form-control" text=text name="featured_post_ids[<?=$cat_id?>]" value="<?=implode(',', $post_ids)?>"/></td>
                                        <td>
                                            
                                        </td>
                                    </tr>
                                    <?php
                                    }
                                    ?>
                                    <tr class="warning">
                                        <th><input class="form-control" type=text name="featured_cat_id" value=""/></th>
                                        <td><input class="form-control" type=text name="featured_post_ids" value=""/></td>
                                        <td>
                                            <button class="btn btn-default" name="action" value="add-featured">Add</button>
                                            <button class="btn btn-danger" name="action" value="del-featured">Del</button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="panel-footer">
                                <button class="btn btn-default" name="action" value="reset-featured">Reload</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    Top commented posts in 7 days
                                </h3>
                            </div>
                            <div class="panel-body">
                                <ul>
                                <?php
                                foreach($redis_keys['n24tv_top_comments_posts']['posts'] as $post){
                                    echo '<li><a href="' . $post['permalink'] . '" target=_blank>' . $post['title'] . '</a>';
                                    echo '<span class="badge">Total: ' . $post['comments'] . '</span>';
                                    echo '<span class="badge">Top: ' . $post['top_comment_count'] . '</span>';
                                    echo '</li>';
                                }
                                ?>
                                </ul>
                            </div>
                            <div class="panel-footer">
                                <button class="btn btn-default" name="action" value="reset-top-comments">Reload</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    Categories
                                </h3>
                            </div>
                            <div class="panel-body">
                                <ul>

                                </ul>
                            </div>
                            <div class="panel-footer">
                                <button class="btn btn-default" name="action" value="reset-categories">Reload</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    Test related post
                                </h3>
                            </div>
                            <div class="panel-body">
                                <div class="input-group">
                                    <input type=text class="form-control" name="related_post_id" placeholder="Post Id ...">
                                    <span class="input-group-btn">
                                        <button class="btn btn-primary" name="action" value="test-related">Test</button>
                                    </span>
                                </div>
                                <?php if (isset($test_related_data)){
                                    $post = $test_related_data['post'];
                                    $by_tags = $test_related_data['by_tags'];
                                    $by_author = $test_related_data['by_author'];
                                    $time = $test_related_data['time'];
                                ?>
                                <hr>
                                <div class="alert alert-success">It took <?=number_format($time, 6)?> to find related article(s)</div>
                                <table class="table table-striped table-bordered table-hover table-condensed">
                                    <thead>
                                        <th>Type</th>
                                        <th>Post</th>
                                        <th>Relevance</th>
                                        <th>Date</th>
                                        <th>Tags</th>
                                    </thead>
                                    <tbody>
                                        <tr class="disabled">
                                            <th>Search</th>
                                            <td>
                                                <a href="<?=$post['permalink']?>"><?=n24tv_ellipsis($post['title'], 30)?></a>
                                            </td>
                                            <td>100%</td>
                                            <td><?=$post['date'];?></td>
                                            <td>
                                                <a title="<?=implode(',', $post['tags'])?>"><?=n24tv_ellipsis(implode(', ', $post['tags']), 40)?></a>
                                            </td>
                                        </tr>
                                        <?php
                                        foreach($by_tags as $p){
                                        ?>
                                        <tr class="success">
                                            <th>by Tags</th>
                                            <td>
                                                <a href="<?=$p['permalink']?>"><?=n24tv_ellipsis($p['title'], 30)?></a>
                                            </td>
                                            <td><?=number_format($p['relevance']*100, 2)?>%</td>
                                            <td><?=$p['date'];?></td>
                                            <td>
                                                <a title="<?=implode(',', $p['tags'])?>"><?=n24tv_ellipsis(implode(', ', $p['tags']), 40)?></a>
                                            </td>
                                        </tr>
                                        <?php
                                        }

                                        foreach($by_author as $p){
                                        ?>
                                        <tr class="<?=(isset($p['relevance']) ? "success" : "warning")?>">
                                            <th>by Author</th>
                                            <td>
                                                <a href="<?=$p['permalink']?>"><?=n24tv_ellipsis($p['title'], 30)?></a>
                                            </td>
                                            <td><?=number_format($p['relevance']*100, 2)?>%</td>
                                            <td><?=$p['date'];?></td>
                                            <td>
                                                <a title="<?=implode(',', $p['tags'])?>"><?=n24tv_ellipsis(implode(', ', $p['tags']), 40)?></a>
                                            </td>
                                        </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <?php } ?>
                            </div>
                            <div class="panel-footer">
                                <button class="btn btn-default" name="action" value="reset-related">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    WP Crontabs
                                </h3>
                            </div>
                            <div class="panel-body">
                                <table class="table table-striped table-bordered table-hover table-condensed">
                                    <thead>
                                        <th>Hook</th>
                                        <th>Scheduled</th>
                                        <th>Run</th>
                                        <th>Schedule</th>
                                        <th>Interval</th>
                                        <th>Timestamp</th>
                                        <th>Args</th>
                                    </thead>
                                    <tbody>
                            <?php
                            foreach($cron_data as $c){
                                ?>
                                <tr class="<?=$c['scheduled'] && $c['run'] ? 'success' : ''?>">
                                    <th><?=$c['hook']?></th>
                                    <td><?=($c['scheduled'] === true ? 'YES' : 'NO')?></td>
                                    <td><?=($c['run'] === true ? 'YES' : 'NO')?></td>
                                    <td><?=$c['schedule']?></td>
                                    <td><?=$c['interval']?></td>
                                    <td><?=date('Y-m-d H:i:s', $c['timestamp'])?></td>
                                    <td><?=@implode(', ', $c['args'])?></td>
                                </tr>
                                <?php
                            }
                            ?>
                                </tbody>
                                </table>
                            </div>
                            <div class="panel-footer">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </body>
</html>