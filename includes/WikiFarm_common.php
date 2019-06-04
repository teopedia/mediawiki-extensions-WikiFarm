<?php
use MediaWiki\MediaWikiServices;    //  for config

//-------------------------------------------------
//------- Functions
//-------------------------------------------------

/**
 * Return formated string for titles
 * @param $title Title string
 */
function wfarmFormatTitle($title) {
	$str = "<div align=center><big><big>'''" . $title . "'''</big></big></div>";
	return $str;
}//wfarmFormatTitle( $title )

/**
 * Returns formated date and time string
 * @param $datetime DateTime class string
 * @return string
 */
// more info http://www.php.net/manual/en/datetime.createfromformat.php
function wfarmFormatDate($datetime='') {
	if (empty($datetime))
		$datetime = date();	//TO-DO: now() does not work
	$date = date('Y-m-d', strtotime($datetime));
	$time = date('H:i', strtotime($datetime));
	$str = $date ."&nbsp;&nbsp;&nbsp;<small>". $time . "</small>";
	return $str;
}

/**
 * Make connection to DB and return a database object. Write debug info.
 * @param $wiki A wfarmWikiInstance object.
 * @param $debugMessage Some leading words for debug message to help to find it in log file.
 */
function wfarmNewDB($wiki, $debugMessage = "") {
	//global $IP;
	//require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" );
	//connect to DB
	$host = $wiki->db['server'];
	$dbname = $wiki->db['name'];
	$user = $wiki->db['user'];
	$pass = $wiki->db['password'];
	wfDebug($debugMessage . ": Connecting to DB: $dbname LOGIN: $user:$pass@$host ...\n");
	$db = new DatabaseMysql();
	if ($db->Open($wiki->db['server'], $wiki->db['user'], $wiki->db['password'], $wiki->db['name'])) {
		wfDebug($debugMessage . ": Connected to: $dbname.\n");
		return $db;
	} else {
		wfDebug($debugMessage . ": Connection failed to: $dbname.\n");
		return null;
	}
	return $db;
}

/**
 * Make a hint string, which will pop up on mouse cursor over the text
 * @param $str_show String to show.
 * @param $hit Pop up text.
 * @return string
 */
function wfarmHint($str_show, $hint) {
	return "<span style=\"border-bottom:1px dotted gray; cursor:help;\" title=\"$hint\">$str_show</span>";
}

/**
 * Make a hint string, which will pop up on mouse cursor over the text
 * @param $str_show String to show.
 * @param $hit Pop up text.
 * @return string
 */
function wfarmHintHidden($str_show, $hint) {
	return "<span title=\"$hint\">$str_show</span>";
}

/**
 * NOT READY YET!!!
 * Writes log to wikipage. If page does not exist, it creates it and puts in Category:Log
 * @param $str String to write
 * @param $summary Comment for post
 * @param $logWiki Wiki to write log to
 * @param $logPage Name of log page
 * @return true if log successfull or false if not. TODO: return string with error if could not write a log.
 */
function wfarmLog($str, $summary, $logWiki, $logPage) {
	//TODO: Make $logPage a global variable, logWiki = wikiAdmin;
	/*
	  $bot = new extended( $logWiki->api );
	  if ( !empty($logWiki->user) ) {
	  $bot->login( $logWiki->user, $logWiki->pass );
	  }
	  bot->addtext($logPage, $str, $summary);
	 */
	return $str;
}

/**
 * Creates summary table for wikis with counters and individual links for each wiki Special page.
 *
 * @param $wikis instance of class wfarmWikiInstance
 * @param $counterIndex Which index in variable array (arr[]) use as a counter
 * @param $link Special page link. Optional
 * @return table as string in wiki format
 *
 * @link http://www.php.net/manual/en/datetime.createfromformat.php description
 */
function wfarmSummary($wikis, $counterIndex, $link='') {
	$str_summary = '';
	$summary = 0;
	foreach ($wikis as $wiki) {
		$counter = $wiki->arr[$counterIndex];
		$summary += $counter;
		if ($counter > 0) {
			$str_summary .= "| '''[" . $wiki->url . $link ." ". $wiki->name . "''' (" . $counter . ")] ";
		} else {
			$str_summary .= "| [" . $wiki->url . $link ." <span style=\"color: grey;\">" . $wiki->name . "</span>] <span style=\"color: grey;\">(" . $counter . ")</span> ";
		}
	}
	$str_summary = "{| class=wikitable align=center" .
			"\n|-" .
			"\n! " . wfMessage('wikifarm_summary')->text() . ": " . $summary .
			"\n| " . $str_summary .
			"\n|}\n";
	return $str_summary;
}

/**
 * Returns encoded string (for example URL) with spaces replaced with underscore "_"
 * @param $str string to encode
 * @return string
 */
// OBSOLETE ? Remove?
function wiki_url_encode($str) {
	$ret = str_replace(" ", "_", $str);
	//return urlencode($ret);
      	return $ret;
}

/**
 * Check if the value is a valid date
 * @param mixed $value
 * @return boolean
 */
