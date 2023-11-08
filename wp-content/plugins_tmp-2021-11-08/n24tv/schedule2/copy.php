<?php
require(__DIR__ . '/schedule.inc.php');

try {

    $from = ($_GET['from'] ?? NULL);
    $to = ($_GET['to'] ?? NULL);
    $type = ($_GET['type'] ?? NULL);
    $start = ($_GET['start'] ?? NULL);

    if ($from === null || $to === null || $type === null)
        throw new Exception('Missing parameters');

    $FDay = N24TV_Schedule_Day::getInstance(new DateTime($from));
    $TDay = N24TV_Schedule_Day::getInstance(new DateTime($to));

    switch($type){
        case 'day':

            if (N24TV_Schedule_Day::isEmpty($FDay->getDay())){
                throw new Exception('Dan iz kjer kopirate je prazen!');
            }
            if (!N24TV_Schedule_Day::isEmpty($TDay->getDay())){
                throw new Exception('Dan kamor kopirate mora biti prazen!');
            }

            $FDay->copy($TDay);
            header("Location: index.php?date=" . $TDay->getDate());
            die();
        case 'week':
            // testing timeranges
            $S = clone $TDay->getDay();
            //$S->add(new DateInterval('P7D'));
            $E = clone $S;
            $E->add(new DateInterval('P7D'));

            // all days have to be empty!
            for($DT = clone $S; $DT < $E; $DT->add(new DateInterval('P1D'))){
                if (!N24TV_Schedule_Day::isEmpty($DT)){
                    throw new Exception('Vsi dnevi, kamor se kopira, morajo biti prazni. Dan ' . schedule_translate_days($DT->format('Y-m-d (l)')) . ' ni prazen');
                }
            }
            // copy timeranges
            $S = clone $FDay->getDay();
            $E = clone $S;
            $E->add(new DateInterval('P7D'));

            // check if days that are being copied have no overlapping entries
            for($DT = clone $S; $DT < $E; $DT->add(new DateInterval('P1D'))){
                $ret = N24TV_Schedule_Day::isOk($DT);
                if (is_array($ret)){
                    $msg = 'Overlapping entries found: ' . "\n";
                    foreach($ret as $entries){
                        list($pre, $cur) = $entries;
                        $msg .= "Entry: start: " . $pre->getStart()->format('Y-m-d H:i:s') . ' overlapps Entry: start: '. $cur->format('Y-m-d H:i:s') . "\n";
                    }
                    throw new Exception($msg);
                }
            }

            // now copy all days that are not empty
            $_to = new DateTime($to);
            for($DT = clone $S; $DT < $E; $DT->add(new DateInterval('P1D'))){
                if (!N24TV_Schedule_Day::isEmpty($DT)){
                    $_F = N24TV_Schedule_Day::getInstance($DT);
		    $_T = N24TV_Schedule_Day::getInstance($_to);
                    $_F->copy($_T);
		    $_to->add(new DateInterval('P1D'));
                }
            }
            header("Location: index.php?date=" . $TDay->getDate());
            die();
        case 'entry':
            if ($start === null)
                throw new Exception('Missing parameters');
            $From = new DateTime($from);
            $Entry = N24TV_Schedule_Entry::get($FDay, $From->format('H:i:s'));
            if (empty($Entry))
                throw new Exception('Vnos ne obstaja');
            $Entry->copy($TDay, $start);
            header("Location: index.php?date=" . $TDay->getDate());
            die();
        default:
            die('Neznana operacija');
    }

} catch (Exception $e){
    die("Napaka: " . $e->getMessage() . "<br/>\n");
}
