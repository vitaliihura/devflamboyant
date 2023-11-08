<?php
class N24TV_Schedule_Day {

    static private $instances = [];
    private $Day    = null;
    private $entries = null;

    static public function getInstance(DateTime $Day){
        $key = $Day->format('Y-m-d');
        if (!isset(self::$instances[$key])){
            self::$instances[$key] = new self($Day);
        }
        return self::$instances[$key];
    }

    static public function getLatest(){
        $DT = new DateTime('-1 day');
        $db = N24TV_Schedule_DB::getInstance();
        $SQL = "SELECT DISTINCT `date` FROM `n24tv_schedule` WHERE `date` >= '" . $DT->format('Y-m-d') . "' ORDER BY `date` ASC";
        $r = $db->query($SQL);
        $ret = [];
        while ($rr = $r->fetch_assoc()){
            $ret[] = self::getInstance(new DateTime($rr['date']));
        }
        return $ret;
    }

    static public function count(DateTime $DT){
        $db = N24TV_Schedule_DB::getInstance();
        $SQL = "SELECT COUNT(`start`) AS ECount FROM `n24tv_schedule` WHERE `date` = '" . $DT->format('Y-m-d') . "'";
        $r = $db->query($SQL);
        $rr = $r->fetch_assoc();
        return (int)$rr['ECount'];
    }

    static public function isEmpty(DateTime $DT){
        return (self::count($DT) > 0 ? false : true);
    }

    static public function isOk(DateTime $DT){
        $Day = self::getInstance($DT);
        $entries = $Day->getEntries();
        $preEntry = null;
        $ret = [];
        foreach($entries as $Entry){
            if ($preEntry !== null){
                if ($Entry->isOverlapping($preEntry)){
                    $ret[] = [$preEntry, $Entry];
                }
            }
            $preEntry = $Entry;
        }
        return (empty($ret) ? true : $ret);
    }

    public function __construct(DateTime $Day){
        $this->setDay($Day);
    }

    public function __toString(){
        return __CLASS__ . '(' . $this->getDate() . ')';
    }

    public function copy(N24TV_Schedule_Day $To){
        $db = N24TV_Schedule_DB::getInstance();
        $SQL = "DELETE FROM `n24tv_schedule` WHERE `date` = '" . $To->getDate() . "'";
        $db->query($SQL);

        foreach($this->getEntries() as $O){
            $O->copy($To);
        }
    }

    public function getNextStart(){
        $entries = $this->getEntries();
        if (count($entries) > 0){
            $Last = end($entries);
            $LastStart = clone $Last->getEnd();
            $LastStart->add(new DateInterval('PT1S'));
            return $LastStart;
        }
        return new DateTime($this->getDate() . ' 00:00:00');
    }

    public function getNextEnd(){
        $NextEnd = $this->getNextStart();
        $NextEnd->add(new DateInterval('PT1H'))->sub(new DateInterval('PT1S'));
        return $NextEnd;
    }

    protected function setDay(DateTime $Day){
        $this->Day = $Day;
        return $this;
    }

    public function getDay(){
        return $this->Day;
    }

    public function getDate(){
        if ($this->Day === null)
            throw new Exception('Day not specified');
        return $this->Day->format('Y-m-d');
    }

    protected function loadEntries(){
        $this->entries = N24TV_Schedule_Entry::loadDay($this);
    }

    public function getEntries(){
        if ($this->entries === null){
            $this->loadEntries();
        }
        return $this->entries;
    }

}
?>