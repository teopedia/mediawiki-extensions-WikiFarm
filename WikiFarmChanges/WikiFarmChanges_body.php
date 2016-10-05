<?php

class WikiFarmChanges extends SpecialPage {

	function __construct() {
		parent::__construct('WikiFarmChanges');
		wfLoadExtensionMessages('WikiFarmChanges');
	}

	function execute($par) {
		global $wgOut, $IP, $wgRequest, $wgUser;
		require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" );  //read config
		require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" );  //include common functions
		$wgOut->addWikiText( wfarmHeader('changes') );	// show WikiFarm links
		//$wgOut->addWikiText( wfarmFormatTitle(wfMsg('wikifarm_changes')) ); // page title
		$wikis = wfarmDefineWikis($wikis, $wikiAdmin, $infoLevel, $wfarmPrefixDelimiter); //define wikis to work with

		//-------------------------------------------------
		//------- Show recent changes filter form -------
		//-------------------------------------------------
		require_once( "WikiFarm_SpecialRecentchanges.php" );

		$report_rc = new WikiFarm_SpecialRecentChanges();
		$opts = $report_rc->getOptions();
		$report_rc->doHeader($opts);

		//-------------------------------------------------
		//------- Get recent changes from every wiki -------
		//-------------------------------------------------
		//TODO: Option to join result for all wikis to trace user activity through all wikis and so on
		require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" );
		$tables = '';	//all tables. One table - one wiki changes
		$tableHeader = "\n{| class=\"wikitable sortable\" align=center\n" .
				"\n! " . wfMsg('prefs-datetime') .
				"\n! " .
				"\n! " . wfMsg('article') .
				"\n! " . wfMsg('listfiles_size') .
				"\n! " .
				"\n! " . wfMsg('nstab-user') .
				"\n! " . wfMsg('summary') .
				"\n";
		$newRecord   = "\n|- valign=top";
		$tableFooter = "\n|}\n";
		foreach ($wikis as $wiki) {
			$bot = new extended($wiki->api);
			if (!empty($wiki->user)) {
				$bot->login($wiki->user, $wiki->pass);
			}
			$wikiChanges = $bot->recentchanges($wgRequest, $wgUser);
			$wiki->arr['changes'] = count($wikiChanges);
			if ($wiki->arr['changes'] > 0) {
				$wikiString = "===" . $wiki->name . " <small><small>[" . $wiki->url . "/Special:RecentChanges " . $wiki->url . "]</small></small> ===\n----";
				$fields = array();	//table fields
				$tableRecords = '';
				foreach ($wikiChanges as $page) {
					$fields['type'] = "\n| ";
					if ($page['type'] == 'new') {
						$fields['type'] .= "'''" . wfarmHint(wfMsg('newpageletter'), wfMsg('recentchanges-label-newpage')) . "'''";
					}
					if (($page['type'] == 'minor') || (!($page['minor'] === null) )) {
						$fields['type'] .= "" . wfarmHint(wfMsg('minoreditletter'), wfMsg('recentchanges-label-minor'));
					}
					if ($page['type'] == 'bot') {
						$fields['type'] .= "'''" . wfarmHint(wfMsg('boteditletter'), wfMsg('recentchanges-label-bot')) . "'''";
					}
					$dif = $page['newlen'] - $page['oldlen'];
					$difColor = "green";
					if ($dif < 0) {
						$difColor = 'red';
					}
					$date_time = wfarmFormatDate($page['timestamp']);
					$fields['time'] = "\n| " . $date_time;
					$fields['page'] = "\n| " . wfarmWikiLink($wiki, $page['title'], $page['title']);
					$fields['size'] = "\n| <div align=right>" . $page['newlen'] . "</div>";
					$fields['diff'] = "\n| <div align=right><span style=\"color: $difColor;\">$dif</span></div>";
					$fields['user'] = "\n| " . wfarmWikiLink($wiki, "User:". $page['user'], $page['user']);
					$fields['comment'] = "\n| <small>''<nowiki>" . $page['comment'] . "</nowiki>''</small>";
					$tableRecords .= $newRecord . $fields['time'] . $fields['type'] . $fields['page'] . $fields['size'] . $fields['diff'] . $fields['user'] . $fields['comment'] . "\n";
				}
				$tables .= $wikiString . $tableHeader . $tableRecords . $tableFooter;
			}
		}

		$str_summary = wfarmSummary($wikis, 'changes', '/Special:Recentchanges');
		$wgOut->addWikiText($str_summary);   // show summary table
		$wgOut->addWikiText($tables); // show requested data (recent changes)
		$wgOut->addWikiText( wfarmFooter() ); // show WikiFarm footer
	} // function execute( $par )
} //class WikiFarmChanges
