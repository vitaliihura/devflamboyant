<?php 
// load N24TV Schedule
define('N24TV_SCHEDULE_DISPLAY_XML', true); // to skip auth check
require(__DIR__ . '/../../plugins/n24tv/schedule/schedule.inc.php');

function n24tv_schedule_get_entry_img(N24TV_Schedule_Entry $E){
    $id = $E->getPicture();
    $ret = 'https://dummyimage.com/150x150&text=Ni+na+voljo';
    if (is_numeric($id)){
        $tmp = wp_get_attachment_image_src($id, 'thumbnail');
        if ($tmp === false || !is_array($tmp) || count($tmp) != 4){
            return $ret;
        }
        list($url, $width, $height, $is_intermediate) = $tmp;
        $ret = $url;
    }
    return '<img class="media-object img-rounded" src="' . $ret . '" height="90">';
}

$dtToday = new DateTime(date('Y-m-d') . ' 00:00:00');

try {
    $dtCurrent = new DateTime(($_GET['date'] ?? date('Y-m-d')));
} catch (Exception $e){
    $dtCurrent = new DateTime;
}

$dtCurrent_str = schedule_translate_days(
                    ($dtCurrent == $dtToday ? 'danes, ' . $dtCurrent->format('d.m.Y') : $dtCurrent->format('l, d.m.Y'))
                );

// all possible datetimes
$arrDateTimes = [];
$S = clone $dtToday;
$S->sub(new DateInterval('P2D'));
$E = clone $dtToday;
$E->add(new DateInterval('P7D'));
for($DT = clone $S; $DT < $E; $DT->add(new DateInterval('P1D'))){
    $arrDateTimes[] = clone $DT;
}

$Day = N24TV_Schedule_Day::getInstance($dtCurrent);
$entries = $Day->getEntries();

// load header.php 
get_header();
?>

<div id="n24tv-main" class="container">
    <div class="row">
        <div class="col-lg-8 col-md-9" role="main">

            <?php
                /* Start the Loop */
                while ( have_posts() ) : the_post();
            ?>
            <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="post-header">

                    <?php the_title('<h1>', '</h1>'); ?>

                </div>
                <div class="post-content">
                    <?php 
                    /**
                     * Check the post thumbnail
                     */
                    if (has_post_thumbnail()){
                        ?>
                        <div class="post-thumbnail">
                            <?php the_post_thumbnail(null, array('class' => 'img-responsive')); ?>
                        </div>
                        <?php
                    }
                    ?>
                    <?php the_content(); // output content, if any at all ?>
                    <br/>
                    <div class="row">
                        <div class="col-md-12 n24tv-schedule">
                            <div class="panel panel-n24tv">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <span>TV Spored - <?=$dtCurrent_str?></span>
                                        <div class="dropdown pull-right">
                                            <a id="n24tv-schedule-label" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Izberi
                                                <i class="fa fa-caret-down"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="n24tv-schedule-label">
                                            <?php
                                            foreach($arrDateTimes as $DT){
                                                $dt_str = schedule_translate_days($DT->format('l, d.m.Y'));
                                                $dt_link = '<a href="?date=' . $DT->format('Y-m-d') . '">';
                                                if ($DT == $dtCurrent){
                                                    $dt_link .= '<b>' . $dt_str . '</b>';
                                                }
                                                elseif($DT < $dtToday){
                                                    $dt_link .= '<i class="text-muted">' . $dt_str . '</i>';
                                                }
                                                else {
                                                    $dt_link .= $dt_str;
                                                }
                                                $dt_link .= '</a>';
                                            ?>
                                                <li>
                                                <?=$dt_link?>
                                                </li>
                                            <?php
                                            }
                                            ?>
                                            </ul>
                                        </div>
                                    </h3>
                                </div>
                                <div class="panel-body">

                                    <div class="panel-group" id="n24tv-schedule-list" role="tablist" aria-multiselectable="true">

                                        <?php
                                        foreach($entries as $E){
                                            $id = $E->getStart()->format('His');
                                            $heading_id = 'n24tv-schedule-entry-heading-' . $id;
                                            $content_id = 'n24tv-schedule-entry-' . $id;
                                        ?>
                                        <div class="panel panel-n24tv-schedule-entry">
                                            <div class="panel-heading" role="tab" id="<?=$heading_id?>">
                                                <h4 class="panel-title">
                                                    <a role="button" data-toggle="collapse" data-parent="#n24tv-schedule-list" href="#<?=$content_id?>" aria-expanded="true" aria-controls="<?=$content_id?>">
                                                        <span><?=$E->getStart()->format('H:i')?>: </span>
                                                        <span><?=$E->getTitle()?></span>
                                                        <?php if (!empty($E->getSubtitle())){ ?>
                                                        - <small><?=$E->getSubtitle()?></small>
                                                        <?php } ?>
                                                        <?=n24tv_schedule_get_status_icons($E)?>
                                                    </a>
                                                </h4>
                                            </div>
                                            <div id="<?=$content_id?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="<?=$heading_id?>">
                                                <div class="panel-body">
                                                    <div class="media">
                                                        <div class="media-top media-left">
                                                            <a><?=n24tv_schedule_get_entry_img($E)?></a>
                                                        </div>
                                                        <div class="media-body">
                                                            <h4 class="media-heading"><?=$E->getCategory() . ' - ' . $E->getGenre() ?></h4>
                                                            <?=nl2br(htmlentities($E->getDescription()))?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                         </div>                                        
                                        <?php
                                        } // foreach(entries ...)
                                        ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end div.post -->
            <?php
                /* The the Loop */
                endwhile;
            ?>
        </div>
        <div id="n24tv-sidebar" class="col-lg-4 col-md-3">
            <?php get_sidebar(); ?>
        </div>
    </div>
</div>

<?php
// load footer.php 
get_footer();
?>