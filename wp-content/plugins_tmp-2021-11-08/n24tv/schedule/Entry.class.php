<?php
class N24TV_Schedule_Entry {

    private $Day    = null;
    private $Start  = null;
    private $End    = null;
    private $title  = null;
    private $subtitle = null;
    private $category = null;
    private $genre  = null;
    private $url_1 = null;
    private $url_2 = null;
    private $short_description   = null;
    private $description = null;
    private $picture    = null;
    private $premiere   = false;
    private $live       = false;
    private $previously_shown = false;

    private $new        = true; // was it loaded or is this a fresh insert?

    static public function loadDay(N24TV_Schedule_Day $Day){
        $ret = [];
        $date = $Day->getDate();
        $db = N24TV_Schedule_DB::getInstance();
        $SQL = "SELECT * FROM `n24tv_schedule` WHERE `date` = '" . $date . "' ORDER BY `start` ASC";
        $r = $db->query($SQL);
        while ($rr = $r->fetch_assoc()){
            $E = new self($Day);
            $E->load_rr($rr);
            $ret[] = $E;
        }
        return $ret;
    }

    static public function isFree(DateTime $Start, DateTime $End){
        if ($Start > $End)
            throw new Exception('Start > End');
        $db = N24TV_Schedule_DB::getInstance();
        $SQL = "SELECT COUNT(`start`) AS ECount FROM `n24tv_schedule` WHERE " . 
               "`end` >= '" . $Start->format('Y-m-d H:i:s') . "' AND " . 
               "`start` <= '" . $End->format('Y-m-d H:i:s') . "'";
        $r = $db->query($SQL);
        $rr = $r->fetch_assoc();
        return ($rr['ECount'] > 0 ? false : true);
    }

    static public function get(N24TV_Schedule_Day $Day, $start){
        $entries = $Day->getEntries();
        $StartDT = new DateTime($Day->getDate() . ' ' . $start);
        foreach($entries as $E){
            if ($E->getStart() == $StartDT){
                return $E;
            }
        }
        return null;
    }

    static public function getCDATA($string){
        return '<![CDATA[' . $string . ']]>';
    }

    public function __construct(N24TV_Schedule_Day $Day){
        $this->setDay($Day);
    }

    public function load_rr($rr){
        $this->new = false;
        $this
            ->setStart(new DateTime($rr['start']))
            ->setEnd(new DateTime($rr['end']))
            ->setTitle($rr['title'])
            ->setSubtitle($rr['subtitle'])
            ->setCategory($rr['category'])
            ->setGenre($rr['genre'])
            ->setURL1($rr['url_1'])
            ->setURL2($rr['url_2'])
            ->setShortDescription($rr['short_description'])
            ->setDescription($rr['description'])
            ->setPicture($rr['picture'])
            ->setPremiere($rr['premiere'] == 'Y' ? true : false)
            ->setLive($rr['live'] == 'Y' ? true : false)
            ->setPreviouslyShown($rr['previously_shown'] == 'Y' ? true : false);
        return $this;
    }

