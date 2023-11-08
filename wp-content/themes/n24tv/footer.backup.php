<!-- START FOOTER.PHP -->
        <div id="n24tv-footer" >
            <div class="container">
                <div class="row">
                    <div class="col-sm-4 n24tv-footer-articles">
                        <h3 class="n24tv-footer-title">Najbolj brano</h3>
                        <?php
                        $posts = n24tv_get_top_posts(3);
                        foreach($posts as $_post){
                        ?>
                        <div class="row">
                            <div clas="col-xs-12">
                                <?= n24tv_render_left_small_article_redis($_post); ?>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                    </div>

                    <div class="col-sm-4 n24tv-footer-articles">
                        <h3 class="n24tv-footer-title">Najbolj komentirano</h3>
                        <?php
                        $posts = n24tv_get_top_comments_posts(3);
                        foreach($posts as $_post){
                        ?>
                        <div class="row">
                            <div class="col-xs-12">
                                <?= n24tv_render_left_small_article_redis($_post); ?>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                    </div>

                    <div class="col-sm-4 n24tv-company-info">
                        <p><img src="//nova24tv.si/wp-content/uploads/2015/10/nova-tv-logo-127x80.png"></p>

                        <p>
                            Nova hiša d.o.o., Linhartova 13, 1000 Ljubljana<br/>
                            <br/>
                            Direktor: Aleš Hojs<br/>
                            Odgovorni urednik: Boris Tomašič<br/>
                            <br/>
                            Kontakt:<br/>
                            TAJNIŠTVO: 01 2355293<br/>
                            MARKETING: <a href="mailto:marketing@nova24tv.si">marketing@nova24tv.si</a><br/>
                            KONTAKT: <a href="mailto:info@nova24tv.si">info@nova24tv.si</a><br/>
                            <br/>
                            <a href="http://nova24tv.si/za-delnicarje/149694/">Za delničarje</a><br/>
                            <a href="http://nova24tv.si/obvestilo-o-piskotkih/">Obvestilo o piškotkih</a><br/>
                            <a href="http://nova24tv.si/splosni-pogoji/">Splošni pogoji</a>
                        </p>
                        <div class="center-block" style="width: 80%; padding: 20px;">
                            <div class="btn-group btn-group-lg btn-group-justified center-block" role="group">
                                <a class="btn btn-n24tv" href="http://facebook.com/Nova24tv" target=_blank>
                                    <i class="fa fa-facebook-official"></i>
                                </a>
                                <a class="btn btn-n24tv" href="http://nova24tv.si/feed/" target=_blank>
                                    <i class="fa fa-rss"></i>
                                </a>
                                <a class="btn btn-n24tv" href="https://twitter.com/Nova24TV" target=_blank>
                                    <i class="fa fa-twitter"></i>
                                </a>
                                <a class="btn btn-n24tv" href="https://www.youtube.com/channel/UCH6QLhXjS6TjhYGVIaMKG8g" target=_blank>
                                    <i class="fa fa-youtube-play"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php wp_footer(); ?>

    <?= n24tv_adocean_display('floater'); ?>
    <?= n24tv_adocean_display('background'); ?>

    <?= n24tv_render_submenus(); ?>

    <?php if (is_front_page()){ ?>
        <!-- (C)2000-2016 Gemius SA - gemiusAudience / nova24tv.si / Home Page -->
        <script type="text/javascript">
            if (n24tv_did_view_cookie_policy()) {
                <!--//--><![CDATA[//><!--
                var pp_gemius_identifier = 'csrrq4sNS.fMix450NsT03YX7DPBKISbJpuVdDgFGvL.a7';
                // lines below shouldn't be edited
                function gemius_pending(i) { window[i] = window[i] || function() {var x = window[i+'_pdata'] = window[i+'_pdata'] || []; x[x.length]=arguments;};};
                gemius_pending('gemius_hit'); gemius_pending('gemius_event'); gemius_pending('pp_gemius_hit'); gemius_pending('pp_gemius_event');
                (function(d,t) {try {var gt=d.createElement(t),s=d.getElementsByTagName(t)[0],l='http'+((location.protocol=='https:')?'s':''); gt.setAttribute('async','async');
                gt.setAttribute('defer','defer'); gt.src=l+'://gasi.hit.gemius.pl/xgemius.js'; s.parentNode.insertBefore(gt,s);} catch (e) {}})(document,'script');
                //--><!]]>
            }
        </script>
    <?php } else { ?>
         <!-- (C)2000-2016 Gemius SA - gemiusAudience / nova24tv.si / Pages -->
        <script type="text/javascript">
            if (n24tv_did_view_cookie_policy()) {
                <!--//--><![CDATA[//><!--
                var pp_gemius_identifier = 'zDFLybSrW.jB.l4Bqnrb15Xm.s6RIW9kZjI3bmgeWED.a7';
                // lines below shouldn't be edited
                function gemius_pending(i) { window[i] = window[i] || function() {var x = window[i+'_pdata'] = window[i+'_pdata'] || []; x[x.length]=arguments;};};
                gemius_pending('gemius_hit'); gemius_pending('gemius_event'); gemius_pending('pp_gemius_hit'); gemius_pending('pp_gemius_event');
                (function(d,t) {try {var gt=d.createElement(t),s=d.getElementsByTagName(t)[0],l='http'+((location.protocol=='https:')?'s':''); gt.setAttribute('async','async');
                gt.setAttribute('defer','defer'); gt.src=l+'://gasi.hit.gemius.pl/xgemius.js'; s.parentNode.insertBefore(gt,s);} catch (e) {}})(document,'script');
                //--><!]]>
            }
        </script>
    <?php } ?>

        <script type="text/javascript">
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
          (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
          m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

          ga('create', 'UA-68823868-1', 'auto');
          ga('send', 'pageview');
        </script>

        <script src="//si.contentexchange.me/static/tracker.js"></script>
	<!-- disabled: 2018-06-20
        <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
        <script>
             (adsbygoogle = window.adsbygoogle || []).push({
                  google_ad_client: "ca-pub-7513212074738858",
                  enable_page_level_ads: true
             });
        </script>
	-->
    </body>
</html>
