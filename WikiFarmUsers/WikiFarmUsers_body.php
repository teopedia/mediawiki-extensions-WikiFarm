<?php

class WikiFarmUsers extends SpecialPage {

function __construct() {
	    parent::__construct( 'WikiFarmUsers' );
	    wfLoadExtensionMessages('WikiFarmUsers');
}

function execute( $par ) {
	global $wgOut, $IP;

    require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" ); 	//read config
    require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" );	//include some common variables
    $wgOut->addWikiText( wfarmHeader('users') );				    // show WikiFarm links
	$wikis = wfarmDefineWikis( $wikis, $wikiAdmin, $infoLevel, $wfarmPrefixDelimiter );	//define wikis to work with
	//$wgOut->addWikiText( wfarmFormatTitle( wfMsg( 'wikifarm_users' ) ) );  // page title

	require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" );
	$bot = new extended( $wikiAdmin->api );
	if ( !empty($wikiAdmin->user) ) {
		$bot->login( $wikiAdmin->user, $wikiAdmin->pass );
	}
	$wfarmUsers = $bot->allusers();
	$userCounter = count($wfarmUsers);
	$tableHeader =	"<div align=center>\n{|class=\"wikitable sortable\"".
			"\n|+ <big>". wfMsg( 'listusers' ) . "</big> ($userCounter)" .
			"\n|- ".
			"\n! ". wfMsg( 'wikifarm_users' ) .
			"\n! ".
			"\n! ". wfMsg( 'wikifarmusers_blockage' ) .
			"\n! ". wfMsg( 'contribslink' ) .
			"\n! ". wfMsg( 'prefs-registration' ) .
			"\n! ". wfMsg( 'userrights-groupsmember' ) .
			"\n\n";
	$newRecord    = "\n|- valign=top";
	$tableRecords = '';
	$tableFooter  = "\n|}\n";
	$fields = array();	//table fields
	foreach( $wfarmUsers as $key => $user ){
		$fields['user'] = "\n| ". $user['name'];
		$fields['service'] = "\n| <small>[[User:".		$user['name'] ."|". wfMsg( 'pageinfo-subjectpage' ) ."]]".
			" <nowiki>|</nowiki> [[User_talk:".			$user['name'] ."|". wfMsg( 'wikifarmusers_talk_short' ) ."]]".
			" <nowiki>|</nowiki> [[Special:UserRights/".$user['name'] ."|". wfMsg( 'listgrouprights-rights' ) ."]]".
//			" ".															wfMsg( 'wikifarmusers_password' ) .
			"</small>";
		$fields['contrib'] = "\n| <div align=right>[[Special:Contributions/". $user['name'] ."|". $user['editcount'] ."]]</div>";
		$fields['registration'] = "\n| <div align=center>". wfarmFormatDate($user['registration']) ."</div>";
		$fields['block'] = "\n| [[Special:Block/". $user['name'] ."|". wfMsg( 'blocklink' ) ."]]";
		if ( !empty($user['blockedby']) ){
			$fields['block'] = "\n| style=\"background: #EEAAAA;\"| [[Special:Unblock/". $user['name'] ."|". wfMsg( 'unblocklink' ) ."]]";
		}
		$fields['groups'] = "\n| ";
		foreach( $user['groups'] as $gr ){
			if ( ! ( ($gr == '*') or ($gr == 'user') or ($gr == 'autoconfirmed') ) ){	//hide default groups
				$fields['groups'] .= $gr .", ";
			}
		}
	    $tableRecords .= $newRecord . $fields['user'] . $fields['service'] . $fields['block'] . $fields['contrib'] . $fields['registration'] . $fields['groups'];
	}

    $wgOut->addWikiText( $tableHeader . $tableRecords . $tableFooter );
    $wgOut->addWikiText( wfarmFooter() );	 // show WikiFarm footer
} // function execute( $par )

} //class WikiFarmUsers
