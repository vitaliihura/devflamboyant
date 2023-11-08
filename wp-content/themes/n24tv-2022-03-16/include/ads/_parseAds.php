<?php
$f = __DIR__ . '/ads.txt';

echo "<pre>\n";

const S_EXPECT_KEY = 'EXPECT_KEY';
const S_READ_SCRIPT = 'READ_SCRIPT';

// map positions
$arrPositions = array(
    'oglasno ozadje'    => 'background',
    '728x90 spodaj'     => 'bottom',
    'billboard zgoraj'  => 'billboard',
    'billboard'         => 'billboard',
    '300x250 desno'     => 'sidebar-top',
    '300x250 zgoraj'    => 'sidebar-top',
    '300x250 desno zgoraj'  => 'sidebar-top',
    '728x90 zgoraj'     => 'upper',
    'banderola'         => 'banderola',
    '728x90 pod rubriko Novice' => 'front-page-novice',
    '728x90 pod rubriko _port'  => 'front-page-sport',
    '300x250 spodaj'    => 'sidebar-bottom',
    '300x250 desno spodaj'  => 'sidebar-bottom',
    'floater'           => 'floater',
    'preroll video'     => 'preroll-video',
    '300x250 pod _lankom'   => 'single-bottom',
    '728x90 nad komentarji' => 'single-comment',
);

$arrConfig = array();

try {
    $fp = fopen($f, 'r');
    if (!$fp)
        throw new Exception('Failed to open file: ' . $f);

    $line = 0;
    $key = NULL;
    $position = NULL;
    $slave_id = NULL;
    $master_id = NULL;

    $status = S_EXPECT_KEY;
    while (!feof($fp)){
        ++$line;

        $l = fgets($fp);
        $l = trim($l);
        if (strlen($l) == 0)
            continue;
        //echo "$line: " . htmlentities($l) . "\n";

        if (preg_match('/== ([a-z\:\-\/]+) ==/', $l, $m) > 0 && isset($m[1])){
            $key = trim($m[1]);
            //echo " - got key: $key\n";
            $arrConfig[$key] = array();
        }

        elseif (preg_match('/\/\* (.*) \*\/$/', $l, $m) > 0 && isset($m[1])){
            $position = explode('.', $m[1]);
            $position = array_pop($position);
            //echo " - got position: $position\n";
        }

        elseif (substr($l, 0, 10) == 'ado.master'){
            $slave_id = 'MASTER';
            $master_id = 'MASTER';
        }

        elseif (preg_match('/ado\.slave\(\'([a-z]+)\', \{myMaster\: \'([a-zA-Z0-9\.\_]+)\' \}\)\;/', $l, $m) && isset($m[1]) && isset($m[2])){
            $slave_id = $m[1];
            $master_id = $m[2];
            if (strlen($slave_id) != 19)
                throw new Exception('Line: ' . $line . ': Received slave Id of invalid length: ' . $slave_id . ' len=' . strlen($slave_id));
            if (strlen($master_id) != 46)
                throw new Exception('Line: ' . $line . ': Received master Id of invalid length: ' . $master_id . ' len=' . strlen($master_id));
        }

        elseif($l == '</script>'){
            if ($key === NULL)
                throw new Exception('Line: ' . $line . '. Received end of script, without key set!?');
            if ($position === NULL)
                throw new Exception('Line: ' . $line . '. Received end of script without position set!?');
            if ($slave_id == 'MASTER' && $master_id == 'MASTER'){
                //echo " - Skipping ado.master configuration\n";
            }
            else {
                if (!isset($arrPositions[$position]))
                    throw new Exception('Line: ' . $line . ': No such possition mapping exists: ' . $position);
                if (empty($slave_id))
                    throw new Exception('Line: ' . $line . '. Received end of script without slave Id set');
                if (empty($master_id))
                    throw new Exception('Line: ' . $line . '. Received end of script without master Id set');
                $position = $arrPositions[$position];
                //echo " *** key: $key position: $position slave_id=$slave_id master_id=$master_id\n";
                $arrConfig[$key][$position] = array('slave_id' => $slave_id, 'master_id' => $master_id);
            }

            $slave_id = $master_id = $position = NULL;
        }

    }
    $o = array();
    $o[] = '<?php';
    $o[] = '/**';
    $o[] = ' * AdOcean Configuration';
    $o[] = ' *';
    $o[] = ' * parsed from ' . $f . ' @ ' . date('Y-m-d H:i:s');
    $o[] = ' *';
    $o[] = ' * Locations were mapped from: ';
    foreach($arrPositions as $from => $to){
        $o[] = ' * ' . $from . ' => ' . $to;
    }
    $o[] = ' *';
    $o[] = ' */';
    $o[] = '';
    $o[] = '$adoceanConfig = array(';
    foreach($arrConfig as $key => $kCfg){
        $o[] = "'$key' => array(";

        foreach($kCfg as $position => $cfg){
            $o[] = "\t'$position' => array('slave_id' => '" . $cfg['slave_id'] . "', 'master_id' => '" . $cfg['master_id'] . "'),";
        }

        $o[] = '),';
    }

    $o[] = ');';
    $o[] = '/* end of AdOcean Configuration */';
    $o[] = '?>';

    file_put_contents(__DIR__ . '/adocean.inc.php', implode("\n", $o));

} catch (Exception $e){
    echo " ERROR: " . $e->getMessage() . "\n";
    echo " TRACE: " . $e->getTraceAsString() . "\n";
}
?>