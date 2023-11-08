<?php
require(__DIR__ . '/schedule.inc.php');

$date = ($_GET['date'] ?? NULL);
$start = ($_GET['start'] ?? NULL);

if (empty($date) || empty($start)){
    die('Incorrect parameters');
}

$Day = N24TV_Schedule_Day::getInstance(new DateTime($date));
$Entry = N24TV_Schedule_Entry::get($Day, $start);
if (empty($Entry)){
    die("No such entry");
}

?>
<html>
    <head>
        <meta charset="utf-8">
        <title>Vnos: <?=$Entry->getTitle();?></title>
        <link rel='stylesheet' id='n24tv-css'  href='/wp-content/themes/n24tv/style.css?ver=4.7.2' type='text/css' media='all' />
        <link rel='stylesheet' id='boostrap-css'  href='/wp-content/themes/n24tv/css/bootstrap.min.css?ver=4.7.2' type='text/css' media='all' />
    </head>
    <body>
        <div class="container">
            <div class="page-header">
                <h1>
                    <?=n24tv_ellipsis($Entry->getTitle(), 50);?> 
                    <small><?=n24tv_ellipsis($Entry->getSubtitle())?></small>
                    <a class="btn btn-default btn-xs" href="add.php?date=<?=$date?>&start=<?=$start?>">Uredi</a>
                    <?=n24tv_schedule_get_status_icons($Entry)?>
                </h1>
            </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date">Datum</label>
                            <p id="date" class="form-control-static"><?=$Entry->getDay()->getDate();?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start">Začetek</label>
                            <p id="start" class="form-control-static"><?=$Entry->getStart()->format('Y-m-d H:i:s')?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end">Konec</label>
                            <p id="end" class="form-control-static"><?=$Entry->getEnd()->format('Y-m-d H:i:s')?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="length">Dolžina</label>
                            <p id="length" class="form-control-static"><?=$Entry->getLength();?></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="title">Naslov</label>
                            <p id="title" class="form-control-static"><?=$Entry->getTitle();?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subtitle">Podnaslov</label>
                            <p id="title" class="form-control-static"><?=$Entry->getSubtitle();?></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="category_1">Kategorija</label>
                            <p id="category_1" class="form-control-static"><?=$Entry->getCategory()?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="category_2">Žanr</label>
                            <p id="category_2" class="form-control-static"><?=$Entry->getGenre()?></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="url_1">URL 1</label>
                            <p id="url_1" class="form-control-static">
                            <?=empty($Entry->getURL1()) ? '' : '<a href="' . $Entry->getURL1() . '" target="_blank">' . $Entry->getURL1() . '</a>'?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="url_2">URL 2</label>
                            <p id="url_2" class="form-control-static">
                            <?=empty($Entry->getURL2()) ? '' : '<a href="' . $Entry->getURL2() . '" target="_blank">' . $Entry->getURL2() . '</a>'?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="short_description">Kratek opis</label>
                            <p id="short_description" class="form-control-static">
                                <?=nl2br($Entry->getShortDescription());?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="description">Opis</label>
                            <p id="description" class="form-control-static">
                                <?=nl2br($Entry->getDescription());?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="description">Slika</label>
                            <p id="description" class="form-control-static">
                                <img class="img-thumbnail" src="<?=$Entry->getPictureURL();?>"/>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="xmltv">XMLTV</label>
                            <textarea class="form-control" name="xmltv" id="xmltv" rows="10"><?=$Entry->getXML()?></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <form class="form-inline" method="get" action="copy.php">
                            <input type="hidden" name="from" value="<?=$Day->getDate()?> <?=$start?>">
                            <div class="form-group">
                                <button class="btn btn-warning" type="submit" name="type" value="entry">Kopiraj v</button>
                                <select name="to" class="form-control">
                                    <?php
                                    $S = clone $Day->getDay();
                                    $S->sub(new DateInterval('P7D'));
                                    $E = clone $Day->getDay();
                                    $E->add(new DateInterval('P14D'));
                                    for($DT = clone $S; $DT <= $E; $DT->add(new DateInterval('P1D'))){
                                        echo '<option value="' . $DT->format('Y-m-d') . '"' 
                                        . ($DT == $Tomorrow ? ' selected' : '') . '>' 
                                        . schedule_translate_days($DT->format('Y-m-d (l)')) 
                                        . '</option>' . "\n";
                                    }
                                    ?>
                                </select>
                                &nbsp;
                            </div>
                            <div class="form-group">
                                <label for="start">Začetek</label>
                                <input type="time" step="1" value="00:00:00" name="start" id="start">
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <a class="btn btn-success" href="index.php?date=<?=$Day->getDate();?>">Nazaj</a>
                        </div>
                    </div>
                </div>
        </div>
    </body>
</html>