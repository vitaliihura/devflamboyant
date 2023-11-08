<!DOCTYPE html>
<html <?php language_attributes(); ?>>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="fb:pages" content="1543883469231132" />
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?php wp_title('|', true, 'right'); ?></title>
    <!-- STARA GA KODA - Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-68823868-1"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'UA-68823868-1');
    </script>
	
	<!-- NOVA GA KODA - Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-7VSZJE3XYH"></script>
	<script>
  	window.dataLayer = window.dataLayer || [];
  	function gtag(){dataLayer.push(arguments);}
  	gtag('js', new Date());

  	gtag('config', 'G-7VSZJE3XYH');
	</script>

    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6488729491925189" crossorigin="anonymous"></script>
    <script charset="UTF-8" src="https://s-eu-1.pushpushgo.com/js/633d75f2ec2df31151f421c4.js" async="async"></script>


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- Comments stuff -->
    <?php if ( is_singular() && get_option( 'thread_comments' ) ) wp_enqueue_script( 'comment-reply' ); ?>
    <!-- END COMMENTS STUFF -->

    <!-- START WP_HEAD(); -->
    <?php wp_head(); ?>
    <!-- END WP_HEAD(); -->

    <!-- Analytics and ads -->
    <script type="text/javascript">
        function n24tv_get_cookie(name){
            var re = new RegExp(name + "=([^;]+)");
            var value = re.exec(document.cookie);
            return (value != null) ? unescape(value[1]) : null;
        }

        /**
         * Check if user was notified about cookie policy
         */
        function n24tv_did_view_cookie_policy(){
            return (n24tv_get_cookie('viewed_cookie_policy') == 'yes');
        }

        if (n24tv_did_view_cookie_policy()){
            document.write('\x3Cscript type="text/javascript" src="//si.adocean.pl/files/js/ado.js">\x3C/script>');
            window['ga-disable-G-7VSZJE3XYH'] = false;
        }
        else {
            window['ga-disable-G-7VSZJE3XYH'] = false;
        }
    </script>

    <script type="text/javascript">
        if(typeof ado!=="object"){ado={};ado.config=ado.preview=ado.placement=ado.master=ado.slave=function(){};}
        ado.config({mode: "old", xml: false, characterEncoding: true});
        ado.preview({enabled: true, emiter: "si.adocean.pl", id: "Mr1Rsue7H.rad6w_4ZHJvEJGwBl.iSL6B.8jEytU5tj.c7"});
    </script>

    <!-- Start Alexa Certify Javascript -->
    <script type="text/javascript">
    _atrk_opts = { atrk_acct:"xJuip1IW1d10cv", domain:"nova24tv.si",dynamic: true};
    (function() { var as = document.createElement('script'); as.type = 'text/javascript'; as.async = true; as.src = "https://certify-js.alexametrics.com/atrk.js"; var s = document.getElementsByTagName('script')[0];s.parentNode.insertBefore(as, s); })();
    </script>
    <noscript><img src="https://certify.alexametrics.com/atrk.gif?account=xJuip1IW1d10cv" style="display:none" height="1" width="1" alt="" /></noscript>
    <!-- End Alexa Certify Javascript -->  
  </head>
  <body <?php /*body_class();*/ ?>>
    <!-- The TOP header -->
    <nav id="n24tv-top-header" class="navbar navbar-inverse navbar-static-top hidden-xs">
        <div class="container">
            <!-- screen size test
            <div class="navbar-left">
                <p class="navbar-text">
                    <a class="visible-lg" href=#>LG</a>
                    <a class="visible-md" href=#>MD</a>
                    <a class="visible-sm" href=#>SM</a>
                    <a class="visible-xs" href=#>XS</a>
                </p>
            </div>
            -->
            <div class="navbar-right">
                <p class="navbar-text">
                    <span id="n24tv_clock"></span>
                    <!-- SOCIAL LINKS -->
                    <a target="_blank" href="https://facebook.com/Nova24tv" title="Facebook">
                        <i class="fa fa-facebook fa-fw"></i>
                    </a>
                    <a target="_blank" href="https://nova24tv.si/feed/" title="RSS">
                        <i class="fa fa-rss fa-fw"></i>
                    </a>
                    <a target="_blank" href="https://twitter.com/Nova24TV" title="Twitter">
                        <i class="fa fa-twitter fa-fw"></i>
                    </a>
                    <a target="_blank" href="https://www.youtube.com/channel/UCH6QLhXjS6TjhYGVIaMKG8g" title="Youtube">
                        <i class="fa fa-youtube-play fa-fw"></i>
                    </a>
                    <!-- END SOCIAL LINKS -->
                </p>
            </div>
        </div>
    </nav>
    <!-- END TOP HEADER -->

    <!-- HEADER HEADER -->
    <div id="n24tv-header" class="container-fluid">
        <div class="row container center-block">
            <div class="hidden-sm hidden-xs col-md-3 col-lg-4">
                <a href="https://nova24tv.si">
                    <img alt="nova24tv.si" class="img-responsive center-block" src="https://nova24tv.si/wp-content/uploads/2015/07/logo-lezeci-300x67.png" style="padding-top: 3%;"/>
                </a>
            </div>
            <div class="col-sm-12 col-md-9 col-lg-8">
                <div class="center-block n24tv-ad hidden-xs">
                    <!-- note: this will also load the ado.master -->
                    <?php require(__DIR__ . '/include/ads/header.php'); ?>
                </div>
            </div>
        </div>
        <div class="row visible-sm">
            <div class="col-sm-12">
                <a href="http://nova24tv.si">
                    <img alt="nova24tv.si" class="center-block" height=40 src="https://nova24tv.si/wp-content/uploads/2015/07/logo-lezeci-300x67.png" />
                </a>
            </div>
        </div>
    </div>
    <!-- END NOT SO TOP HEADER -->

    <!-- NAVIGATION -->
    <nav id="n24tv-navbar" class="navbar navbar-inverse navbar-static-top" role="navigation">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-n24tv-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand visible-xs" href="https://nova24tv.si" style="padding: 5px;">
                <img alt="nova24tv.si" height=40 style="float: left" src="https://nova24tv.si/wp-content/uploads/2015/07/logo-lezeci-300x67.png" />
            </a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse navbar-n24tv-collapse">
            <?php
            wp_nav_menu( array(
                'theme_location' => 'top_menu',
                'menu'  => 'td-demo-header-menu',
                'depth' => 2,
                'container' => false,
                'menu_class' => 'nav navbar-nav',
                'fallback_cb' => 'wp_page_menu',
                //Process nav menu using our custom nav walker
                'walker' => new wp_bootstrap_navwalker())
            );
            ?>
        </div><!-- /.navbar-collapse -->
    </nav>
    <!-- END NAVIGATION -->

            <div class="container">
                <div class="n24tv-ad center-block" style="padding-bottom: 10px;">
                    <?php require(__DIR__ . '/include/ads/upper.php'); ?>
                </div>
            </div>
            <?php
            if (is_category() || is_tag()){
            ?>
            <div class="container">
                <h1 itemprop="name" class="n24tv-title"><?=wp_title('')?></h1>
            </div>
            <?php
            }
            ?>

    <!-- MASONRY -->
    <?php
        /**
         * List of categories where we skip masonry
         */
        $arrSkipMasonryCatId =
            array(
                11239,   // za delnicarje
            );
        $category_id = null;
        if (is_front_page() || is_category()){
            if (is_category()){
                $category = get_category( get_query_var( 'cat' ) );
                $category_id = $category->cat_ID;
                $category_name = $category->name;
            }
            else {
                $category_id = 'home';
            }
            if (!in_array($category_id, $arrSkipMasonryCatId)){
                echo n24tv_render_masonry($category_id);
            }
        }
    ?>
    <!-- END MASONRY -->


  <!-- END HEADER.PHP -->