    public function save(){
        if (empty($this->Day))
            throw new Exception('Day not set');
        if (empty($this->Start))
            throw new Exception('Start not set');
        if (empty($this->End))
            throw new Exception('End not set');
        if (empty($this->title))
            throw new Exception('Title not set');
        if ($this->new === true && !self::isFree($this->Start, $this->End)){
            throw new Exception('Termin: ' . $this->Start->format('Y-m-d H:i:s') . ' -- ' . $this->End->format('Y-m-d H:i:s') . ' je že zaseden');
        }
        $db = N24TV_Schedule_DB::getInstance();
        $SQL = "INSERT INTO `n24tv_schedule` (" . 
               "`date`, `start`, `end`, `title`, `subtitle`, " . 
               "`category`, `genre`, `url_1`, `url_2`, " . 
               "`short_description`, `description`, `picture`, " . 
               "`premiere`, `live`, `previously_shown`) VALUES(" . 
               "'" . $this->Day->getDate() . "', " . 
               "'" . $this->Start->format('Y-m-d H:i:s') . "', " . 
               "'" . $this->End->format('Y-m-d H:i:s') . "', " . 
               "'" . $db->real_escape_string($this->title) . "', " . 
               "'" . $db->real_escape_string($this->subtitle) . "', " . 
               "'" . $db->real_escape_string($this->category) . "', " . 
               "'" . $db->real_escape_string($this->genre) . "', " . 
               "'" . $db->real_escape_string($this->url_1) . "', " . 
               "'" . $db->real_escape_string($this->url_2) . "', " . 
               "'" . $db->real_escape_string($this->short_description) . "', " . 
               "'" . $db->real_escape_string($this->description) . "', " . 
               "'" . $db->real_escape_string($this->picture) . "', " . 
               "'" . ($this->premiere === true ? 'Y' : 'N') . "', " . 
               "'" . ($this->live === true ? 'Y' : 'N') . "', " . 
               "'" . ($this->previously_shown === true ? 'Y' : 'N') . "') " . 
               "ON DUPLICATE KEY UPDATE " . 
               "`end` = VALUES(`end`), " . 
               "`title` = VALUES(`title`), " . 
               "`subtitle` = VALUES(`subtitle`), " . 
               "`category` = VALUES(`category`), " . 
               "`genre` = VALUES(`genre`), " . 
               "`url_1` = VALUES(`url_1`), " . 
               "`url_2` = VALUES(`url_2`), " . 
               "`short_description` = VALUES(`short_description`), " . 
               "`description` = VALUES(`description`), " . 
               "`picture` = VALUES(`picture`), " . 
               "`premiere` = VALUES(`premiere`), " . 
               "`live` = VALUES(`live`), " . 
               "`previously_shown` = VALUES(`previously_shown`)";
        $db->query($SQL);
        $this->new = false;
    }

    public function delete(){
        $db = N24TV_Schedule_DB::getInstance();
        $SQL = "DELETE FROM `n24tv_schedule` WHERE `date` = '" . $this->Day->getDate() . "' AND `start` = '" . $this->Start->format('Y-m-d H:i:s') . "'";
        $db->query($SQL);
    }

    public function copy(N24TV_Schedule_Day $To, $start = null){

        if ($start === null){
            $Start = new DateTime($To->getDate() . ' ' . $this->getStart()->format('H:i:s'));
            $End = new DateTime($To->getDate() . ' ' . $this->getEnd()->format('H:i:s'));
        }
        else {
            $NewStart = new DateTime($this->getStart()->format('Y-m-d') . ' ' . $start);
            $diff_seconds = $NewStart->format('U') - $this->getStart()->format('U');

            $Start = new DateTime($To->getDate() . ' ' . $this->getStart()->format('H:i:s'));
            $End = new DateTime($To->getDate() . ' ' . $this->getEnd()->format('H:i:s'));
            $AddDI = new DateInterval('PT' . $diff_seconds . 'S');
            $Start->add($AddDI);
            $End->add($AddDI);
        }

        $N = new self($To);
        $N
            ->setStart($Start)
            ->setEnd($End)
            ->setTitle($this->getTitle())
            ->setSubtitle($this->getSubtitle())
            ->setCategory($this->getCategory())
            ->setGenre($this->getGenre())
            ->setURL1($this->getURL1())
            ->setURL2($this->getURL2())
            ->setShortDescription($this->getShortDescription())
            ->setDescription($this->getDescription())
            ->setPicture($this->getPicture())
            ->setPremiere($this->getPremiere())
            ->setLive($this->getLive())
            ->setPreviouslyShown($this->getPreviouslyShown())
            ->save();
    }