function isDate($value) {
    if (!$value) {
        return false;
    }

    try {
        new \DateTime($value);
        return true;
    } catch (\Exception $e) {
        return false;
    }
} // isDate()

// Obsolete function? Remove? 
function timestamp2str($ts='') {
    $str = '';
    echo "timestamp2str: $ts";
    if ( isDate($ts) ) {
        $str = date('Y-m-d H:i:s', $ts);
    }
    return $str;
}

function bool2str($bool) {
    $str = ($bool) ? 'true' : 'false';
    echo "\n bool2str: $str \n";
    return $str;
} // bool2str()


//-------------------------------------------------
//------- Classes
//-------------------------------------------------

/**
 * Holds wiki parameters and functions
 *
 */
class wfarmWikiInstance	{
    // common
    public $name;           // wiki name for WikiFarm usage; set by WikiFarm admin
    public $notes;          // notes for wiki or description 
    public $site_name;      // wiki name as in wiki configuration ($wgSiteName); get via API
    public $url;            // URL address of wiki
    public $interwiki_link;  // interwiki link, if exist in interwiki table
    public $info;           // info on update

    // bot connection via MediaWiki API
    public $api;            // URL to API
    public $user;           // bot user name
    public $pass;           // bot user password
    public $api_status;     // last saved api status
    public $api_status_date;// last saved api status date
    public $api_status_info;// last saved api status information (error code and message)
    
    // database connection via MySQl requests
    public $db;             // database connection parameters. See $wfarmDBConnection array
    public $db_id;          // database connection ID. Index in $wfarmDBConnection array. 0 = none; 1 = default; >1 user defined
    public $prefix;         // table prefix in DB for the wiki
    
    // data; 
    public $data_statistics; 
    public $data_changes; 
            
    public function __construct( $arr = array() ){ 
        $this->name   = ( isset($arr['name']) )  ? $arr['name'] : '';
        $this->notes  = ( isset($arr['notes']) ) ? $arr['notes'] : ''; 
        $this->site_name   = ( isset($arr['site_name']) )  ? $arr['site_name'] : '';
        $this->url    = ( isset($arr['url']) )   ? $arr['url'] : '';
        $this->interwiki_link = ( isset($arr['interwiki_link']) ) ? $arr['interwiki_link'] : ''; 
        $this->info   = '';
        
        $this->api    = ( isset($arr['api']) )  ? $arr['api'] : ''; 
        $this->user   = ( isset($arr['user']) ) ? $arr['user'] : '';
        $this->pass   = ( isset($arr['pass']) ) ? $arr['pass'] : '';
        $this->api_status      = ( isset($arr['api_status']) ) ? $arr['api_status'] : '';
        $this->api_status_date = ( isset($arr['api_status_date']) ) ? $arr['api_status_date'] : '';
        $this->api_status_info = ( isset($arr['api_status_info']) ) ? $arr['api_status_info'] : '';
        
        $this->db     = ( isset($arr['db']) )     ? $arr['db'] : '';
        $this->db_id  = ( isset($arr['db_id']) )  ? $arr['db_id'] : '';
        $this->prefix = ( isset($arr['prefix']) ) ? $arr['prefix'] : '';
        
        $this->data_statistics = array();
        $this->data_changes    = array();
    }

    function toArray(){
        $arr = array();
        
        // common 
        $arr['name']  = $this->name;
        $arr['notes'] = $this->notes;
        $arr['site_name']  = $this->site_name;
        $arr['url']   = $this->url;
        $arr['interwiki_link'] = $this->interwiki_link;
        
        // bot connection via MediaWiki API
        $arr['api']   = $this->api;     // for anonymous connection also
        $arr['user']  = $this->user;
        $arr['pass']  = $this->pass;
        $arr['api_status']      = $this->api_status;
        $arr['api_status_date'] = $this->api_status_date;
        $arr['api_status_info'] = $this->api_status_info;
        
        // database connection via MySQl requests
        $arr['db_id'] = $this->db_id;
        if ( empty($this->db_id) ){
            $arr['db'] = $this->db;
        }
        $arr['prefix'] = $this->prefix;
        
        return $arr;
    } // toArray()
    
    /**
     * Returns link as interwiki or external URL, depending on if the wiki is in the interwiki table. 
     * Returns plain text if URL is not set.
     * @param $page Page name
     * @param $reference Link text ($page used if empty)
     * @return String
     */    
    function link($page='', $reference='') {
        $str = ($reference) ? $reference : $page;   //  set return value for empty URL
        
        // make link if URL is not empty
        if ( !empty($this->url)) {
            if ( empty($this->interwiki_link) ) { // link without interwiki
                $str = '['. $this->url . $page .' '. $reference .']';   //wiki_url_encode($page) 
            } else {    // interwiki link
                if ( empty ($reference) AND empty($page)) {
                    $str = "[[:$this->interwiki_link:|$this->name]]";
                } elseif ( empty ($reference) ) {
                    $str = "[[:$this->interwiki_link:$page|$page]]";
                } else {
                    $str = "[[:$this->interwiki_link:$page|$reference]]";
                }
            }
        }
	return $str;
    } // link()

} //wfarmWikiInstance class

?>
