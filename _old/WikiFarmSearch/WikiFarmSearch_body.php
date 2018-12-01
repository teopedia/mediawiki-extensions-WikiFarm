<?php

class WikiFarmSearch extends SpecialPage {

function __construct() {
	    parent::__construct( 'WikiFarmSearch' );
	    wfLoadExtensionMessages('WikiFarmSearch');
}

function execute( $par ) {
	global $wgOut, $IP, $wgRequest;

    require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" ); 	//read config
    require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" );	//include some common variables
    $wgOut->addWikiText( wfarmHeader('search') );					    // show WikiFarm links
	//$wgOut->addWikiText( wfarmFormatTitle( wfMsg( 'wikifarm_search' ) ) );	// page title
	$wikis = wfarmDefineWikis( $wikis, $wikiAdmin, $infoLevel, $wfarmPrefixDelimiter );	//define wikis to work with
	$this->searchForm();
	$searchstring = trim($wgRequest->getVal( 'wpSearchString' ));

	if ( !empty($searchstring) ){
		require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" );
		$tableHeader =	"<div align=center>" .
			"\n{| class=\"wikitable sortable\" ".
			"\n|-".
			"\n! ". wfMsg('wikifarm_pages') .
			"\n! ". wfMsg('listfiles_size') .
			"\n! ". wfMsg('wikifarm_words_number') .
			"\n! ". wfMsg('prefs-datetime') .
			"\n! ". wfMsg('wikifarm_wiki_name') .
			"\n";
		$newRecord    = "\n|- valign=top";
		$tableRecords = '';
		$tableFooter  = "\n|}\n</div>\n";
		foreach( $wikis as $wiki ){
			$bot = new extended( $wiki->api );
			if ( !empty($wiki->user) ) {
				$bot->login( $wiki->user, $wiki->pass );
			}

			// search in title
			$searchResult = $bot->search($searchstring);
			$searchCounter = count($searchResult);
			$wiki->changes = $searchCounter;
			//$wgOut->addWikiText( "wiki: ". $wiki->name ." ($searchCounter)\n");
			if ($searchCounter > 0){
				foreach( $searchResult as $page ){
					$date_time = wfarmFormatDate($page['timestamp']);
					$fields['wiki']	 	= "\n| ". $wiki->name;
					$fields['title'] 	= "\n| [[". $wiki->interwikiLink .":". $page['title'] ."|". $page['title'] ."]] <br />\n<small>". $page['snippet'] ."</small>";
					if ( empty($wiki->interwikiLink) )
						$fields['title'] 	= "\n| [". $wiki->url ."/". wiki_url_encode($page['title']) ." ". $page['title'] ."] <br />\n<small>". $page['snippet'] ."</small>";
					$fields['size']  	= "\n| <div align=right>". $page['size'] ."</div>";
					$fields['words'] 	= "\n| <div align=right>". $page['wordcount'] ."</div>";
					$fields['date']  	= "\n| <div align=center>". $date_time ."</div>";

					$tableRecords .= $newRecord . $fields['title'] . $fields['size'] . $fields['words'] . $fields['date'] . $fields['wiki'];
				}
			} //done with records in wiki

			// search in text
			$searchResult = $bot->search($searchstring, 'text');
			$searchCounter = count($searchResult);
			$wiki->arr['counter'] += $searchCounter;
			if ($searchCounter > 0){
				foreach( $searchResult as $page ){
					$date_time = wfarmFormatDate($page['timestamp']);
					$newRecord    	= "\n\n|- valign=top";
					$fields['wiki']	 	= "\n| ". $wiki->name;
					$fields['title'] 	= "\n| [[". $wiki->interwikiLink .":". $page['title'] ."|". $page['title'] ."]] <br />\n<small>". $page['snippet'] ."</small>";
					if ( empty($wiki->interwikiLink) )
						$fields['title'] 	= "\n| [". $wiki->url ."/". wiki_url_encode($page['title']) ." ". $page['title'] ."] <br />\n<small>". $page['snippet'] ."</small>";
					$fields['size']  	= "\n| <div align=right>". $page['size'] ."</div>";
					$fields['words'] 	= "\n| <div align=right>". $page['wordcount'] ."</div>";
					$fields['date']  	= "\n| <div align=center>". $date_time ."</div>";

					$table2Records .= $newRecord . $fields['title'] . $fields['size'] . $fields['words'] . $fields['date'] . $fields['wiki'];
				}
			} //done with records in wiki
		} //done with all wikis

		//-------------------------------------------------
		//------- Output final page -------
		//-------------------------------------------------
		//$wgOut->addWikiText( "<pre>". $table2Records ."</pre>");
		$str_summary = wfarmSummary($wikis, 'counter', '/Special:Search');
		$wgOut->addWikiText( $str_summary );
		$wgOut->addWikiText( "\n\n== ". wfMsg('titlematches') ." ==" );
		$wgOut->addWikiText( $tableHeader . $tableRecords . $tableFooter );
		$wgOut->addWikiText( "\n\n== ". wfMsg('textmatches') ." ==" );
		$wgOut->addWikiText( $tableHeader . $table2Records . $tableFooter );
	}//search sting is not empty

	$wgOut->addWikiText( wfarmFooter() );	 // show WikiFarm footer
} // function execute( $par )

/**
 * HTML for the top form.
 */
function searchForm(){
	global $wgOut, $wgRequest, $wgUser, $wgScriptPath;

	// Simple search form
	// There is more reliable and complicated form in official search interface in ./includes/specials/SpecialSearch.php
	$actionUrl = $this->getTitle()->getLocalURL( 'action=submit' );
	$token = $wgUser->editToken();
	$searchstring = $wgRequest->getVal( 'wpSearchString' );
	//$searchstring = $wgRequest->getVal( 'wpWikiFarmSearchString' ) ? $wgRequest->getVal( 'wpWikiFarmSearchString' ) : $wgRequest->getVal( 'searchstring' );
	$button = wfMsg( 'wikifarm_search' );
	$topmessage = wfMsg( 'wikifarmsearch_search_farm');

	$wgOut->addHTML(
		Xml::openElement( 'fieldset' ) .
		Xml::element( 'legend', null, $topmessage ) .
		Xml::openElement( 'form', array( 'id' => 'wfarm-searchform', 'method' => 'post', 'action' => $actionUrl ) ) .
		Xml::openElement( 'table' ) .
		'<tr><td class="mw-input">' .
		Xml::input( 'wpSearchString', 60, $searchstring, array( 'tabindex' => '1', 'id' => 'wfarm-searchstring', 'maxlength' => '200' ) ) .
		'</td>'.
		'<td class="mw-submit">' . Xml::submitButton( $button, array( 'id' => 'wfarm-submit' ) ) .
		Html::hidden( 'wpAction', $action ) .
		Html::hidden( 'wpEditToken', $token ) .
		'</td></tr>' .
		Xml::closeElement( 'table' ) .
		Xml::closeElement( 'form' ) .
		Xml::closeElement( 'fieldset' )
	);
	return 0;
} //function drawForm()

} //class WikiFarmSearch
