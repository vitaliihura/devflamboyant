<?php
require(__DIR__ . '/schedule.inc.php');
$date = ($_GET['date'] ?? date('Y-m-d'));

// get date's pagination
$DateTime = new DateTime($date);
$date = $DateTime->format('Y-m-d');
$date_str = schedule_translate_days($DateTime->format('Y-m-d (l)'));
$PreviousWeek = new DateTime($date);
$PreviousWeek->sub(new DateInterval('P7D'));
$Yesterday = new DateTime($date);
$Yesterday->sub(new DateInterval('P1D'));
$NextWeek = new DateTime($date);
$NextWeek->add(new DateInterval('P7D'));
$Tomorrow = new DateTime($date);
$Tomorrow->add(new DateInterval('P1D'));

$pre_week = $PreviousWeek->format('Y-m-d');
$pre_week_str = schedule_translate_days($PreviousWeek->format('Y-m-d (l)'));
$yesterday = $Yesterday->format('Y-m-d');
$yesterday_str = schedule_translate_days($Yesterday->format('Y-m-d (l)'));
$next_week = $NextWeek->format('Y-m-d');
$next_week_str = schedule_translate_days($NextWeek->format('Y-m-d (l)'));
$tomorrow = $Tomorrow->format('Y-m-d');
$tomorrow_str = schedule_translate_days($Tomorrow->format('Y-m-d (l)'));



