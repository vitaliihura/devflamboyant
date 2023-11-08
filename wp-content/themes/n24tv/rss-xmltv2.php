<?php
define('N24TV_SCHEDULE_DISPLAY_XML', true); // to skip auth check
require(__DIR__ . '/../../plugins/n24tv/schedule2/schedule.inc.php');

header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
?>
<!DOCTYPE tv SYSTEM "http://xmltv.cvs.sourceforge.net/viewvc/xmltv/xmltv/xmltv.dtd">
<tv source-info-url="https://nova24tv.si/" source-info-name="Nova 24TV 2" generator-info-name="XMLTV/$Id: nova24tv.si@<?=date('c')?> $">

    <channel id="nova24tv2">
        <display-name>Nova 24TV 2</display-name>
    </channel>

    <?php
    $days = N24TV_Schedule_Day::getLatest();
    foreach($days as $Day){
        foreach($Day->getEntries() as $Entry){
            echo $Entry->getXML('nova24tv2');
        }
    }
    ?>
</tv>
