<?php
define ('WP_CACHE', false);
if ( !defined('ABSPATH') ) {
    /** Set up WordPress environment */
    require_once( dirname( __FILE__ ) . '/../../../../wp-load.php' );
}

if (!defined('N24TV_SCHEDULE_DISPLAY_XML') || N24TV_SCHEDULE_DISPLAY_XML === FALSE){
    if (!current_user_can('publish_posts')){
        die("No permissions to access this page");
    }
}

if (!class_exists('N24TV')){
    die("Plugin not activated!");
}

define('N24TV_SCHEDULE_TABLE', 'n24tv_schedule');

date_default_timezone_set('Europe/Ljubljana');

// include( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );

require(__DIR__ . '/../lib/schedule/DB.class.php');
require(__DIR__ . '/../lib/schedule/Day.class.php');
require(__DIR__ . '/../lib/schedule/Entry.class.php');

if (!function_exists('n24tv_ellipsis')) {
	/**
	 * Replace oversized text and append "..."
	 */
	function n24tv_ellipsis($text, $max = 70, $append = '&hellip;'){
		if (mb_strlen($text) <= $max) return $text;
		$out = mb_substr($text,0,$max);
		if (mb_strpos($text,' ') === FALSE) return $out.$append;
		return mb_ereg_replace('/\w+$/','',$out).$append;
	}
}

$arrCategories = [
    'Dokumentarni program' => 
        [
            'Biografija',
            'Družbslovje',
            'Glasba',
            'Magazin',
            'Naravoslovje',
            'Ostalo',
            'Poljudnoznanstvena oddaja',
            'Potopis',
            'Reportaža',
            'Šport',
            'Tehnika',
            'Umetnost',
            'Zgodovina',
        ],
    'Dokumentarna serija' => 
        [
            'Biografija',
            'Družbslovje',
            'Glasba',
            'Magazin',
            'Naravoslovje',
            'Ostalo',
            'Poljudnoznanstvena oddaja',
            'Potopis',
            'Reportaža',
            'Šport',
            'Tehnika',
            'Umetnost',
            'Zgodovina',
        ],
    'Erotični program' => 
        [
            'Film',
            'Magazin',
            'Nadaljevanka',
            'Nanizanka',
            'Ostalo',
        ],
    'Film' => 
        [
            'Kratki',
            'Kriminalka',
            'Muzikal',
            'Ostalo',
            'Otroški ali mladinski',
            'Potopis',
            'Pustolovščina',
            'Romantična komedija',
            'Romantični',
            'Srhljivka',
            'Šport',
            'Triler',
            'Vestern',
            'Vojni',
            'Zgodovina',
            'Znanstvena fantastika',
        ],
    'Informativni program' => 
        [
            'Dnevno-informativna oddaja',
            'Gospodarstvo',
            'Infokanal',
            'Informativna oddaja',
            'Intervju',
            'Magazin',
            'Ostalo',
            'Pogovorna oddaja',
            'Politika',
            'Promet',
            'Šport',
            'Vreme',
        ],
    'Izobraževalni program' => 
        [
            'Kuharska oddaja',
            'Ostalo',
            'Pogovorna oddaja',
            'Svetovalna oddaja',
            'Vzgojna oddaja',
        ],
    'Kulturno-umetniški program' => 
        [
            'Glasba',
            'Književnost',
            'Koncert',
            'Kultura',
            'Opera',
            'Ostalo',
            'Ples',
            'Predstava',
            'Prireditev',
            'Proslava',
            'Umetnost',
        ],
    'Nadaljevanka' => 
        [
            'Akcija',
            'Animirani',
            'Biografija',
            'Dokumentarni',
            'Domišljijski',
            'Drama',
            'Družinski',
            'Glasba',
            'Grozljivka',
            'Humoristični',
            'Kriminalka',
            'Ostalo',
            'Otroški ali mladinski',
            'Potopis',
            'Pustolovščina',
            'Romantični',
            'Srhljivka',
            'Telenovela',
            'Triler',
            'Vestern',
            'Vojni',
            'Zgodovina',
            'Znanstvena fantastika',
        ],
    'Nanizanka' => 
        [
            'Akcija',
            'Animirani',
            'Biografija',
            'Dokumentarni',
            'Domišljijski',
            'Drama',
            'Družinski',
            'Glasba',
            'Grozljivka',
            'Humoristični',
            'Kriminalka',
            'Ostalo',
            'Otroški ali mladinski',
            'Potopis',
            'Pustolovščina',
            'Romantični',
            'Srhljivka',
            'Triler',
            'Vestern',
            'Vojni',
            'Zgodovina',
            'Znanstvena fantastika',
        ],
    'Otroški in mladinski program' => 
        [
            'Izobraževalna oddaja',
            'Kontaktna oddaja',
            'Kviz',
            'Ostalo',
            'Otroška oddaja',
            'Pogovorna oddaja',
            'Predstava',
            'Risanka',
            'Šolska oddaja',
            'Šport',
            'Zabavna oddaja',
        ],
    'Propagandni program' => 
        [
            'Napovedniki',
            'Oglasi',
            'Ostalo',
            'TV prodaja',
        ],
    'Razvedrilni program' => 
        [
            'Glasba',
            'Igre na srečo',
            'Infokanal',
            'Koncert',
            'Kontaktna oddaja',
            'Kviz',
            'Magazin',
            'Moda',
            'Ostalo',
            'Pogovorna oddaja',
            'Potopis',
            'Predstava',
            'Prireditev',
            'Resničnostna oddaja',
            'Zabavna oddaja',
        ],
    'Resničnostna serija' => 
        [
            'Ostalo',
        ],
    'Šport' => 
        [
            'Ameriški nogomet',
            'Atletika',
            'Avto-moto športi',
            'Badminton',
            'Biatlon',
            'Biljard',
            'Boks',
            'Borilni športi',
            'Deskanje na snegu',
            'Drsanje',
            'Ekstremni športi',
            'Formula 1',
            'Gimnastika',
            'Golf',
            'Hokej',
            'Informativna oddaja',
            'Jadranje',
            'Kolesarstvo',
            'Konjeništvo',
            'Košarka',
            'Motokros',
            'Namizni tenis',
            'Nogomet',
            'Odbojka',
            'Ostalo',
            'Plavanje',
            'Ples',
            'Poker',
            'Ragbi',
            'Reportaža',
            'Rokomet',
            'Smučanje',
            'Smučarski skoki',
            'Smučarski teki',
            'Tenis',
            'Vaterpolo',
            'Veslanje',
        ],
    'Verski program' => 
        [
            'Ostalo',
            'Verska oddaja',
            'Verski obred',
        ],
];


