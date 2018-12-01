<?php
class WikiFarmPages extends SpecialPage {

function __construct() {
	    parent::__construct( 'WikiFarmPages' );
	    wfLoadExtensionMessages('WikiFarmPages');
}

function execute( $par ) {
	global $wgOut, $IP, $wgRequest, $wgMetaNamespace;

    require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" ); 	//read config
    require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" ); 	//include common functions
	$wikis = wfarmDefineWikis( $wikis, $wikiAdmin, $infoLevel, $wfarmPrefixDelimiter );	//define wikis to work with
	$wgOut->addWikiText( wfarmHeader('pages') );	// show WikiFarm links
	//$wgOut->addWikiText( wfarmFormatTitle( wfMsgHtml( 'wikifarm_pages' ) ) ); 	// page title

    //-------------------------------------------------
    //------- Show all pages form -------
    //-------------------------------------------------
	$from = $wgRequest->getVal( 'from', null );
	$namespace = $wgRequest->getInt( 'namespace' );
	$filterform = $this->namespaceForm( $namespace, $from );
	$wgOut->addHTML($filterform);

	//-------------------------------------------------
    //------- Collect data to array $farmPages using botclasses.php -------
    //-------------------------------------------------
	require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" );
	$str="";
	$farmPages = array();
	//if ($infoLevel > 1){ $str_debug = "Get all pages from wikis:"; }
	foreach( $wikis as $wiki ){
		$bot = new extended( $wiki->api );
		if ( !empty($wiki->user) ) {
			$bot->login( $wiki->user, $wiki->pass );
		}
		$namespace = 0; //files
		$limit = 500;
		$wikiPages = $bot->allpages( $from, $namespace, $limit );
		$wiki->arr['pages'] = count($wikiPages);
		//if ($infoLevel > 1){ $str_debug = $str_debug . "\n* '''" . $wiki->name . "''', pages: '''" . $wiki->arr['pages'] . "'''"; }
		//wfDebug("WikiFarm. All pages compare for wiki: ". $wiki->name ."\n");
		if ( count($wikiPages) > 0 ){
			foreach( $wikiPages as $page ){
				$page['links'] = wfarmWikiLink($wiki, $page['title'], $wiki->name);
				$page['wiki_name'] = $wiki->name;
				$key = '';
				foreach( $farmPages as $i => $fp ){
					if ( strcasecmp($fp['title'], $page['title']) == 0 ){	//case-insensitive string comparison (0=equal)	//array_search( $page['title'], $fp ) ){
						$key = $i;
						break;
					}
				}
				//wfDebug("\t". $page['title'] . " <-= ". $key ." =-> " . $fp['title'] ."\n");
				if ( empty($key) ){	//add page to array
					$page['counter'] = 1;
					$farmPages[] = $page;
				}else{ 		//page with such a title is already in array. Add link
					$farmPages[$key]['links']   = $farmPages[$key]['links'] . " | " . $page['links'];
					$farmPages[$key]['counter'] = $farmPages[$key]['counter'] + 1;
				}
			}
		}
		//wfDebug("WikiFarm. All pages. done with: ". $wiki->name . "\n");
	}
	//wfDebug("WikiFarm. All pages. DONE with all wikis\n");
	//if ($infoLevel > 1){ $wgOut->addWikiText($str_debug); }

    //-------------------------------------------------
    //------- Show pages with links to their wikis -------
    //-------------------------------------------------
	$tableHeader =	"<div align=center>" .
		"\n{| class=\"wikitable sortable\" ".
		"\n|-".
		"\n! ". wfMsg('article') .
		"\n! ".
		"\n! ". wfMsg('wikifarm_wiki_name') .
		"\n";
	$tableRecords = '';
	$tableFooter = "\n|}\n</div>\n";
	$fields = array();	//table fields
	foreach( $farmPages as $page ){
		$fields['article'] 	= "\n| ". $page['title'];
		$fields['counter'] 	= "\n| ". $page['counter'];
		$fields['wiki']	 	= "\n| ". $page['links'];

		$tableRecords .= "\n|- valign=top" . $fields['article'] . $fields['counter'] . $fields['wiki'];
	}
	//-------------------------------------------------
	//------- Output final page -------
	//-------------------------------------------------
	//$wgOut->addWikiText( "<pre>". $table2Records ."</pre>");
	$str_summary = wfarmSummary($wikis, 'pages', '/Special:AllPages');
	$wgOut->addWikiText( $str_summary );
	$wgOut->addWikiText( $tableHeader . $tableRecords . $tableFooter );
    $wgOut->addWikiText( wfarmFooter() );	 // show WikiFarm footer
} // function execute( $par )


/**
 * HTML for the top form. (Based on MediaWiki 16.5 SpecialAllpages.php code)
 *
 * @param $namespace Integer: a namespace constant (default NS_MAIN).
 * @param $from String: dbKey we are starting listing at.
 * @param $to String: dbKey we are ending listing at.
 * @return html form
 */
function namespaceForm( $namespace = NS_MAIN, $from = '' ) {
	global $wgScript;
	$t = $this->getTitle();

	$out  = Xml::openElement( 'div', array( 'class' => 'namespaceoptions' ) );
	$out .= Xml::openElement( 'form', array( 'method' => 'get', 'action' => $wgScript ) );
	$out .= Html::hidden( 'title', $t->getPrefixedText() );
	$out .= Xml::openElement( 'fieldset' );
	$out .= Xml::element( 'legend', null, wfMsg( 'allpages' ) );
	$out .= Xml::openElement( 'table', array( 'id' => 'nsselect', 'class' => 'allpages' ) );
	$out .= "<tr>
	<td class='mw-label'>" .
			Xml::label( wfMsg( 'allpagesfrom' ), 'nsfrom' ) .
			"	</td>
	<td class='mw-input'>" .
			Xml::input( 'from', 30, str_replace('_',' ',$from), array( 'id' => 'nsfrom' ) ) .
			"	</td>
</tr>";
/*<tr>
	<td class='mw-label'>" .
			Xml::label( wfMsg( 'allpagesto' ), 'nsto' ) .
			"	</td>
			<td class='mw-input'>" .
			Xml::input( 'to', 30, str_replace('_',' ',$to), array( 'id' => 'nsto' ) ) .
			"		</td>
</tr>*/
		$out .= "<tr>
	<td class='mw-label'>" .
			Xml::label( wfMsg( 'namespace' ), 'namespace' ) .
			"	</td>
			<td class='mw-input'>" .
			Xml::namespaceSelector( $namespace, null ) . ' ' .
			Xml::submitButton( wfMsg( 'allpagessubmit' ) ) .
			"	</td>
</tr>";
	$out .= Xml::closeElement( 'table' );
	$out .= Xml::closeElement( 'fieldset' );
	$out .= Xml::closeElement( 'form' );
	$out .= Xml::closeElement( 'div' );
	return $out;
} //function namespaceForm( $namespace = NS_MAIN, $from = '', $to = '' )

} //class WikiFarmPages