    // check if entry is overlapping this entry
    public function isOverlapping(N24TV_Schedule_Entry $Entry){
        return (
            ($Entry->getStart() <= $this->getEnd())
            &&
            ($Entry->getEnd() >= $this->getStart())
           );
    }

    public function getXML($channel = 'nova24tv'){
        /*
        $TZ = new DateTimeZone('UTC');
        */
        $datetime_fmt = 'YmdHis P';
        /*
        $Start = clone $this->Start;
        $End = clone $this->End;
        $Start->setTimeZone($TZ);
        $End->setTimeZone($TZ);
        */
        $Start = $this->Start;
        $End = $this->End;

        $End->add(new DateInterval('PT1S'));

        $o = [];
        $o[] = '<programme start="' . $Start->format($datetime_fmt) . '" end="' . $End->format($datetime_fmt) . '" channel="' . $channel . '">';

        $o[] = '<title lang="sl">' . $this->title . '</title>';
        if (!empty($this->subtitle))
            $o[] = '<sub-title lang="sl">' . $this->subtitle . '</sub-title>';

        if (!empty($this->short_description))
            $o[] = '<desc lang="sl">' . self::getCDATA($this->short_description) . '</desc>';
        if (!empty($this->description))
            $o[] = '<desc lang="sl">' . self::getCDATA($this->description) . '</desc>';

        if ($this->premiere === true || $this->live === true){
            $o[] = '<premiere' . ($this->live === true ? ' lang="sl">V živo</premiere>' : '/>');
        }
        if ($this->previously_shown === true){
            $o[] = '<previously-shown />';
        }

        if (!empty($this->category))
            $o[] = '<category lang="sl">' . $this->category . '</category>';
        if (!empty($this->genre))
            $o[] = '<category lang="sl">' . $this->genre . '</category>';
        if (!empty($this->url1))
            $o[] = '<url>' . $this->url1 . '</url>';
        if (!empty($this->url2))
            $o[] = '<url>' . $this->url2 . '</url>';
        $picture = $this->picture;
        $url = $this->getPictureURL();
        if (!empty($url)){
            $o[] = '<icon src="' . $url . '" width="1280" height="720" />';
        }

        $o[] = '</programme>';
        return implode("\n", $o);
    }

    protected function setDay(N24TV_Schedule_Day $Day){
        $this->Day = $Day;
        return $this;
    }

    public function getDay(){
        return $this->Day;
    }

    public function setStart(DateTime $Start){
        $this->Start = $Start;
        return $this;
    }

    public function getStart(){
        return $this->Start;
    }

    public function setEnd(DateTime $End){
        if ($this->Start === NULL)
            throw new Exception('Set start first');
        if ($End <= $this->Start)
            throw new Exception('End is >= Start: start=' . $this->Start->format('Y-m-d H:i:s') . ' end=' . $End->format('Y-m-d H:i:s'));
        $this->End = $End;
        return $this;
    }

    public function getEnd(){
        return $this->End;
    }

    public function getLength(){
        if ($this->Start === NULL || $this->End === NULL){
            return '0';
        }
        $detailed = true;
        $Start = $this->Start;
        $End = $this->End;
        $Interval = $End->diff($Start); 
        $doPlural = function($nb,$str){return $nb>1?$str.'s':$str;}; // adds plurals 
        $format = array(); 
        if($Interval->y !== 0) { 
          $format[] = "%y ".$doPlural($Interval->y, "year"); 
        } 
        if($Interval->m !== 0 && ($detailed === true || !count($format))) { 
          $format[] = "%m ".$doPlural($Interval->m, "month"); 
        } 
        if($Interval->d !== 0 && ($detailed === true || !count($format))) { 
          $format[] = "%d ".$doPlural($Interval->d, "day"); 
        } 
        if($Interval->h !== 0 && ($detailed === true || !count($format))) { 
          $format[] = "%h ".$doPlural($Interval->h, "hr"); 
        } 
        if($Interval->i !== 0 && ($detailed === true || !count($format))) { 
          $format[] = "%i ".$doPlural($Interval->i, "min"); 
        } 
        if($Interval->s !== 0) { 
          if(!count($format)) { 
            return "less than a minute"; 
          } else { 
            if ($detailed)
                $format[] = "%s ".$doPlural($Interval->s, "sec"); 
          } 
        } 
        // We use the two biggest parts 
        if(count($format) > 1) { 
          $format = array_shift($format)." and ".array_shift($format); 
        } else { 
          $format = array_pop($format); 
        }
        return $Interval->format($format);
    }

