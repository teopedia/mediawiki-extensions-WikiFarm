<?php

class SpecialWikiFarm extends SpecialPage {
    public $dbs = array();      // list of db connections
    public $wikis = array();    // list of wikis
    public $admin_wiki;         // admin wiki of wfarmWikiInstance class
    public $time_start;         // time of execution
    public $has_info = false;   // show or not the info column; = 'true' if at least one wiki has non-empty 'info' 

function __construct() {
    parent::__construct( 'WikiFarm', 'editinterface' );	  // restrict to sysops group, since only they have 'editinterface' right
}

function execute( $par ) {  //$par contains subpage name = WikiFarm module name
    $out = $this->getOutput();  // get object for output = content area
    //if ( !empty($par) ){ $out->addWikiText( 'Current module: '. $par ); }     // debug, 
    
    // Restrict access to special page. See __construct(). 
    if ( !$this->userCanExecute( $this->getUser() ) ) {
            $this->displayRestrictionError();
            return;
    }

    $this->time_start = microtime(true); 
    $this->setHeaders();        // Set page title
    $this->outputHeader();
    $this->DefineWikis();       // set all class variables using config files

    $module = strtolower($par);   // current module
    switch ($module) {
        case "links":
            $this->ShowHeader('links');
            $this->ShowPageLinks();
            break;
        case "changes":
            $this->ShowHeader('changes');
            $this->ShowPageChanges();
            break;
        case "statistics":
            $this->ShowHeader('statistics');
            $this->ShowPageStatistics();
            break;
        case "users":
            $this->ShowHeader('users');
            $this->ShowPageUsers();
            break;
        case "update":
            $this->WikiListUpdate();
            // after update go on and show main page
            //TO_DO: make update works for each module. Implement after storing module data in files or DB
        case "main":
        default: 
            $this->ShowHeader();
            $this->ShowPageMain();          
            break;
    }
    $this->ShowFooter(); 
} // function execute()

/** ****************************************************************************
 * Set up list of wikis to watch and administer
 */
function DefineWikis() {
    $conf = $this->getConfig();     // object to get configuration variables from extension.json and LocalSettings.php

    if ( $conf->get( 'WikifarmWikiListMode' ) == "memory" ){   
        $this->WikiListCreate();
    } else {        // == "file"
        $this->WikiListRead();
    }
} // DefineWikis()

/** ****************************************************************************
 * Read wiki list from file
 */
function WikiListRead(){
    require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions and classes
    $out = $this->getOutput();      // for error and debug messages
    $conf = $this->getConfig();     // object to get configuration variables from extension.json and LocalSettings.php

    $json_conf = file_get_contents($conf->get( "WikifarmWikiListFile"), true);
    //$json_contents = utf8_encode($json_conf);
    //$new_wiki_conf = json_decode($json_contents, true); 
    $new_wiki_conf = json_decode($json_conf, true); 
    if (json_last_error() === JSON_ERROR_NONE) {    // no error, go on
        $this->dbs = $new_wiki_conf['databases'];
        $this->addDB(); // add empty and default DB connections
        $this->admin_wiki = new wfarmWikiInstance( $new_wiki_conf['admin wiki'] );

        //convert array to wfarmWikiInstance objects
        foreach ( $new_wiki_conf['wikis'] as $i => $one_wiki){  // get from WikiFarm_config.php
            $this->wikis[] = new wfarmWikiInstance( $one_wiki );
        }
        /*
        // debug
        echo "\n== DBs from JSON file ==================================\n";
        print_r($this->dbs);
        echo "\n== Admin wiki from JSON file ===========================\n";
        print_r($this->admin_wiki);
        echo "\n== Wikis from JSON file ================================\n";
        print_r($this->wikis);        
        */
    } else {
        $out->addWikiText( 'ERROR in <b>'. $conf->get( "WikifarmWikiListFile") .'</b>! JSON file is not correct' );
    }  
} // WikiListRead()

/** ****************************************************************************
 * Create new wiki list
 */
function WikiListCreate(){
    $out = $this->getOutput();      // for debug messages
    $conf = $this->getConfig();     // object to get configuration variables from extension.json and LocalSettings.php
    $info_level = $conf->get( 'WikifarmInfoLevel' );
    $prefixDelimiter = '__';        // Used to  TO-DO: get it from config file; generally it depends on wiki => should not be a constant
    require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions and classes
    require_once( __DIR__ ."/WikiFarm_config.php" ); 	//include common functions

    $this->addDB();
    foreach ( $wfarmDBConnection as $i => $db){  // get from WikiFarm_config.php
        $this->dbs[] = $wfarmDBConnection[$i];
    }
    //$out->addWikiText( 'DB COUNTER:'. count($this->dbs) );    // debug
    //print_r($wikiAdmin);      // debug
    
    // get from WikiFarm_config.php
    $this->admin_wiki = (is_array($wikiAdmin)) ? $wikiAdmin : new wfarmWikiInstance( array('name' => '-',) ); 
    $this->wikis = $wikis;          
    
    if ($info_level > 1) {
        $str = "{| class=\"wikitable sortable\"\n" . 
                "|+ Wiki list in configuration file\n" .
                "! URL || DB name || Table prefix\n ";    
        foreach ($this->wikis as $wiki) {
            if ( $wiki->db_id < 1){
                $str_db_namewiki = '---';
            }else{
                $wiki->db = $this->dbs[$wiki->db_id];
                $str_db_namewiki = $wiki->db['name'];
            }
            $str .= "|-\n| ". $wiki->url ." || ". $str_db_namewiki ." || ". $wiki->prefix ."\n";
        }
        $str .= "|}";
        $out->addWikiText($str);
    }
    //$out->addWikiText( 'WIKI COUNTER:'. count($this->wikis) );    // debug

    // check if there are wikis with '[all]' parameter. Those records we need to unfold to get all wikis
    foreach ($this->wikis as $wiki_index => $wiki) {  // go through wiki list and see if there are items with several wikis (prefix = [all]) 
        $wiki->name = substr($wiki->prefix, 0, mb_strlen($wiki->prefix) - mb_strlen($prefixDelimiter));    // keep this for those where prefix != '[all]'
        if ($wiki->prefix == '[all]') {	  //scan db for all wiki prefixes
            $link = mysqli_connect($wiki->db['server'], $wiki->db['user'], $wiki->db['password'], $wiki->db['name']);
            if (mysqli_connect_errno()) {   // test DB connection 
                printf("Failed to connect to MySQL server: %s\n", mysqli_connect_error());
                exit();
            }            
            if ($result = mysqli_query($link, "SHOW TABLES LIKE '%text'")) {    // get all table names with substring "text"
                //$i = 0;   // debug
                $wiki_counter = 0;
                $new_wiki = array();
                $str = "{| class=\"wikitable sortable\"\n" .
                        "|+ Search in DB <br>" . $wiki->db['name'] . "<br> at server <br>" . $wiki->db['server'] . "\n" . 
                        "! № || Name || API \n";        
                while ( $row = mysqli_fetch_assoc($result) ) {    // Looping through the resultset.
                    //print_r($row, true);     //debug: show entire result as array structure
                    $table_name = array_values($row);   
                    //$out->addWikiText( "row $i, table: ". $table_name[0] .", prefix: <b>". $t_prefix ."</b>");       // debug
                    //$i++; // debug
                    $new_wiki['db_id'] = $wiki->db_id;
                    $new_wiki['db'] = $wiki->db;
                    $end = strlen($table_name[0]) - 4;                                  // 4 = letters in word "text"
                    $new_wiki['prefix'] = substr($table_name[0], 0, $end);              // table name - "text"
                    $end = strlen($new_wiki['prefix']) - mb_strlen($prefixDelimiter);
                    $new_wiki['name'] = substr($new_wiki['prefix'], 0, $end);           // prefix - delimiter
                    $new_wiki['url'] = $wiki->url . "/" . $new_wiki['name'];
                    $new_wiki['api'] = $wiki->api . "/w-" . $new_wiki['name'] . "/api.php";

                    $this->wikis[] = new wfarmWikiInstance( $new_wiki );
                    $wiki_counter++;
                    $str .= "|-\n| ". $wiki_counter ." || ". $new_wiki['name'] ." || ". $new_wiki['api'] ."\n";
                }
                mysqli_free_result($result);
                if ($info_level == 2) {   //show verbous wikis info found in this DB
                    //$out->addWikiText($str . "|}");   // debug
                }
                if ($info_level == 1) {
                    $out->addWikiText("\n* Found: '''$wiki_counter''' wikis");
                }                
            }                       
        }
    }   // [all] parameter handled
    
    // Clean wikis array: remove processed wikis with [all] prefix
    foreach ($this->wikis as $i => $wiki) {
        if ($wiki->prefix == '[all]') { 
            unset($this->wikis[$i]);
        } 
    }
} // WikiListCreate()

/** ****************************************************************************
 * Update wiki list using API requests
 */
function WikiListUpdate(){
    require_once( __DIR__ ."/botclasses.php" );         //include bot functions
    require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions and classes
    $this->has_info = false;                            // no info to show yet
    //$out = $this->getOutput();      
    //$conf = $this->getConfig();     
    
/*       
    // get interwiki list
    $bot = new extended($wikiAdmin->api);
    if (!empty($wikiAdmin->user)) {
            $bot->login($wikiAdmin->user, $wikiAdmin->pass);
    }
    $interwikiList = $bot->interwikilist();
    $interwikiCounter = count($interwikiList);
    //print_r($interwikiList);      // debug
*/
    foreach ($this->wikis as $wiki) {
            $wiki->info = '';               // clean info before update
            $bot = new extended($wiki->api);
            if (!empty($wiki->user)) {      // use bot user instead of anonymous 
                    $bot->login($wiki->user, $wiki->pass);
            }

            // test if API is accessable 
            $wikiApiLastStatus = $wiki->api_status;
            $wikiApiLastStatusDate = $wiki->api_status_date;  
            $err = $bot->testapi();
            $wiki->api_status_date = date('Y-m-d H:i:s');

            if ( !empty($err['code']) ) { // there is an error
                //echo "\n inside err[code] ";
                $wiki->api_status = false;
                $wiki->api_status_info = 'API access error! CODE: '. $err['code'] .'; INFO: '. $err['info'];
            } else {  //get sitename
                $wiki->api_status = true;
                $wikiInfo = $bot->siteinfo();
                if ( array_key_exists('sitename', $wikiInfo) ) {  // if request is allowed, wiki is not closed
                    $wiki->info .= $wikiInfo['generator'] . '<br>' .
                        'PHP: '. $wikiInfo['phpversion'] .' ('. $wikiInfo['phpsapi'] .')<br>';
                    
                    // article url    
                    $end = strlen($wikiInfo['articlepath']) - 2;    // 2 = '$1'
                    $path = substr($wikiInfo['articlepath'], 0, $end);
                    $wiki->url = $wikiInfo['server'] . $path;
                    
                    if ($wiki->site_name != $wikiInfo['sitename']){
                        $wiki->info .=  '• <b>site name</b> <br>' .
                                        'old: '. $wiki->site_name . '<br>' .
                                        'new: '. $wikiInfo['sitename'];
                    }
                    $wiki->site_name = $wikiInfo['sitename'];
                }
            }
            
            // compare statuses after update
            if ( $wikiApiLastStatus != $wiki->api_status ) {
                $wiki->info .=  '<div>• <b>API accessability has changed</b><br>' .
                                'old: <b>'. bool2str($wikiApiLastStatus) .'</b> '. $wikiApiLastStatusDate .'<br>' .
                                'new: <b>'. bool2str($wiki->api_status)  .'</b> '. $wiki->api_status_date .
                                '</div>';
            }
            
            /*
            // get interwiki link from admin wiki if exist
            foreach ($interwikiList as $iwiki) {
                    $pos = strpos($iwiki['url'], $wiki->url);
                    if ($pos !== false) {
                            $tmp = $iwiki['prefix'];
                            $wiki->interwikiLink = $iwiki['prefix'];
                            break;
                    }
            }
            */
            //$out->addWikiText("* wiki name: ". $wiki->name ."; interwiki: " . $wiki->interwikiLink);
    }
    // check if there is an information to show
    foreach ($this->wikis as $wiki) {
        if ( !empty($wiki->info) ){
            $this->has_info = true;
            break;
        }
    }
} // WikiListUpdate()

/** ****************************************************************************
 * Create main page. Output list of wikis.
 */
function ShowPageMain(){
    require_once( __DIR__ ."/botclasses.php" );         //include bot functions
    require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions and classes

    $out = $this->getOutput();
    $conf = $this->getConfig();     // object to get configuration variables from extension.json and LocalSettings.php
    $infoLevel = $conf->get( 'WikifarmInfoLevel' );
    
    // List of all wikis
    $str = "<div align=center>" .
            "\n{| class=\"wikitable sortable\"" .
            "\n|+ List of wikis (<b>" . count($this->wikis) . "</b>)" . 
            "\n! № || Name || <!-- API status --> || API";
    if ( $infoLevel > 2 ) { $str .= "\n! Table prefix"; }
    if ( $this->has_info ) { $str .= "\n! Info"; }    

    $i = 1;
    foreach ($this->wikis as $wiki) {   // create table row for one wiki
        $strSiteName = $this->WikiName($wiki);                                // Determine site name

        // API status cell. Test if API is accessable         
        if ( $wiki->api_status ) { // there is an error
            $strApiStatus = 'style="text-align: center; background-color: #00fa9a; font-weight: bold; color: green;" | √ ';
        } else {
            $strApiStatus = 'style="text-align: center; background-color: #FF6666; font-weight: bold; color: maroon;" | ' .
                        '<span title="'. $wiki->api_status_info .'"> × </span>';
        }
        
        // API URL and user
        if ( empty($wiki->user) ) {
            $strTitle = 'without login';
            $strUser  = wfMessage( 'nstab-user' )->text() .': --';
        } else {
            $$strTitle = '';
            $strUser = wfMessage( 'nstab-user' )->text() .': '. $wiki->user;
        }        
        $strApi = $wiki->api . '<br><span style="color: grey; font-size: 80%;">'. wfarmHint($strUser, $strTitle) .'</span>';

        // info text block
        $strInfo = '';            
        if (!empty($wiki->info)){
            if ( $conf->get( 'WikifarmCollapseInfo' ) ) {
                $collapseMode = ' mw-collapsed';
            } else { $collapseMode =''; }            
            $strInfo .='<div class="mw-collapsible'. $collapseMode .'">'. wfMessage( 'wikifarm_changes' )->text() .
                        '<div class="mw-collapsible-content">'. $wiki->info .
                        '</div></div>';
        }
        $strInfo .= "\n";
        
        // create row
        $str .= "\n|-";
        $str .= "\n| ". $i;
        $str .= "\n| ". $strSiteName;
        $str .= "\n| ". $strApiStatus;
        $str .= "\n| ". $strApi;
        if ( $infoLevel > 2 ) { $str .= "\n| ". $wiki->prefix; }
        if ( $this->has_info ) { $str .= "\n| ". $strInfo; }
        $i++;
    }
    $str .= "\n|} \n</div>";
    $out->addWikiText($str);
    //$out->addWikiText('<pre>'. $str .'</pre>');   // debug    
    
    if ($infoLevel > 1){
        $this->ShowWikiListJSON();
    }
} // ShowMainPage()

/** ****************************************************************************
 * Create Statistics page.
 */
function ShowPageStatistics() {
    require_once( __DIR__ ."/botclasses.php" );         //include bot functions
    require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions and classes
    $out = $this->getOutput();
    $wikisAccessible = 0;
    $skipped = '';
    $wikisTotal = count($this->wikis);

    //------- Get info about every wiki -------
    foreach( $this->wikis as $wiki ){
        if ( $wiki->api_status ) { // process only wikis with accessible API
            $bot = new extended( $wiki->api );
            if ( !empty($wiki->user) ) {
                    $bot->login( $wiki->user, $wiki->pass );
            }
            $wiki->data_statistics['update_time'] = date('Y-m-d H:i:s');

            $wikiInfo = $bot->sitestatistics();
            if ( ! is_array($wikiInfo) ) {          // could not connect to API
                $skipped .= $wiki->name . ', ';        
            } else {                                // connection is OK
                $wikisAccessible++;
                $wiki->data_statistics['pages']     = array_key_exists('pages', $wikiInfo) ? $wikiInfo['pages'] : '';
                $wiki->data_statistics['articles']  = array_key_exists('articles', $wikiInfo) ? $wikiInfo['articles'] : '';
                $wiki->data_statistics['edits']     = array_key_exists('edits', $wikiInfo) ? $wikiInfo['edits'] : '';
                $wiki->data_statistics['images']    = array_key_exists('images', $wikiInfo) ? $wikiInfo['images'] : '';
                $wiki->data_statistics['users']     = array_key_exists('users', $wikiInfo) ? $wikiInfo['users'] : '';

                $wikiInfo = $bot->allcategories();
                $wiki->data_statistics['categories']= count($wikiInfo);

                $wikiInfo = $bot->alltemplates();
                $wiki->data_statistics['templates'] = count($wikiInfo);
            }
        }
    }

    //------- All data is collected. Show it. -------
    if ($wikisTotal > $wikisAccessible) { 
        $strCounter = '('. wfarmHint( $wikisAccessible .' / '. $wikisTotal, 'Could not connect to some APIs') .')';
    } else {
        $strCounter = '('. $wikisAccessible .')';
    }
    $tableHeader = "\n<div align=center>".
        "\n{| class=\"wikitable sortable\" " .
        "\n|+ <big>". wfMessage( 'wikifarm_wiki_list' )->text() .'</big> ' . $strCounter .
        "\n|-".
        "\n! ". wfMessage( 'wikifarm_wiki_name' )->text() .
        "\n! ". wfMessage( 'wikifarm_pages' )->text() .
        "\n! ". wfMessage( 'wikifarm_articles' )->text() .
        "\n! ". wfMessage( 'wikifarm_edits' )->text() .
        "\n! ". wfMessage( 'prefs-files' )->text() .
        "\n! ". wfMessage( 'group-user' )->text() .
        "\n! ". wfMessage( 'wikifarm_categories' )->text() .
        "\n! ". wfMessage( 'wikifarm_templates' )->text();
    
    $newRecord    = "\n|- valign=top align=right";
    $tableRecords = '';
    $tableFooter  = "\n|} \n</div> \n";

    // create rows
    $fields = array();	//table fields
    foreach( $this->wikis as $wiki ){
        if ( $wiki->api_status ) { // process only wikis with accessible API
            $fields['wikiName']     = "\n| align=left| ". $this->WikiName($wiki);
            $fields['pages']        = "\n| ". $wiki->data_statistics['pages'];
            $fields['articles']     = "\n| ". $wiki->link( 'Special:AllPages?hideredirects=1', $wiki->data_statistics['articles']);
            $fields['edits']        = "\n| ". $wiki->data_statistics['edits'];
            $fields['images']       = "\n| ". $wiki->link( "Special:FileList", $wiki->data_statistics['images'] );
            $fields['users']        = "\n| ". $wiki->link( "Special:ListUsers", $wiki->data_statistics['users'] );
            $fields['categories']   = "\n| ". $wiki->link( "Special:Categories", $wiki->data_statistics['categories'] );
            $fields['templates']    = "\n| ". $wiki->link( 'Special:AllPages?namespace=10&hideredirects=1', $wiki->data_statistics['templates']);

            $tableRecords .= $newRecord . $fields['wikiName'] . $fields['pages'] . $fields['articles'] .
                            $fields['edits'] . $fields['images'] . $fields['users'] . $fields['categories'] . $fields['templates'];
        }
    }
    $out->addWikiText( $tableHeader . $tableRecords . $tableFooter );  
    if ( !empty($skipped) ) { 
        $skipped = substr($skipped, 0, strlen($skipped) -2 );         // remove ', ' from tail
        $out->addWikiText( 'Could not connect to: '. $skipped );    
    }
} // ShowStatisticsPage()

/** ****************************************************************************
 * Create admin links for every wiki. Output table with links.
 */
function ShowPageLinks(){
    $out = $this->getOutput();
    //require_once( __DIR__ ."/botclasses.php" ); //include bot functions
    $wikiCounter = count($this->wikis);

    $tableHeader =  "\n{| class=\"wikitable sortable\" width=100%" .
                    "\n|+ <big>". wfMessage( 'wikifarm_wiki_list' )->text() ."</big> (". $wikiCounter .")" .
                    "\n|- ".
                    "\n! ". wfMessage( 'wikifarm_wiki_name' )->text() .
                    "\n! ". wfMessage( 'wikifarm_links' )->text() .
                    "\n";
    $newRecord    = "\n|- valign=top";
    $tableRecords = '';
    $tableFooter  = "\n|}\n";
    $fields = array();	//table fields
    foreach( $this->wikis as $wiki ){
        $str_Interwiki= "[[:$wiki->interwiki_link:|". wfMessage( 'wikifarm_interwiki' )->text() ."]] <nowiki>|</nowiki> ";
        if ( empty($wiki->interwikiLink) ){
            $str_Interwiki = "<span style=\"color: grey;\">" . 
                wfarmHintHidden( wfMessage( 'wikifarm_interwiki' )->text(), wfMessage( 'wikifarm_interwiki_link_absent' )->text() ) .
                "</span> <nowiki>|</nowiki> ";
        }
        $fields['wikiName'] = "\n| ". $this->WikiName($wiki); //$wiki->name";
        $fields['links']    = "\n| <small> ".
            "\n* <b>". wfMessage('wikifarm_wiki')->text() .":</b> ".
                $wiki->link( ":Special:Statistics", wfMessage('wikifarm_statistics')->text() ) ." <nowiki>|</nowiki> ".
                $str_Interwiki .
                $wiki->url .
            "\n* <b>". wfMessage('wikifarm_engine')->text() .":</b> ".
                wfarmHintHidden( $wiki->link( ":Special:Version", wfMessage('version-software-version')->text()), wfMessage('version')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:Log", wfMessage('log')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:SpecialPages", wfMessage('specialpages')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:AllMessages", wfMessage('allmessages')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:MediaWiki:Common.css", "CSS" ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:MediaWiki:Common.jss", "JS" ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:MediaWiki:Mainpage", wfMessage('mainpage')->text() ) .
            "\n* <b>". wfMessage('toc')->text() .":</b> ".
                $wiki->link( ":Special:Allpages", wfMessage('wikifarm_pages')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:Categories", wfMessage('categories')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:RecentChanges", wfMessage('prefs-files')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:Sidebar", wfMessage('wikifarm_sidebar')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:Search", wfMessage('search')->text() ) .
            "\n* <b>". wfMessage('group-user')->text() .":</b> ".
                $wiki->link( ":Special:UserLogin", wfMessage('login')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:ListUsers", wfMessage('group-user')->text() ) ." <nowiki>|</nowiki> ".
                $wiki->link( ":Special:UserRights", wfMessage('listgrouprights-rights')->text() ) .
            "</small>\n";

    $tableRecords .= $newRecord . $fields['wikiName'] . $fields['links'];
    }
    $out->addWikiText( $tableHeader . $tableRecords . $tableFooter );
    
} // ShowLinksPage()

/** ****************************************************************************
 * Create Changes page.
 */
//TO-DO: Option to join result for all wikis to trace user activity through all wikis and so on
function ShowPageChanges() {
    require_once( __DIR__ ."/botclasses.php" );         //include bot functions
    require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions and classes
    $out = $this->getOutput();
    $out->addWikiText("\n__TOC__\n");       // TO-DO: Does not work (not in source html). Check CSS rules for special pages
    //$str = "{| align=right \n|- \n| __TOC__ \n|}";    // right align 
    //$out->addWikiText($str ."\n<pre>$str</pre>");

    //------- Get recent changes from every wiki -------
    $tables = '';	//all tables. One table - one wiki changes
    $tableHeader = "\n{| class=\"wikitable sortable\" align=center\n" .
                    "\n! " . wfMessage('filehist-datetime')->text() .
                    "\n! " .
                    "\n! " . wfMessage('article')->text() .
                    "\n! " . wfMessage('listfiles_size')->text() .
                    "\n! " .
                    "\n! " . wfMessage('nstab-user')->text() .
                    "\n! " . wfMessage('summary')->text() .
                    "\n";
    $tableFooter = "\n|}\n";
    $wikiUnchanged = '';
	$wikiModified = '';
    $wikiUnchangedCounter = 0;
	$wikiModifiedCounter = 0;
	//print_r($this->wikis);
	//exit();
    foreach ($this->wikis as $wiki) {
        if ( $wiki->api_status ) { // process only wikis with accessible API
            $bot = new extended($wiki->api);
            if (!empty($wiki->user)) {
                    $bot->login($wiki->user, $wiki->pass);
            }
            //$user = $this->getUser(); //  user for option "hide my edit"
            $wikiLink = $wiki->link('Special:RecentChanges', $wiki->name);
            //$out->addWikiText('Request: '. $this->getRequest(). '<br>');  // debug
            //echo "DUMP request from ShowPageChanges() ---------\n";
            //var_dump($this->getRequest());
            
            $wikiChanges = $bot->recentchanges($this->getRequest(), $this->getUser());
            $wikiChangesCounter = count($wikiChanges);
            if ( $wikiChangesCounter > 0) { // there are some changes -> show them
                $fields = array();	//table fields
                $tableRows = '';
                $out->addWikiText("=== $wikiLink ($wikiChangesCounter) ===\n");
                foreach ($wikiChanges as $page) {
                    $fields['type'] = "\n| ";                    
                    if ( array_key_exists('new', $page) ){
                        $fields['type'] .= "<b>" . wfarmHint(wfMessage('newpageletter')->text(), wfMessage('recentchanges-label-newpage')->text()) . "</b> ";
                    }
                    if ( array_key_exists('minor', $page) ){
                        $fields['type'] .= "<b>" . wfarmHint(wfMessage('minoreditletter')->text(), wfMessage('recentchanges-label-minor')->text()) . "</b> ";
                    }
                    if ( array_key_exists('bot', $page) ){
                        $fields['type'] .= "<b>" . wfarmHint(wfMessage('boteditletter')->text(), wfMessage('recentchanges-label-bot')->text()) . "</b> ";
                    }

                    $dif = $page['newlen'] - $page['oldlen'];
                    $difColor = "green";
                    if ($dif < 0) {
                            $difColor = 'red';
                    }
                    $date_time = wfarmFormatDate($page['timestamp']);
                    $fields['time'] = "\n| " . $date_time;
					//$fields['page'] = "\n| " . $wiki->link($page['title'], $page['title']);
					//Формируем ссылку на страницу, удаляя пробелы в названии.
					$pageUrl = str_replace(' ', '_', $page['title']);
                    $fields['page'] = "\n| " . $wiki->link($pageUrl, $page['title']);
                    $fields['size'] = "\n| <div align=right>" . $page['newlen'] . "</div>";
                    $fields['diff'] = "\n| <div align=right><span style=\"color: $difColor;\">$dif</span></div>";
                    //$fields['user'] = "\n| " . $wiki->link('User:'. $page['user'], $page['user']);
					//Формируем ссылку на страницу, удаляя пробелы в названии.
					$userUrl = str_replace(' ', '_', $page['user']);
					$fields['user'] = "\n| " . $wiki->link('User:'. $userUrl, $page['user']);
                    $fields['comment'] = "\n| <small>''<nowiki>" . $page['comment'] . "</nowiki>''</small>";
                    $tableRows .= "\n|- valign=top" . $fields['time'] . $fields['type'] . $fields['page'] . $fields['size'] . $fields['diff'] . $fields['user'] . $fields['comment'] . "\n";
                }
				//print_r($wikiChanges);
				//exit();
                $out->addWikiText($tableHeader . $tableRows . $tableFooter);
				$ID = str_replace(' ', '_', $wiki->name . ' (' . $wikiChangesCounter . ')');
				$wikiModified .= "\n* " . $wikiLink . " <span id='scrollModified'>([[#" . $ID . "|". $wikiChangesCounter . "]])</span>";
				//$wikiModified .= "\n* " . $wikiLink . " <span id='scrollModified' data-id='" . $pageUrl . "'>(" . $wiki->link('[[#' . $pageUrl . ']]', $wikiChangesCounter) . ")</span>";
				//$wikiModified .= "\n* " . $wikiLink . " <span id='scrollModified' data-id='" . $pageUrl . "'>(" . $wiki->link($pageUrl, $wikiChangesCounter) . ")</span>";
				// " (<a id='scrollModified' data-id='" . $pageUrl . "'>" . $wikiChangesCounter . "</a>)";
				$wikiModifiedCounter++;
            } else {
                $wikiUnchanged .= "\n* ". $wikiLink;
                $wikiUnchangedCounter++;
            }
        }
    }

    //$str_summary = wfarmSummary($this->wikis, 'changes', '/Special:Recentchanges');
    //$out->addWikiText($str_summary);   // show summary table
    //$out->addWikiText($tables); // show requested data (recent changes)
	if ( !empty($wikiModified) ) {
        $out->addWikiText("\n== wfMessage('wikifarm_wikis_modified')->text() ($wikiModifiedCounter)==\n". $wikiModified);
    }
	
    if ( !empty($wikiUnchanged) ) {
        $out->addWikiText("\n== wfMessage('wikifarm_wikis_not_modified')->text() ($wikiUnchangedCounter)==\n". $wikiUnchanged);
    }
	
	//Подключаем js
	//$script = '<script type="text/javascript" src="/extensions/WikiFarm/includes/js.js"></script>';
	//$out->addHeadItem("itemName", $script);
}

/**
 * Creates a header for the WikiFarm modules.
 * Header consists of links to active modules.
 *
 * @param string $module WikiFarm module name to highlight. Optional, default = 'main'.
 *		Possible values: main, statistics, links, changes, pages, users, search, log
 */
function ShowHeader($module = 'main') {
    $out = $this->getOutput();
    $conf = $this->getConfig();
    $fields = array();                          // columns (fields) in header
    $delimiter = " '''<nowiki>|</nowiki>''' ";  // delimiter for menu items

    // date and time
    $fields['time'] = "\n| style=\"background: #e6e6ff; width: 100px\" | <center><small>". date('Y.m.d') ."</small> '''". date('H:i:s') ."'''</center>";
    
    // title: extension name and version, wiki list mode and info level indicators
    $str_version = wfarmHint('v.'. $conf->get( "WikifarmVersion" ), 
                            'Version: '. $conf->get( "WikifarmVersion" ) .' Release date: '. $conf->get( "WikifarmBuild" ));
    //$str_version = '<span title="Version: '. $conf->get( "WikifarmVersion" ) .' Release date: '. $conf->get( "WikifarmBuild" ) .'">v.'. $conf->get( "WikifarmVersion" ) .'</span>';
    if ( $conf->get( 'WikifarmWikiListMode' ) == "memory" ){
        $strDbMode = ' • '. wfarmHint('m', 'Mode: memory. Scan all DBs and make a brand new wiki list each time');
        //$str_db_mode = '<span title="Mode: memory. Scan all DBs and make a brand new wiki list each time"> • m </span>';
    }elseif ($conf->get( 'WikifarmWikiListMode' ) == "file") {
        $strDbMode = ' • '. wfarmHint('f', 'Mode: file. Read wikis from file');
        //$str_db_mode = '<span title="Mode: file. Read wikis from file"> • f </span>';    
    }
    $strInfoLevel = ' • '. wfarmHint($conf->get( "WikifarmInfoLevel" ),
                                'Info level = '. $conf->get( "WikifarmInfoLevel" ));
    //$str_info_level = '<span title="Info level = '. $conf->get( "WikifarmInfoLevel" ) .'"> • '. $conf->get( "WikifarmInfoLevel" ) .'</span>';
    $fields['title']= "\n! style=\"background: #e6e6ff; width: 20px\" | [[Special:WikiFarm|" . 
                wfMessage( 'wikifarm_name' )->escaped() . ']]' .
                '<div style="font-size: 80%; text-align: left;">' . 
                    $str_version . $strDbMode . $strInfoLevel .
                '</div>';
    
    // main menu: links to modules
    $update_button= "<div style=\"float: right; font-size: 80%; border: 1px solid grey; margin-top: 0;\">" . 
                        "[[Special:WikiFarm/Update|" . wfMessage( 'wikifarm_update' )->escaped() . "]]" .
                    "</div>";
    $fields['links']= "\n|". $update_button ."<center>";

    // links in navigation bar for modules
    $links['main']      = "[[Special:WikiFarm|" . wfMessage( 'wikifarm_main' )->escaped() . "]]";
    $links['statistics']= "[[Special:WikiFarm/Statistics|" . wfMessage( 'wikifarm_statistics' )->escaped() . "]]";
    $links['links']	= "[[Special:WikiFarm/Links|" . wfMessage( 'wikifarm_links' )->escaped() . "]]";
    $links['changes']	= "[[Special:WikiFarm/Changes|" . wfMessage( 'wikifarm_changes' )->escaped() . "]]";
    $links['pages']	= "[[Special:WikiFarm/Pages|" . wfMessage( 'wikifarm_pages' )->escaped() . "]]";
    $links['users']	= "[[Special:WikiFarm/Users|" . wfMessage( 'wikifarm_users' )->escaped() . "]]";
    $links['search']	= "[[Special:WikiFarm/Search|" . wfMessage( 'wikifarm_search' )->escaped() . "]]";
    $links['log']	= "[[Special:WikiFarm/Log|" . wfMessage( 'wikifarm_log' )->escaped() . "]]";
    
    // highlight current module
    $links[$module]	= "<big>'''". $links[$module] ."'''</big>";
    
    // navigation bar; check if the module is switched on
    $fields['links'] .= $links['main'];
    if ( $conf->get( "WikifarmModuleStatistics" ) )   {$fields['links'] .= $delimiter . $links['statistics'];}
    if ( $conf->get( "WikifarmModuleLinks" ) )        {$fields['links'] .= $delimiter . $links['links'];}
    if ( $conf->get( "WikifarmModuleChanges" ) )      {$fields['links'] .= $delimiter . $links['changes'];}
    //if ( $conf->get( "WikifarmModulePages" ) )        {$fields['links'] .= $delimiter . $links['pages'];}
    if ( $conf->get( "WikifarmModuleUsers" ) )        {$fields['links'] .= $delimiter . $links['users'];}
    //if ( $conf->get( "WikifarmModuleSearch" ) )       {$fields['links'] .= $delimiter . $links['search'];}
    //if ( $conf->get( "WikifarmModuleLog" ) )          {$fields['links'] .= $delimiter . $links['log'];}
    $fields['links'] .= $delimiter ."</center>";

    $tableHeader = "{| style=\"border: 1px dotted #000000;\" width=100%";
    $tableFooter = "\n|}";
    $tableRow = $fields['title'] . $fields['links'] . $fields['time'];
    
    $out->addWikiText( $tableHeader . $tableRow . $tableFooter );    
} // ShowHeader()

/**
 * Creates a footer for the WikiFarm modules.
 * Footer consists of links for Special page in admin wiki.
 */
function ShowFooter() {
    $out = $this->getOutput();  
    $delimiter = " '''<nowiki>|</nowiki>''' ";
    $time = round( microtime(true) - $this->time_start, 2 );
    $fields = array();  // fields (columns) of the table
    $fields['title']= "\n! style=\"background: #e6e6ff; \" | [[Special:SpecialPages|" . wfMessage( 'wikifarm_admin_special_pages' )->escaped() . "]]";
    $fields['time'] = "\n|" . 'style="background: #e6e6ff; width: 100px; text-align: center;" |'. wfarmHint($time .' sec.', 'Seconds spent to create this page');

    $links['version']   = $delimiter . "[[Special:Version|" . wfMessage( 'wikifarm_version' )->escaped() . "]]";
    $links['pages']     = $delimiter . "[[Special:AllPages|" . wfMessage( 'wikifarm_pages' )->escaped() . "]]";
    $links['files']     = $delimiter . "[[Special:ListFiles|" . wfMessage( 'prefs-files' )->escaped() . "]]";
    $links['users']     = $delimiter . "[[Special:ListUsers|" . wfMessage( 'wikifarm_users' )->escaped() . "]]";
    $links['rights']    = $delimiter . "[[Special:UserRights|" . wfMessage( 'wikifarm_user_rights' )->escaped() . "]]";
    $links['interwiki'] = $delimiter . "[[Special:Interwiki|" . wfMessage( 'wikifarm_interwiki' )->escaped() . "]]";

    $fields['links'] = "\n|<center>";
    $fields['links'] .= $links['version'] . $links['pages'] . $links['files'] .	$links['users'] . $links['rights'] . $links['interwiki'];
    $fields['links'] .= $delimiter ."</center>";

    $tableHeader = "{| style=\"border: 1px dotted #000000;\" width=100%";
    $tableFooter = "\n|}";
    $tableRow = $fields['title'] . $fields['links'] . $fields['time'];

    $out->addWikiText( $tableHeader . $tableRow . $tableFooter );    
} // ShowFooter()


/** ****************************************************************************
 * Create Changes page.
 */
//TO-DO: Option to join result for all wikis to trace user activity through all wikis and so on
function ShowPageUsers() {
    require_once( __DIR__ ."/botclasses.php" );         //include bot functions
    require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions and classes
    $out = $this->getOutput();
    $bot = new extended( $this->admin_wiki->api );
    if ( !empty($wikiAdmin->user) ) {
        $bot->login( $this->admin_wiki->user, $this->admin_wiki->pass );
    }
    $users = $bot->allusers();
    $userCounter = count($users);
    $tableHeader = "<div align=center>" .
                    "\n{|class=\"wikitable sortable\"".
                    "\n|+ <big>". wfMessage( 'listusers' )->text() . "</big> ($userCounter)" .
                    "\n|- ".
                    "\n! ". wfMessage( 'wikifarm_users' )->text() .
                    "\n! ". wfMessage( 'wikifarm_info' )->text() .
                    "\n! ". wfMessage( 'actions' )->text() .
                    "\n! ". wfMessage( 'anoncontribs' )->text() .
                    "\n! ". wfMessage( 'prefs-registration' )->text() .
                    "\n! ". wfMessage( 'userrights-groupsmember' )->text() .
                    "\n\n";
    $newRow    = "\n|- valign=top";
    $tableRows = '';
    $tableFooter  = "\n|} \n</div> \n";
    $fields = array();	//table fields
    foreach( $users as $key => $user ){
        $fields['user'] = "\n| ". $user['name'];
        $fields['service'] = "\n| <small>[[User:". $user['name'] ."|". wfMessage( 'mypage' )->text() ."]]".
                " <nowiki>|</nowiki> [[User_talk:". $user['name'] ."|". wfMessage( 'mytalk' )->text() ."]]".
                " <nowiki>|</nowiki> [[Special:UserRights/".$user['name'] ."|". wfMessage( 'listgrouprights-rights' )->text() ."]]".
                "</small>";
        $fields['contrib'] = "\n| <div align=right>[[Special:Contributions/". $user['name'] ."|". $user['editcount'] ."]]</div>";
        $fields['registration'] = "\n| <div align=center>". wfarmFormatDate($user['registration']) ."</div>";
        $fields['block'] = "\n| [[Special:Block/". $user['name'] ."|". wfMessage( 'blocklink' )->text() ."]]";
        if ( !empty($user['blockedby']) ){
                $fields['block'] = "\n| style=\"background: #EEAAAA;\"| [[Special:Unblock/". $user['name'] ."|". wfMessage( 'unblocklink' )->text() ."]]";
        }
        $fields['groups'] = "\n| ";
        foreach( $user['groups'] as $gr ){
                if ( ! ( ($gr == '*') or ($gr == 'user') or ($gr == 'autoconfirmed') ) ){	//hide default groups
                        $fields['groups'] .= $gr .", ";
                }
        }
        $tableRows .= $newRow . $fields['user'] . $fields['service'] . $fields['block'] . $fields['contrib'] . $fields['registration'] . $fields['groups'];
    }
    $out->addWikiText( $tableHeader . $tableRows . $tableFooter );    
} // ShowPageUsers()

/** ****************************************************************************
 * Create JSON text for wiki list. Output this text for adimin to copy to wikis.json.
 */
function ShowWikiListJSON(){
    $conf = $this->getConfig();
    $out = $this->getOutput();  
    //$out->addWikiText( '<b>Wiki list in JSON format. Check, modify and copy to wikis.json file.</b>' ); 
    $str  = "<div class=\"mw-collapsible mw-collapsed\" style=\"width:100%\">Wiki list in JSON format. Check, modify and copy to <b>wikis.json</b> file.";
    $str .= "<div class=\"mw-collapsible-content\" style=\"height: 300px; overflow: scroll; border: 1px solid black;\">";
    
    // Prepear list of DB connections
    $dbs_array = $this->dbs;    
    foreach ( $this->dbs as $i => $db) {
        if ( empty($dbs_array[$i]['id']) ){ 
            $dbs_array[$i]['id'] = $i;            
        }
        if ( empty($dbs_array[$i]['notes']) ){
            $dbs_array[$i]['notes'] = 'DB from '. $dbs_array[$i]['server'];            
        }
    }
    
    // Prepear admin wiki
    if (!empty($this->admin_wiki)){
        $wiki_admin_array = $this->admin_wiki->toArray();
    }else{
        $tmp_arr = array('name' => '-',);
        $wiki_admin_array = new wfarmWikiInstance($tmp_arr);
    }
    //$wiki_admin_array = (!empty($this->admin_wiki)) ? $this->admin_wiki->toArray() : new $wfarmDBConnection( array('name' => '-',) );    
    
    // Prepear list of wikis to watch
    $wikis_array = array();
    foreach ($this->wikis as $i => $wiki) {
        //echo "=========== wiki->toArray() in ShowWikiListJSON() =================\n";
        //print_r($wiki);
        $wikis_array[$i] = $wiki->toArray();
    }
    
    // join configurations into one array and export to json
    $wiki_conf['databases'] =  $dbs_array;
    unset($wiki_conf['databases'][0]);  //  do not save in config empty DB connection
    unset($wiki_conf['databases'][1]);  //  do not save in config default DB connection
    $wiki_conf['admin wiki'] = $wiki_admin_array;
    $wiki_conf['wikis']     =  $wikis_array;
    $str .= json_encode($wiki_conf, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);    
    $str .= "</div></div><p></p>";
    // output configuration in json format to scrolling text box; 
    $out->addWikiText($str);    
    
    // TO-DO: make it in form in editable text box, so it can be highlighted with Ctrl + A and copied
    // Read https://www.mediawiki.org/wiki/HTMLForm
} // ShowWikiListJSON()


/** ****************************************************************************
 * Create wiki name with link and alternative name from configuration
 */
function WikiName($wiki){
    $str = $wiki->link('', $wiki->name);
    if ( !empty($wiki->site_name) ) {
        $str .= '<br><span style="color: grey; font-size: 90%;';
        if ( !empty($wiki->notes) ) {
            $str .= ' border-bottom:1px dotted gray; cursor: help;" title="'. $wiki->notes ;
        }
        $str .= '">'. $wiki->site_name .'</span>';
    }
    
    return $str;
}


/** ****************************************************************************
 * Add empty and default DB connections
 */
function addDB(){
    global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword; //TO-DO: use $conf->get()
    //$conf = $this->getConfig();     
    
    $this->dbs[0] = array(
        'id'   => '0',
        'notes'   => 'Null DB. For wikis without DB requests',
        'server'   => '',
        'name'     => '',
        'user'     => '',
        'password' => '',
    );
    $this->dbs[1] = array(
        'id'   => '1',
        'notes'   => 'Default DB. For wikis using DB from LocalSettings.php',
        'server'   => $wgDBserver,
        'name'     => $wgDBname,
        'user'     => $wgDBuser,
        'password' => $wgDBpassword,
    );
} // addDB()

} //class SpecialWikiFarm