?>
<html>
    <head>
        <meta charset="utf-8">
        <title>Spored 2: Pregled</title>
        <link rel='stylesheet' id='n24tv-css'  href='/wp-content/themes/n24tv/style.css?ver=4.7.2' type='text/css' media='all' />
        <link rel='stylesheet' id='boostrap-css'  href='/wp-content/themes/n24tv/css/bootstrap.min.css?ver=4.7.2' type='text/css' media='all' />
    </head>
    <body>
        <div class="container">
            <div class="page-header">
                <h1>Spored 2: <?=$date_str?> <small><a class="btn btn-success" href="add.php?date=<?=$date?>">Dodaj</a></small></h1>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li>
                                <a href="index.php?date=<?=$pre_week?>" title="Prejšnji teden">
                                    &laquo; 
                                    <?=$pre_week_str?> 
                                    <span class="label label-success"><?=N24TV_Schedule_Day::count($PreviousWeek)?></span>
                                </a>
                            </li>
                            <li>
                                <a href="index.php?date=<?=$yesterday?>" title="Včeraj">
                                    &lt; 
                                    <?=$yesterday_str?>
                                    <span class="label label-success"><?=N24TV_Schedule_Day::count($Yesterday)?></span>
                                </a>
                            </li>
                            <li class="active">
                                <a href="index.php?date=<?=$date?>">
                                    <?=$date_str?>
                                    <span class="label label-success"><?=N24TV_Schedule_Day::count($DateTime)?></span>
                                </a>
                            </li>
                            <li>
                                <a href="index.php?date=<?=$tomorrow?>" title="Jutri">
                                    <?=$tomorrow_str?> 
                                    <span class="label label-success"><?=N24TV_Schedule_Day::count($Tomorrow)?></span>
                                     &gt;
                                </a>
                            </li>
                            <li>
                                <a href="index.php?date=<?=$next_week?>" title="Naslednji teden">
                                    <?=$next_week_str?>
                                    <span class="label label-success"><?=N24TV_Schedule_Day::count($NextWeek)?></span> 
                                    &raquo;
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped table-hover table-responsive table-condensed" style="font-size: 0.9em;">
                        <caption><?=$date_str?></caption>
                        <thead>
                            <tr>
                                <th>Začetek</th>
                                <th>Konec</th>
                                <th>Naslov</th>
                                <th>Podnaslov</th>
                                <th>Kategorija/Žanr</th>
                                <th>Kratek opis</th>
                                <th>Možnosti</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $Day = N24TV_Schedule_Day::getInstance($DateTime);
                        $errors = N24TV_Schedule_Day::isOK($DateTime);
                        if (!is_array($errors)){
                            $errors = [];
                        }
                        function n24tv_schedule_event_in_errors(N24TV_Schedule_Entry $Entry, $errors = []){
                            foreach($errors as $events){
                                foreach($events as $E){
                                    if ($E == $Entry){
                                        return true;
                                    }
                                }
                            }
                            return false;
                        }
                        $entries = $Day->getEntries();
                        foreach($entries as $E){
                        ?>
                            <tr class="<?=n24tv_schedule_event_in_errors($E, $errors) ? 'danger' : ''?>">
                                <td><?=$E->getStart()->format('H:i:s')?></td>
                                <td><?=$E->getEnd()->format('H:i:s')?></td>
                                <td><?=$E->getTitle()?><?=n24tv_schedule_get_status_icons($E)?></td>
                                <td><?=$E->getSubtitle()?></td>
                                <td><?=$E->getCategory() . '/' . $E->getGenre()?></td>
                                <td><?=n24tv_ellipsis($E->getShortDescription(), 50)?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Možnosti">
                                        <a class="btn btn-default" href="view.php?date=<?=$E->getDay()->getDate()?>&start=<?=$E->getStart()->format('H:i:s')?>" title="Ogled vnosa">
                                            <span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>
                                        </a>
                                        <a type="button" class="btn btn-default" title="Uredi vnos" href="add.php?date=<?=$E->getDay()->getDate()?>&start=<?=$E->getStart()->format('H:i:s')?>">
                                            <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                        </a>
                                        <a type="button" onClick="return confirm('Ali ste prepričani?');" href="delete.php?date=<?=$E->getDay()->getDate()?>&start=<?=$E->getStart()->format('H:i:s')?>" class="btn btn-danger" title="Briši vnos">
                                            <span class="glyphicon glyphicon-trash" aria-hidden="true">
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <form class="form-inline" method="get" action="copy.php">
                        <input type="hidden" name="from" value="<?=$DateTime->format('Y-m-d')?>">
                        <div class="form-group">
                            <button class="btn btn-warning" type="submit" name="type" value="day"<?=(N24TV_Schedule_Day::isEmpty($DateTime) ? ' disabled' : '')?>>Kopiraj dan v</button>
                            <select name="to" class="form-control">
                                <?php
                                $S = clone $DateTime;
                                $S->sub(new DateInterval('P7D'));
                                $E = clone $DateTime;
                                $E->add(new DateInterval('P14D'));
                                for($DT = clone $S; $DT <= $E; $DT->add(new DateInterval('P1D'))){
                                    if (N24TV_Schedule_Day::isEmpty($DT)){
                                        if ($DT != $DateTime){
                                            echo '<option value="' . $DT->format('Y-m-d') . '"' 
                                            . ($DT == $Tomorrow ? ' selected' : '') . '>' 
                                            . schedule_translate_days($DT->format('Y-m-d (l)')) 
                                            . '</option>' . "\n";
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <form class="form-inline" method="get" action="copy.php">
                        <input type="hidden" name="from" value="<?=$DateTime->format('Y-m-d')?>">
                        <div class="form-group">
                            <button class="btn btn-warning" type="submit" name="type" value="week"<?=(N24TV_Schedule_Day::isEmpty($DateTime) ? ' disabled' : '')?>>Kopiraj teden v</button>
                            <select name="to" class="form-control">
                                <?php
                                $S = clone $DateTime;
                                $S->sub(new DateInterval('P1D'));
                                //$E = clone $DateTime;
                                $E = new DateTime;
				$E->add(new DateInterval('P14D'));
                                for($DT = clone $S; $DT <= $E; $DT->add(new DateInterval('P1D'))){
                                    if (N24TV_Schedule_Day::isEmpty($DT)){
                                        if ($DT != $DateTime){
                                            echo '<option value="' . $DT->format('Y-m-d') . '"' 
                                            . ($DT == $NextWeek ? ' selected' : '') . '>' 
                                            . schedule_translate_days($DT->format('Y-m-d (l)')) 
                                            . '</option>' . "\n";
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <a class="btn btn-success" href="add.php?date=<?=$date?>">Dodaj</a>
                </div>
            </div>
        </div>
    </body>
</html>
