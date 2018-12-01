<?php
//use MediaWiki\MediaWikiServices;   // for configuration variables

class SpecialWikiFarm extends SpecialPage {
    public $wikis = array();
    public $admin_wiki = new wfarmWikiInstance();
    public $c_var = "costruct";
    //$config = $this->getConfig();  
    //$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wikifarm' );

function __construct() {
    parent::__construct( 'WikiFarm', 'editinterface' );	  // restrict to sysops group, since only they have 'editinterface' right
    
}

function execute( $par ) {  //$par contains subpage name = WikiFarm module name
    //if ( !empty($par) ){ $out->addWikiText( 'Current module: '. $par ); }     // debug, 
    
    // Restrict access to special page. 
    if ( !$this->userCanExecute( $this->getUser() ) ) {
            $this->displayRestrictionError();
            return;
    }
    $out = $this->getOutput();
    $this->setHeaders();
    $this->outputHeader();

    require_once( __DIR__ ."/WikiFarm_config.php" ); 	//read config
    require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions
    //Загрузить все переменные из конфига, кроме списка вики - его создать в wfarmDefineWikis -> wfarmCreateWikiList

    
    $config = $this->getConfig();       // this is a Config object
    $out->addWikiText( 'c_var 1 = '. $this->c_var );
    $out->addWikiText( wfarmHeader() );  // show WikiFarm header with loaded modules
    $wikis = $this->wfarmDefineWikis( );     //define wikis to work with
    $out->addWikiText( 'c_var 2 = '. $this->c_var );

    $module = strtolower($par);             // current module
    switch ($module) {
        case "links":
            $out->addWikiText( 'Links table. Message in case statement' );
            //require_once( __DIR__ ."/WikiFarmLinks.php" );
            $this->wfarmLinks($wikis);
            break;
        case "changes":
            $out->addWikiText( 'Changes' );
            break;
        case "statistics":
            $out->addWikiText( 'Statistics' );
            break;
        default: 
            // List of all wikis (sorted), admin wiki highlighted
            // make list out of $this->wikis class variable
            
            // List of all modules with description, active ones are highlighted
           /* $tableRows  = moduleInfo('statistics');
            $tableRows .= moduleInfo('links');
            $tableRows .= moduleInfo('changes');
            $tableRows .= moduleInfo('pages');
            $tableRows .= moduleInfo('users');
            $tableRows .= moduleInfo('search');
            $tableRows .= moduleInfo('log');
            $tableHeader =  "<div align=center>".
                            "\n{| class=wikitable" .
                            "\n|+ ";
                            "\n|-".
                            "\n! ". wfMessage( 'wikifarm_module' )->text() .
                            "\n! ". wfMessage( 'wikifarm_version' )->text().
                            "\n! ". wfMessage( 'wikifarm_changed' )->text().
                            "\n! ". wfMessage( 'wikifarm_noteskey' )->text();
            $tableFooter = "\n|}\n</div>";
            $table = $tableHeader . $tableRows . $tableFooter;
            $out->addWikiText( $table );*/
    }
    $out->addWikiText( wfarmFooter() ); // show WikiFarm footer
} // function execute( $par )

/** ****************************************************************************
 * Process all wikis in configuration. 
 * If there is a wiki with '[all]' parameter, then query database for all wikis in it and add to $wikis array
 *
 * @param array  $wikis 			wiki array
 * @param string $level_info 		how verbous info should be (0,1,2)
 * @param string $prefixDelimiter	what delimiters prefix from table name
 * @return array Array of wfarmWikiInstance objects
 *
 * @todo Make all variables global and do not pass to the function.
 */
//TO-DO: make MySQL table for wikis and update it on config file change or demand.
// 	For the last option: put links in wiki table on Statistics page.
//	Links point to php script which after update redirects back to Statistics.
//	URL: wikifarm_update.php?action=update_wikilist
//	other actions: update_wikicounter_pages, update_wikicounter_categories, update_wikicounter_interwiki, update_wikicounter_all
function wfarmDefineWikis() {
    $out = $this->getOutput();
    $config = $this->getConfig(); // this is a Config object
    $info_level = $config->get( 'WikifarmInfoLevel' );
    $this->c_var = 'changed in wfarmDefineWikis';
    $prefixDelimiter = '__';    // get it from config file or $wfarmPrefixDelimiter;
    //TO-DO: save wiki list to file (to DB in WikiFarm v.4) and check for option WikifarmUpdate. 
    //      If WikifarmUpdate = true then update list else use the saved one.
    
    //TO-DO ? Create a local config file instead of global
    //$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wikifarm' );
    //$out->addWikiText("* wikiAdmin: ". $wikiAdmin->url);
    //$wikis = $wfarmWikis;

    if ($info_level > 1) {
        $str = "{| class=\"wikitable sortable\"\n" . 
                "|+ Wiki list in configuration file\n" .
                "! URL || DB name || Table prefix\n ";    
        foreach ($this->wikis as $wiki) {
            $str .= "|-\n| " . $wiki->url . " || " . $wiki->db['name'] . " || " . $wiki->prefix . "\n";
        }
        $str .= "|}";
        $out->addWikiText($str);
    }
    //$out->addWikiText("\n\n\n" .       "<big>'''Check if there are wikis with '[all]' parameter:'''</big>");
    
    // check if there are wikis with '[all]' parameter. Those records we need to unfold to get all wikis
    foreach ($wikis as $wiki_index => $wiki) {  // go through wiki list and see if there are items with several wikis (prefix = [all]) 
        if ($wiki->prefix == '[all]') {	  //scan db for all wiki prefixes
            $link = mysqli_connect($wiki->db['server'], $wiki->db['user'], $wiki->db['password'], $wiki->db['name']);

            /* test connection */
            if (mysqli_connect_errno()) {
                printf("Failed to connect to MySQL server: %s\n", mysqli_connect_error());
                exit();
            }            
            if ($result = mysqli_query($link, "SHOW TABLES LIKE '%text'")) {    // get all table names with substring "text"
                $i = 0;
                $wiki_counter = 0;
                $str = "{| class=\"wikitable sortable\"\n" .
                        "|+ Search in DB <br>" . $wiki->db['name'] . "<br> at server <br>" . $wiki->db['server'] . "\n" . 
                        "! № || URL || Table prefix\n";        
                //$out->addWikiText("DB server: ". $wiki->db['server']);
                //$out->addWikiText("DB name: ". $wiki->db['name']);
                while ($row = mysqli_fetch_assoc($result)) {    // Looping through the resultset.
                    //print_r($row, true);     //debug: show entire result as array structure
                    $table_name = array_values($row);   
                    $prefix_end = strlen($table_name[0]) - 6; // 6 = 4 (letters in word "text") + 2 ("__")
                    $t_prefix = substr($table_name[0], 0, $prefix_end);
                    //$out->addWikiText( "row $i, table: ". $table_name[0] .", prefix: <b>". $t_prefix ."</b>");       // debug
                    $i++;
                    
                    $wiki_url = $wiki->url . "/" . $wiki->name;
                    $wiki_api = $wiki->api . "/w-" . $wiki->name . "/api.php";
                    $wikis[] = new wfarmWikiInstance(
                                                    $wiki_url,
                                                    $wiki_api,
                                                    $wiki->db,
                                                    $t_prefix,
                                                    $wiki->user,
                                                    $wiki->pass,
                                                    $wiki->common_name
                    );
                    $wiki_counter++;
                    $str .= "|-\n| ". $wiki_counter ." || ". $wiki_url ." || ". $t_prefix ."\n";

                }
                mysqli_free_result($result);
                if ($info_level == 2) {   //show verbous wikis info
                    $out->addWikiText($str . "|}");
                }
                if ($info_level == 1) {
                    $out->addWikiText("\n* Found: '''$wiki_counter''' wikis");
                }                
            }                       
        }
    }   // [all] parameter handled
    // Clean wikis array: remove processed wikis with [all] prefix
    // Set additional info for each wiki
    foreach ($wikis as $i => $wiki) {
        if ($wiki->prefix == '[all]') {
            unset($wikis[$i]);
        } else {
            // TODO: read wiki name from additional table. For now make it from table prefix.
            if (empty($wiki->name)){
                $wiki->name = substr($wiki->prefix, 0, mb_strlen($wiki->prefix) - mb_strlen($prefixDelimiter));
            }
            // TODO: move here interwiki lookup from WikiFarmStatistics
        }
        //$wiki_index++;
    }
    
    // Final list of all wikis
    $str = "{| class=\"wikitable sortable\"\n" .
            "|+ List of wikis (<b>" . count($wikis) . "</b>)\n" . 
            "! № || URL || wiki API || Table prefix\n";   
    $i = 1;
    foreach ($wikis as $wiki) {
            $str .= "|-\n| ". $i ." || ". $wiki->url ." || ". $wiki->api ." || ". $wiki->prefix ."\n";
            $i++;
    }
    $out->addWikiText($str . "|}");

    //-------------------------------------------------
    //------- Get wiki name and interwiki link for every wiki via bot -------
    //-------------------------------------------------
    //require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" ); //include bot functions
    require_once( __DIR__ ."/botclasses.php" ); //include bot functions
    //$out->addWikiText("* wikiAdmin2: ". $wikiAdmin->url);
    // get interwiki list
    $bot = new extended($wikiAdmin->api);
    if (!empty($wikiAdmin->user)) {
            $bot->login($wikiAdmin->user, $wikiAdmin->pass);
    }
    $interwikiList = $bot->interwikilist();
    $interwikiCounter = count($interwikiList);

    //print_r($interwikiList);

    foreach ($wikis as $wiki) {
            $bot = new extended($wiki->api);
            if (!empty($wiki->user)) {
                    $bot->login($wiki->user, $wiki->pass);
            }

            // get sitename
            $wikiInfo = $bot->siteinfo();
            if (!empty($wikiInfo['sitename'])) {
                    //substract common part (f.e. for farm) from wiki name
                    $pos = strpos($wikiInfo['sitename'], $wiki->common_name);
                    if ($pos !== false) {
                            $str = substr_replace($wikiInfo['sitename'], '', $pos, strlen($wiki->common_name));
                    } else {
                            $str = $wikiInfo['sitename'];
                    }
                    $wiki->name = $str;
            }

            // get interwiki link if exist
            foreach ($interwikiList as $iwiki) {
                    $pos = strpos($iwiki['url'], $wiki->url);
                    if ($pos !== false) {
                            $tmp = $iwiki['prefix'];
                            $wiki->interwikiLink = $iwiki['prefix'];
                            break;
                    }
            }
            //$out->addWikiText("* wiki name: ". $wiki->name ."; interwiki: " . $wiki->interwikiLink);
    }
    return $wikis;
} // wfarmDefineWikis()


/** ****************************************************************************
 * Create admin links for every wiki. Output table with links.
 */
function wfarmLinks($wikis){
    $out = $this->getOutput();
    $tableRecords = 'Test line';
    $out->addWikiText( $tableRecords );
    //require_once( __DIR__ ."/WikiFarm_config.php" ); 	//read config
    //require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions
    require_once( __DIR__ ."/botclasses.php" ); //include bot functions
    
    //$wikis = wfarmDefineWikis( $wikis, $wikiAdmin, $infoLevel, $wfarmPrefixDelimiter );	//define wikis to work with
    $wikiCounter = count($wikis);

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
    foreach( $wikis as $wiki ){
        //$str_Interwiki = wfarmWikiLink($wiki, '', wfMessage( 'wikifarm_interwiki_link' )->text()); //works but does not fit, because of format
        $str_Interwiki= "[[:$wiki->interwikiLink:|". wfMessage( 'wikifarm_interwiki_link' )->text() ."]] <nowiki>|</nowiki> ";
        if ( empty($wiki->interwikiLink) ){
            $str_Interwiki = "<span style=\"color: grey;\">" . 
                wfarmHintUnhilited( wfMessage( 'wikifarm_interwiki_link' )->text(), wfMessage( 'wikifarm_interwiki_link_absent' )->text() ) .
                "</span> <nowiki>|</nowiki> ";
        }
        $fields['wikiName'] = "\n| $wiki->name";
        $fields['links']    = "\n| <small> ".
            "\n* '''". wfMessage('wikifarm_wiki')->text() .":''' ".
                    $str_Interwiki .
                    wfarmWikiLink( $wiki, ":Special:Statistics", wfMessage('wikifarm_statistics')->text() ) ." <nowiki>|</nowiki> ".
                    $wiki->url .
            "\n* '''". wfMessage('wikifarm_engine')->text() .":''' ".
                    wfarmHintUnhilited(wfarmWikiLink( $wiki, ":Special:Version", wfMessage('version-software-version')->text()), wfMessage('version')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:Log", wfMessage('log')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:SpecialPages", wfMessage('specialpages')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:AllMessages", wfMessage('allmessages')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:MediaWiki:Common.css", "CSS" ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:MediaWiki:Mainpage", wfMessage('mainpage')->text() ) ." <nowiki>|</nowiki> ".
            "\n* '''". wfMessage('revdelete-content')->text() .":''' ".
                    wfarmWikiLink( $wiki, ":Special:Allpages", wfMessage('wikifarm_pages')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:Categories", wfMessage('categories')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:RecentChanges", wfMessage('wikifarm_files')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:Sidebar", wfMessage('wikifarm_sidebar')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:Search", wfMessage('search')->text() ) ." <nowiki>|</nowiki> ".
            "\n* '''". wfMessage('group-user')->text() .":''' ".
                    wfarmWikiLink( $wiki, ":Special:ListUsers", wfMessage('group-user')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:UserLogin", wfMessage('nologinlink')->text() ) ." <nowiki>|</nowiki> ".
                    wfarmWikiLink( $wiki, ":Special:UserRights", wfMessage('listgrouprights-rights')->text() ) ." <nowiki>|</nowiki> ".
            "</small>\n";

    $tableRecords .= $newRecord . $fields['wikiName'] . $fields['links'];
    }
    $out->addWikiText( $tableHeader . $tableRecords . $tableFooter );
    
}

} //class SpecialWikiFarm

//-------------------------------------------------
//------- Functions outsite any class
//-------------------------------------------------
