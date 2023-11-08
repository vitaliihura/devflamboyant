<!-- SIDEBAR START -->
    <div class="row n24tv-sidebar-item center-block n24tv-ad z1">
        <div class="col-xs-12">
            <?php require(__DIR__ . '/include/ads/sidebar_top.php'); ?>
        </div>
    </div>


<div id="n24tv-sidebar-affix">
    <?php
    if (is_front_page()){
        $arrConfig = array(
            array(
                'title' =>  'Novosti',
                'category_ids'  => array(8158),
                'count'         => 2
            ),
        );
        foreach($arrConfig as $c){
            $posts = n24tv_get_front_posts($c);
        ?>
    <div class="row n24tv-sidebar-item center-block">
        <div class="col-xs-12 n24tv-sidebar-panel">
            <div class="panel panel-n24tv">
                <div class="panel-heading">
                    <a href="/rubrika/oglasevalske-vsebine/"><h3 class="panel-title"><span><?=$c['title']?></span></h3></a>
                </div>
                <div class="panel-body">
                <?php
                        $count = 0;
                        foreach($posts as $_post){
                            ++$count;
                            echo n24tv_render_left_medium_article_redis($_post, array(), array('thumbnail_size' => 'n', 'content' => false));
                        ?>
                   
                        <?php
                            if ($count >= 3)
                                break;
                        }
                        ?>
                </div>
            </div>
        </div>
    </div>
        <?php
        } // foreach posts
        ?>
    <!-- LIFE FEED LINK -->
    <div class="row n24tv-sidebar-item center-block">
        <div class="col-xs-12 n24tv-sidebar-panel">
            <div class="panel panel-n24tv">
                <div class="panel-heading">
                    <h3 class="panel-title"><span>Nova24TV v živo</span></h3>
                </div>
                <div class="panel-body">
                    <iframe z="1" width="360" height="203" src="https://www.youtube.com/embed/HrGTpPF_r_c" frameborder="0" gesture="media" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="row n24tv-sidebar-item center-block">
        <div class="col-xs-12 n24tv-sidebar-panel">
            <div class="panel panel-n24tv">
                <div class="panel-heading">
                    <h3 class="panel-title"><span>Nova24TV 2 v živo</span></h3>
                </div>
                <div class="panel-body">
                    <iframe z="3" width="360" height="203" src="https://www.youtube.com/embed/9l73nXaVzxs" frameborder="0" gesture="media" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
    <!-- END LIFE FEED LINK -->

        <?php if ( is_active_sidebar( 'n24tv-sidebar' ) ) : ?>

                <?php dynamic_sidebar( 'n24tv-sidebar' ); ?>


        <?php endif; ?>



    <?php
    } // is_front_page()
    else {
    ?>

    <div class="row n24tv-sidebar-item center-block">
        <div class="col-xs-12 n24tv-sidebar-panel">
            <div class="panel panel-n24tv">
                <div class="panel-heading">
                    <h3 class="panel-title"><span>Oglasi</span></h3>
                </div>
                <div class="panel-body">
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6488729491925189" crossorigin="anonymous"></script>
<!-- Sidebar v članku -->
<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-6488729491925189" data-ad-slot="7982471840" data-ad-format="auto" data-full-width-responsive="true"></ins>
<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>

                </div>
            </div>
        </div>
    </div>


    <div class="row n24tv-sidebar-item center-block">
        <div class="col-xs-12 n24tv-sidebar-panel">
            <div class="panel panel-n24tv">
                <div class="panel-heading">
                    <h3 class="panel-title"><span>Najbolj brano</span></h3>
                </div>
                <div class="panel-body">
                    <?php
                    echo n24tv_render_sidebar_articles(
                        n24tv_get_top_posts(),
                        array('title' => 65)
                    );
                    ?>
                </div>
            </div>
        </div>
    </div>



    <div class="row n24tv-sidebar-item center-block">
        <div class="col-xs-12 n24tv-sidebar-panel">
            <div class="panel panel-n24tv">
                <div class="panel-heading">
                    <h3 class="panel-title"><span>Sveže novice</span></h3>
                </div>
                <div class="panel-body">
                    <?=
                        n24tv_render_sidebar_articles(
                            n24tv_get_latest_posts(), 
                            array('title' => 60)
                        );
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- LIFE FEED LINK -->
    <div class="row n24tv-sidebar-item center-block">
        <div class="col-xs-12 n24tv-sidebar-panel">
            <div class="panel panel-n24tv">
                <div class="panel-heading">
                    <h3 class="panel-title"><span>V živo</span></h3>
                </div>
                <div class="panel-body">
                    <iframe z="2" width="360" height="203" src="https://www.youtube.com/embed/live_stream?channel=UCjYCgkpX1eQCTne99oT63yA" frameborder="0" gesture="media" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
    <!-- END LIFE FEED LINK -->

    <div class="row n24tv-sidebar-item center-block">
        <div class="col-xs-12 n24tv-sidebar-panel n24tv-ad z2">
            <?php require(__DIR__ . '/include/ads/sidebar_bottom.php'); ?>
        </div>
    </div>
    <?php } // !is_front_page(); ?>
</div>
<!-- SIDEBAR END -->