    public function setTitle($title){
        $title = trim($title);
        if (empty($title))
            throw new Exception('Title is not valid');
        $this->title = $title;
        return $this;
    }

    public function getTitle(){
        return $this->title;
    }

    public function setSubtitle($subtitle){
        $this->subtitle = trim($subtitle);
        return $this;
    }

    public function getSubtitle(){
        return $this->subtitle;
    }

    public function setCategory($name){
        $this->category = trim($name);
        return $this;
    }

    public function getCategory(){
        return $this->category;
    }

    public function setGenre($genre){
        $this->genre = trim($genre);
        return $this;
    }

    public function getGenre(){
        return $this->genre;
    }

    public function setURL1($url){
        $this->url_1 = trim($url);
        return $this;
    }

    public function getURL1(){
        return $this->url_1;
    }

    public function setURL2($url){
        $this->url_2 = trim($url);
        return $this;
    }

    public function getURL2(){
        return $this->url_2;
    }

    public function setShortDescription($descr){
        $this->short_description = trim($descr);
        if (mb_strlen($this->short_description) > 150){
            throw new Exception('Short description is limited to 150 chars');
        }
        return $this;
    }

    public function getShortDescription(){
        return $this->short_description;
    }

    public function setDescription($descr){
        $descr = trim($descr);
        if (empty($descr))
            throw new Exception('Description is not valid');
        $this->description = $descr;
        return $this;
    }

    public function getDescription(){
        return $this->description;
    }

    public function setPicture($picture){
        if (empty($picture))
            $picture = null;
        $this->picture = $picture;
        return $this;
    }

    public function getPicture(){
        return $this->picture;
    }

    public function getPictureURL(){
        if (is_numeric($this->picture)){
            return $this->getWPPictureURL();
        }
        else {
            return $this->picture;
        }
    }

    public function setPremiere($b){
        $this->premiere = (bool)$b;
        return $this;
    }

    public function getPremiere(){
        return $this->premiere;
    }

    public function setLive($b){
        $this->live = (bool)$b;
        return $this;
    }

    public function getLive(){
        return $this->live;
    }

    public function setPreviouslyShown($b){
        $this->previously_shown = (bool)$b;
        return $this;
    }

    public function getPreviouslyShown(){
        return $this->previously_shown;
    }

    protected function getWPPictureURL($regenerate = true){
        $id = (int)$this->picture;
        $tmp = wp_get_attachment_image_src($id, 'xmltv-feed');
        if ($tmp === false || !is_array($tmp) || count($tmp) != 4){
            return null;
        }
        list($url, $width, $height, $is_intermediate) = $tmp;
        if ($width >= 1280 || $height >= 720){
            if ($width == 1280 && $height == 720){
                return $url;
            }
            else if($regenerate) {
                if ($this->regerenrateThumbnails($id)){
                    return $this->getWPPictureURL(false);
                }
            }
        }
        return null;
    }

    protected function regerenrateThumbnails($id){
        $full_path = get_attached_file($id);
        if (empty($full_path)){
            return false;
        }
        $metadata = wp_generate_attachment_metadata($id, $full_path);

        if (is_wp_error($metadata)){
            return false;
        }
        elseif(empty($metadata)){
            return false;
        }
        wp_update_attachment_metadata($id, $metadata);
        return true;
    }

}
?>