if (isset($_POST['add-schedule'])){
    try {
        if (isset($_POST['id'])){
            $DT = new DateTime($_POST['id']);
            $Day = N24TV_Schedule_Day::getInstance($DT);
            $Entry = N24TV_Schedule_Entry::get($Day, $DT->format('H:i:s'));
            if ($Entry === null){
                die('No such entry');
            }
        }
        else {
            $Day = N24TV_Schedule_Day::getInstance(new DateTime($_POST['date']));
            $Entry = new N24TV_Schedule_Entry($Day);
        }
        $Entry
            ->setStart(new DateTime($_POST['date'] . ' ' . $_POST['start']))
            ->setEnd(new DateTime($_POST['date'] . ' ' . $_POST['end']))
            ->setTitle($_POST['title'])
            ->setSubtitle($_POST['subtitle'])
            ->setCategory($_POST['category'])
            ->setGenre($_POST['genre'])
            ->setURL1($_POST['url_1'])
            ->setURL2($_POST['url_2'])
            ->setShortDescription($_POST['short_description'])
            ->setDescription($_POST['description'])
            ->setPicture($_POST['picture'])
            ->setPremiere($_POST['premiere'] == 'Y' ?? false)
            ->setLive($_POST['live'] == 'Y' ?? false)
            ->setPreviouslyShown($_POST['previously_shown'] == 'Y' ?? false)
            ->save();
        header('Location: index.php?date=' . $Day->getDate());
        die();
    } catch (Exception $e){
        $GLOBALS['post-error'] = $e->getMessage();
    }
}

function schedule_translate($str){
    switch($str){
        case 'add':
            return 'dodaj';
        case 'Add':
            return 'Dodaj';
        case 'edit':
            return 'uredi';
        case 'Edit':
            return 'Uredi';
        default:
            return $str;
    }
}

function schedule_translate_days($str){
    $arrSearch = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday'
    ];
    $arrReplace = [
        'Ponedeljek',
        'Torek',
        'Sreda',
        'Četrtek',
        'Petek',
        'Sobota',
        'Nedelja'
    ];
    return str_replace($arrSearch, $arrReplace, $str);
}

function n24tv_schedule_get_status_icons(N24TV_Schedule_Entry $E){
    $premiere = $E->getPremiere();
    $live = $E->getLive();
    $previously_shown = $E->getPreviouslyShown();
    if ($premiere || $live || $previously_shown){
        $o = [];
        $o[] = '<p class="pull-right" style="font-size: 0.8em;">';
        if ($premiere){
            $o[] = '<span title="Premiera" class="label label-success"><i class="fa fa-fw fa-star" aria-hidden="true"></i></span>';
        }
        if ($live){
            $o[] = '<span title="V živo" class="label label-warning"><i class="fa fa-fw fa-tv" aria-hidden="true"></i></span>';
        }
        if ($previously_shown){
            $o[] = '<span title="Ponovitev" class="label label-default"><i class="fa fa-fw fa-history" aria-hidden="true"></i></span>';
        }
        $o[] = '</p>';
        return implode("\n", $o);
    }
    return '';
}
