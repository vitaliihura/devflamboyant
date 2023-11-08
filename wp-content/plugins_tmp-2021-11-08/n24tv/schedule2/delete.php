<?php
require(__DIR__ . '/schedule.inc.php');

try {
    $date = ($_GET['date'] ?? NULL);
    $start = ($_GET['start'] ?? NULL);

    if ($date === NULL || $start === NULL){
        throw new Exception('Missing arguments');
    }

    $Day = N24TV_Schedule_Day::getInstance(new DateTime($date));
    $Entry = N24TV_Schedule_Entry::get($Day, $start);
    if ($Entry === NULL)
        throw new Exception('No such schedule entry in database');
    $Entry->delete();
    header('Location: index.php?date=' . $Day->getDate());
    die();
} catch (Exception $e){
    die("ERROR: " . $e->getMessage() . "<br/>\n");
}


?>