<?php
require(__DIR__ . '/schedule.inc.php');

$operation = 'add';

$date = ($_GET['date'] ?? date('Y-m-d'));
if (isset($_POST['date'])){
    $date = $_POST['date'];
}
$Day = N24TV_Schedule_Day::getInstance(new DateTime($date));
$Entry = null;
$id = null;
$start = null;
if (isset($_GET['start'])){
    $start = $_GET['start'];
}
if ($start === null){
    $start = ($_POST['start'] ?? $Day->getNextStart()->format('H:i:s'));
    $Entry = new N24TV_Schedule_Entry($Day);
}
else {
    $Entry = N24TV_Schedule_Entry::get($Day, $start);
    if ($Entry !== null){
        $id = $Entry->getStart()->format('Y-m-d H:i:s');
        $operation = 'edit';
    }
    else {
        die("No such entry!");
    }
}

//$start = ($_POST['start'] ?? $Day->getNextStart()->format('H:i'));


$end = ($_POST['end'] ?? ($Entry->getEnd() === NULL ? $Day->getNextEnd()->format('H:i:s') : $Entry->getEnd()->format('H:i:s')));


$title = ($_POST['title'] ?? $Entry->getTitle());
$subtitle = ($_POST['subtitle'] ?? $Entry->getSubtitle());
$category = ($_POST['category'] ?? $Entry->getCategory());
$genre = ($_POST['genre'] ?? $Entry->getGenre());
$url_1 = ($_POST['url_1'] ?? $Entry->getURL1());
$url_2 = ($_POST['url_2'] ?? $Entry->getURL2());
$short_description = ($_POST['short_description'] ?? $Entry->getShortDescription()); 
$description = ($_POST['description'] ?? $Entry->getDescription());
$picture = ($_POST['picture'] ?? $Entry->getPicture());
$premiere = ($_POST['premiere'] ?? $Entry->getPremiere());
$live = ($_POST['live'] ?? $Entry->getLive());
$previously_shown = ($_POST['previously_shown'] ?? $Entry->getPreviouslyShown());
if ($premiere == 'Y')
    $premiere = true;
if ($live == 'Y')
    $live = true;
if ($previously_shown == 'Y')
    $previously_shown = true;
?>
<html>
    <head>
        <meta charset="utf-8">
        <title>Spored 2: <?=schedule_translate(ucfirst($operation))?></title>
        <link rel='stylesheet' id='n24tv-css'  href='/wp-content/themes/n24tv/style.css?ver=4.7.2' type='text/css' media='all' />
        <link rel='stylesheet' id='boostrap-css'  href='/wp-content/themes/n24tv/css/bootstrap.min.css?ver=4.7.2' type='text/css' media='all' />
	<script type='text/javascript' src='/wp-includes/js/jquery/jquery.js'></script>
        <script src="/wp-content/themes/n24tv/js/bootstrap.min.js?t=<?=time()?>"></script>
        <script src="schedule.js?t=<?=time();?>"></script>

        <script type='text/javascript'>var pollsL10n={"ajax_url":"https:\/\/nova24tv.si\/wp-admin\/admin-ajax.php","text_wait":"Your last request is still being processed. Please wait a while ...","text_valid":"Please choose a valid poll answer.","text_multiple":"Maximum number of choices allowed: ","show_loading":"0","show_fading":"1"};</script>  <script type='text/javascript'>var countVars={"disqusShortname":"nova24tv"};</script>
        <script type='text/javascript'>var n24tv_ajax={"url":"https:\/\/nova24tv.si\/wp-admin\/admin-ajax.php","id":"0","nonce":""};</script>

    </head>
    <body>
        <form method=post>
            <?php
            if ($id !== null){
            ?>
            <input type="hidden" name="id" value="<?=$id?>">
            <?php
            }
            ?>
            <div class="container">
                <div class="page-header">
                    <h1>Spored 2 - <?=schedule_translate($operation)?> (<?=$date?>)</h1>
                </div>
                <?php
                if (isset($GLOBALS['post-error'])){
                    ?>
                    <p class="bg-danger">
                    <?= $GLOBALS['post-error'] ?>
                    </p>
                    <?php
                    $GLOBALS['post-error'] = NULL;
                }
                ?>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date">Datum</label>
                            <input type="date" class="form-control" id="date" name="date" placeholder="Date" value="<?=$date?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="start">Začetek</label>
                            <input type="time" step="1" class="form-control" name="start" id="start" placeholder="Start time" value="<?=$start?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="end">Konec</label>
                            <input type="time" step="1" class="form-control" name="end" id="end" placeholder="End time" value="<?=$end?>" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="title">Naslov</label>
                            <input type="text" class="form-control" name="title" id="title" placeholder="Title" value="<?=$title?>" maxlength=200 required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subtitle">Podnaslov</label>
                            <input type="text" class="form-control" name="subtitle" id="subtitle" placeholder="Subtitle" value="<?=$subtitle?>" maxlength=200>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="category">Kategorija</label>
                            <select id="category" name="category" class="form-control" required>
                                <option value=0>Izberi kategorijo</option>
                            <?php
                            foreach($arrCategories as $_category => $genres){
                                echo '<option value="' . $_category . '"' . ($category == $_category ? ' selected': '') . '>' . $_category . '</option>';
                            }
                            ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="genre">Žanr</label>
                            <select id="genre" name="genre" class="form-control">

                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="url_1">URL 1</label>
                            <input type="url" class="form-control" name="url_1" id="url_1" placeholder="http://..." value="<?=$url_1?>" maxlength=200>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="url_2">URL 2</label>
                            <input type="url" class="form-control" name="url_2" id="url_2" placeholder="http://..." value="<?=$url_2?>" maxlength=200>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="premiere" value="Y"<?=($premiere === true ? ' checked' : '')?>>
                                    Premiera
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="live" value="Y"<?=($live === true ? ' checked' : '')?>>
                                    V živo
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="previously_shown" value="Y"<?=($previously_shown === true ? ' checked' : '')?>>
                                    Ponovitev
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="short_description">Kratek opis</label>
                            <textarea class="form-control" name="short_description" id="short_description" placeholder="Short description ..." rows="3" maxlength=150 required><?=$short_description?></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="description">Opis</label>
                            <textarea class="form-control" name="description" id="description" placeholder="Description ..." rows="3" maxlength=1024 required><?=$description?></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label for="picture">Slika</label>
                        <div class="input-group">
                            <input class="form-control" type="text" id="picture" name="picture" value="<?=$picture?>" placeholder="http://...">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-default" data-toggle="modal" data-target="#myPictureSelect">Izberi sliko</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <br/>
                    <div class="col-md-12">
                        <div class="form-group">
                            <button type="submit" name="add-schedule" class="btn btn-primary">Shrani</button>
                            <a class="btn btn-warning" href="index.php?date=<?=$Day->getDate();?>">Razveljavi</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <!-- start modal -->
        <div class="modal fade" id="myPictureSelect" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Zapri"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Izberi sliko</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <form id="wp_media_search">
                                    <div class="input-group">
                                        <input type="text" id="wp_media_search_text" class="form-control" placeholder="Išči slike ...">
                                        <span class="input-group-btn">
                                            <button type="submit" class="btn btn-success" type="button">Išči</button>
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="row">
                            <div id="wp_media_search_results" class="col-md-12">

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- end modal -->
        <script type="text/javascript">
            jQuery(function () {
                schedule_update_genre(jQuery('#category').val(), '<?=$genre?>');
            });
        </script>
    </body>
</html>
