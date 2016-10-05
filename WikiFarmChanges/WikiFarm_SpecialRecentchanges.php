<?php
/**
 * This code based on SpecialRecentchanges.php from MediaWikis 1.17
 * all changed lines marked with "WikiFarm edition" comment
 *
 * GNU/GPL v.2 license
 *
 * A special page that lists last changes made to each wiki
 *
 * Implements Special:WikiFarmChanges
 * @file
 * @ingroup SpecialPage
 */
//global $IP;
//require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" );

class WikiFarm_SpecialRecentChanges extends IncludableSpecialPage {  //WikiFarm edition
	var $rcOptions, $rcSubpage;

	public function __construct( $name = 'WikiFarmChanges' ) {    //WikiFarm edition
		parent::__construct( $name );
	}

	/**
	 * Get a FormOptions object containing the default options
	 *
	 * @return FormOptions
	 */
	public function getDefaultOptions() {
		global $wgUser;
		$opts = new FormOptions();

		$opts->add( 'days',  (int)$wgUser->getOption( 'rcdays' ) );
		$opts->add( 'limit', (int)$wgUser->getOption( 'rclimit' ) );
		$opts->add( 'from', '' );

		$opts->add( 'hideminor',     $wgUser->getBoolOption( 'hideminor' ) );
		$opts->add( 'hidebots',      true  );
		$opts->add( 'hideanons',     false );
		$opts->add( 'hideliu',       false );
		$opts->add( 'hidepatrolled', $wgUser->getBoolOption( 'hidepatrolled' ) );
		$opts->add( 'hidemyself',    true );       //WikiFarm edition

		$opts->add( 'namespace', '', FormOptions::INTNULL );
		$opts->add( 'invert', false );

		$opts->add( 'categories', '' );
		$opts->add( 'categories_any', false );
		$opts->add( 'tagfilter', '' );
		return $opts;
	}

	/**
	 * Create a FormOptions object with options as specified by the user
	 *
	 * @return FormOptions
	 */
	public function setup( $parameters ) {
		global $wgRequest;

		$opts = $this->getDefaultOptions();
		$opts->fetchValuesFromRequest( $wgRequest );

		// Give precedence to subpage syntax
		if( $parameters !== null ) {
			$this->parseParameters( $parameters, $opts );
		}

		$opts->validateIntBounds( 'limit', 0, 5000 );
		return $opts;
	}

	/**
	 * Create a FormOptions object specific for feed requests and return it
	 *
	 * @return FormOptions
	 */
	public function feedSetup() {
		global $wgFeedLimit, $wgRequest;
		$opts = $this->getDefaultOptions();
		# Feed is cached on limit,hideminor,namespace; other params would randomly not work
		$opts->fetchValuesFromRequest( $wgRequest, array( 'limit', 'hideminor', 'namespace' ) );
		$opts->validateIntBounds( 'limit', 0, $wgFeedLimit );
		return $opts;
	}

	/**
	 * Get the current FormOptions for this request
	 */
	public function getOptions() {
		if ( $this->rcOptions === null ) {
			global $wgRequest;
			$feedFormat = $wgRequest->getVal( 'feed' );
			$this->rcOptions = $feedFormat ? $this->feedSetup() : $this->setup( $this->rcSubpage );
		}
		return $this->rcOptions;
	}


	/**
	 * Main execution point
	 *
	 * @param $subpage String
	 * @param $wiki wfarmWikiInstance, see WikiFarm_common.php 	//WikiFarm edition
	 */
	public function execute( $subpage, $wiki, $opts) {		//WikiFarm edition
		global $wgRequest, $wgOut;
		$this->rcSubpage = $subpage;
		$feedFormat = $wgRequest->getVal( 'feed' );

		# 10 seconds server-side caching max
		$wgOut->setSquidMaxage( 10 );
		# Check if the client has a cached version
		$lastmod = $this->checkLastModified( $feedFormat, $wiki );
		if( $lastmod === false ) {
			return;
		}

		#$opts = $this->getOptions(); //moved to WikiFarmChanges_body.php
		#$this->setHeaders();        //-pm- no need
		#$this->outputHeader();      //-pm- no need

		// Fetch results, prepare a batch link existence check query
		$conds = $this->buildMainQueryConds( $opts, $wiki );
		$rows = $this->doMainQuery( $conds, $opts, $wiki );  //WikiFarm edition
		if( $rows === false ){
			if( !$this->including() ) {
				$this->doHeader( $opts );
			}
			return;
		}

		if( !$feedFormat ) {
			$batch = new LinkBatch;
			foreach( $rows as $row ) {
				//$wgOut->addWikiText("'''row->rc_  :''' ". $row->rc_title); //-pm- debug
				$batch->add( NS_USER, $row->rc_user_text  );
				$batch->add( NS_USER_TALK, $row->rc_user_text  );
				$batch->add( $row->rc_namespace, $row->rc_title );
			}
			$batch->execute();
		}
		if( $feedFormat ) {
			list( $changesFeed, $formatter ) = $this->getFeedObject( $feedFormat );
			$changesFeed->execute( $formatter, $rows, $lastmod, $opts );
		} else {
			$this->webOutput( $rows, $opts, $wiki );
		}

		$rows->free();
	}

	/**
	 * Return an array with a ChangesFeed object and ChannelFeed object
	 *
	 * @return Array
	 */
	public function getFeedObject( $feedFormat ){
		$changesFeed = new ChangesFeed( $feedFormat, 'rcfeed' );
		$formatter = $changesFeed->getFeedObject(
			wfMsgForContent( 'recentchanges' ),
			wfMsgForContent( 'recentchanges-feed-description' )
		);
		return array( $changesFeed, $formatter );
	}

	/**
	 * Process $par and put options found if $opts
	 * Mainly used when including the page
	 *
	 * @param $par String
	 * @param $opts FormOptions
	 */
	public function parseParameters( $par, FormOptions $opts ) {
		$bits = preg_split( '/\s*,\s*/', trim( $par ) );
		foreach( $bits as $bit ) {
			if( 'hidebots' === $bit ) $opts['hidebots'] = true;
			if( 'bots' === $bit ) $opts['hidebots'] = false;
			if( 'hideminor' === $bit ) $opts['hideminor'] = true;
			if( 'minor' === $bit ) $opts['hideminor'] = false;
			if( 'hideliu' === $bit ) $opts['hideliu'] = true;
			if( 'hidepatrolled' === $bit ) $opts['hidepatrolled'] = true;
			if( 'hideanons' === $bit ) $opts['hideanons'] = true;
			if( 'hidemyself' === $bit ) $opts['hidemyself'] = true;

			if( is_numeric( $bit ) ) $opts['limit'] =  $bit;

			$m = array();
			if( preg_match( '/^limit=(\d+)$/', $bit, $m ) ) $opts['limit'] = $m[1];
			if( preg_match( '/^days=(\d+)$/', $bit, $m ) ) $opts['days'] = $m[1];
		}
	}

	/**
	 * Get last modified date, for client caching
	 * Don't use this if we are using the patrol feature, patrol changes don't
	 * update the timestamp
	 *
	 * @param $feedFormat String
	 * @return String or false
	 */
	public function checkLastModified( $feedFormat, $wiki ) {
		global $wgUseRCPatrol, $wgOut;
		//$wikiID = $wiki->db['name'] ."-". $wiki->prefix;	//WikiFarm edition
		//$groups = array();        //fake array for wfGetDB	//WikiFarm edition
		//$dbr = wfGetDB( DB_SLAVE, array(), $wikiID );   	//WikiFarm edition
		$dbr = wfarmNewDB($wiki, "WikiFarm_SpecialRecentChanges.checkLastModified()");
		//$table = $wiki->db['name'] ."---". $wiki->db['server']  ."---".  $wiki->prefix. 'recentchanges';
		$table_recentchanges = '`'. $wiki->prefix .'recentchanges`';
		$lastmod = $dbr->selectField( $table_recentchanges, 'MAX(rc_timestamp)', false, __METHOD__ );
		//wfDebug("WikiFarm_SpecialRecentChanges.checkLastModified(): table prefix=$table_recentchanges\n");
		if( $feedFormat || !$wgUseRCPatrol ) {
			if( $lastmod && $wgOut->checkLastModified( $lastmod, $wiki ) ) {
				# Client cache fresh and headers sent, nothing more to do.
				return false;
			}
		}
		return $lastmod;
	}

	/**
	 * Return an array of conditions depending of options set in $opts
	 *
	 * @param $opts FormOptions
	 * @return array
	 */
	public function buildMainQueryConds( FormOptions $opts, $wiki ) {
		global $wgUser;

		//$wikiID = $wiki->db['name'] ."-". $wiki->prefix;	//WikiFarm edition
		//$groups = array();        //fake array for wfGetDB	//WikiFarm edition
		//$dbr = wfGetDB( DB_SLAVE, $groups, $wikiID );   	//WikiFarm edition
		$conds = array();
		$dbr = wfarmNewDB($wiki, "WikiFarm_SpecialRecentChanges.buildMainQueryConds()");

		# It makes no sense to hide both anons and logged-in users
		# Where this occurs, force anons to be shown
		$forcebot = false;
		if( $opts['hideanons'] && $opts['hideliu'] ){
			# Check if the user wants to show bots only
			if( $opts['hidebots'] ){
				$opts['hideanons'] = false;
			} else {
				$forcebot = true;
				$opts['hidebots'] = false;
			}
		}

		// Calculate cutoff
		$cutoff_unixtime = time() - ( $opts['days'] * 86400 );
		$cutoff_unixtime = $cutoff_unixtime - ($cutoff_unixtime % 86400);
		$cutoff = $dbr->timestamp( $cutoff_unixtime );

		$fromValid = preg_match('/^[0-9]{14}$/', $opts['from']);
		if( $fromValid && $opts['from'] > wfTimestamp(TS_MW,$cutoff) ) {
			$cutoff = $dbr->timestamp($opts['from']);
		} else {
			$opts->reset( 'from' );
		}

		$conds[] = 'rc_timestamp >= ' . $dbr->addQuotes( $cutoff );


		$hidePatrol = $wgUser->useRCPatrol() && $opts['hidepatrolled'];
		$hideLoggedInUsers = $opts['hideliu'] && !$forcebot;
		$hideAnonymousUsers = $opts['hideanons'] && !$forcebot;

		if( $opts['hideminor'] )  $conds['rc_minor'] = 0;
		if( $opts['hidebots'] )   $conds['rc_bot'] = 0;
		if( $hidePatrol )         $conds['rc_patrolled'] = 0;
		if( $forcebot )           $conds['rc_bot'] = 1;
		if( $hideLoggedInUsers )  $conds[] = 'rc_user = 0';
		if( $hideAnonymousUsers ) $conds[] = 'rc_user != 0';

		if( $opts['hidemyself'] ) {
			if( $wgUser->getId() ) {
				$conds[] = 'rc_user != ' . $dbr->addQuotes( $wgUser->getId() );
			} else {
				$conds[] = 'rc_user_text != ' . $dbr->addQuotes( $wgUser->getName() );
			}
		}

		# Namespace filtering
		if( $opts['namespace'] !== '' ) {
			if( !$opts['invert'] ) {
				$conds[] = 'rc_namespace = ' . $dbr->addQuotes( $opts['namespace'] );
			} else {
				$conds[] = 'rc_namespace != ' . $dbr->addQuotes( $opts['namespace'] );
			}
		}

		return $conds;
	}

	/**
	 * Process the query
	 *
	 * @param $conds Array
	 * @param $opts FormOptions
	 * @return database result or false (for Recentchangeslinked only)
	 * @param $wiki wfarmWikiInstance, see WikiFarm_common.php 	//WikiFarm edition
	 */
	public function doMainQuery( $conds, $opts, $wiki ) {    //WikiFarm edition
		global $wgUser;
		global $wgOut, $wgDBprefix; //WikiFarm edition

		$table_recentchanges = '`'. $wiki->prefix .'recentchanges`';
		//$tables = array( 'recentchanges' );
		$tables = array( $table_recentchanges );
		$join_conds = array();
		$query_options = array( 'USE INDEX' => array('recentchanges' => 'rc_timestamp') );

		$uid = $wgUser->getId();
		/*$wikiID = $wiki->db['name'] ."-". $wiki->prefix;	//WikiFarm edition
		$groups = array();        //fake array for wfGetDB	//WikiFarm edition
		$dbr = wfGetDB( DB_SLAVE, $groups, $wikiID );   	//WikiFarm edition*/
		$dbr = wfarmNewDB($wiki, "WikiFarm_SpecialRecentChanges.doMainQuery()");

		$limit = $opts['limit'];
		$namespace = $opts['namespace'];
		$select = '*';
		$invert = $opts['invert'];

		// JOIN on watchlist for users
		if( $uid ) {
			$tables[] = 'watchlist';
			$join_conds['watchlist'] = array('LEFT JOIN',
				"wl_user={$uid} AND wl_title=rc_title AND wl_namespace=rc_namespace");
		}
		if ($wgUser->isAllowed("rollback")) {
			$tables[] = 'page';
			$join_conds['page'] = array('LEFT JOIN', 'rc_cur_id=page_id');
		}
		if ( !$this->including() ) {
			// Tag stuff.
			// Doesn't work when transcluding. See bug 23293
			$fields = array();
			// Fields are * in this case, so let the function modify an empty array to keep it happy.
			ChangeTags::modifyDisplayQuery(
				$tables, $fields, $conds, $join_conds, $query_options, $opts['tagfilter']
			);
		}

		if ( !wfRunHooks( 'SpecialRecentChangesQuery', array( &$conds, &$tables, &$join_conds, $opts, &$query_options, &$select ) ) )
			return false;

		// Don't use the new_namespace_time timestamp index if:
		// (a) "All namespaces" selected
		// (b) We want all pages NOT in a certain namespaces (inverted)
		// (c) There is a tag to filter on (use tag index instead)
		// (d) UNION + sort/limit is not an option for the DBMS
		if( is_null( $namespace )
			|| ( $invert && !is_null( $namespace ) )
			|| $opts['tagfilter'] != ''
			|| !$dbr->unionSupportsOrderAndLimit() )
		{
			$res = $dbr->select( $tables, '*', $conds, __METHOD__,
				array( 'ORDER BY' => 'rc_timestamp DESC', 'LIMIT' => $limit ) +
				$query_options,
				$join_conds );
		// We have a new_namespace_time index! UNION over new=(0,1) and sort result set!
		} else {
			// New pages
			$sqlNew = $dbr->selectSQLText( $tables, $select,
				array( 'rc_new' => 1 ) + $conds,
				__METHOD__,
				array( 'ORDER BY' => 'rc_timestamp DESC', 'LIMIT' => $limit,
					'USE INDEX' =>  array('recentchanges' => 'rc_timestamp') ),
				$join_conds );
			// Old pages
			$sqlOld = $dbr->selectSQLText( $tables, '*',
				array( 'rc_new' => 0 ) + $conds,
				__METHOD__,
				array( 'ORDER BY' => 'rc_timestamp DESC', 'LIMIT' => $limit,
					'USE INDEX' =>  array('recentchanges' => 'rc_timestamp') ),
				$join_conds );
			# Join the two fast queries, and sort the result set
			$sql = $dbr->unionQueries(array($sqlNew, $sqlOld), false).' ORDER BY rc_timestamp DESC';
			$sql = $dbr->limitResult($sql, $limit, false);
			$res = $dbr->query( $sql, __METHOD__ );
/*		//WikiFarm edition
			$wgOut->addWikiText("'''cur2    prefix:''' ". $wgDBprefix);
			$wgOut->addWikiText("'''passed prefix:''' ". $prefix);
			$wgOut->addWikiText("'''db name      :''' ". $db['name']);
			$wgOut->addWikiText("'''sql before   :''' ". $sql);

*/
		}

		return $res;
	}

	/**
	 * Send output to $wgOut, only called if not used feeds
	 *
	 * @param $rows Array of database rows
	 * @param $opts FormOptions
	 */
	public function webOutput( $rows, $opts, $wiki) {
		global $wgOut, $wgUser, $wgRCShowWatchingUsers, $wgShowUpdatedMarker;
		global $wgAllowCategorizedRecentChanges;

		$limit = $opts['limit'];
/*
		if( !$this->including() ) {
			// Output options box
			$this->doHeader( $opts );
		}
*/
		// And now for the content
		$wgOut->setFeedAppendQuery( $this->getFeedQuery() );

		if( $wgAllowCategorizedRecentChanges ) {
			$this->filterByCategories( $rows, $opts );
		}

		$showWatcherCount = $wgRCShowWatchingUsers && $wgUser->getOption( 'shownumberswatching' );
		$watcherCache = array();

		/*$wikiID = $wiki->db['name'] ."-". $wiki->prefix;	//WikiFarm edition
		$groups = array();        //fake array for wfGetDB	//WikiFarm edition
		$dbr = wfGetDB( DB_SLAVE, $groups, $wikiID );   	//WikiFarm edition*/
		$dbr = wfarmNewDB($wiki, "WikiFarm_SpecialRecentChanges.webOutput()");

		$counter = 1;
		$list = ChangesList::newFromUser( $wgUser );

		$s = $list->beginRecentChangesList();
		foreach( $rows as $obj ) {
			if( $limit == 0 ) break;
			$rc = RecentChange::newFromRow( $obj );
			$rc->counter = $counter++;
			# Check if the page has been updated since the last visit
			if( $wgShowUpdatedMarker && !empty($obj->wl_notificationtimestamp) ) {
				$rc->notificationtimestamp = ($obj->rc_timestamp >= $obj->wl_notificationtimestamp);
			} else {
				$rc->notificationtimestamp = false; // Default
			}
			# Check the number of users watching the page
			$rc->numberofWatchingusers = 0; // Default
			if( $showWatcherCount && $obj->rc_namespace >= 0 ) {
				if( !isset($watcherCache[$obj->rc_namespace][$obj->rc_title]) ) {
					$watcherCache[$obj->rc_namespace][$obj->rc_title] =
						 $dbr->selectField( 'watchlist',
							'COUNT(*)',
							array(
								'wl_namespace' => $obj->rc_namespace,
								'wl_title' => $obj->rc_title,
							),
							__METHOD__ . '-watchers' );
				}
				$rc->numberofWatchingusers = $watcherCache[$obj->rc_namespace][$obj->rc_title];
			}
			$s .= $list->recentChangesLine( $rc, !empty( $obj->wl_user ), $counter );
			--$limit;
		}
		$s .= $list->endRecentChangesList();
		$wgOut->addHTML( $s );
	}

	/**
	 * Get the query string to append to feed link URLs.
	 * This is overridden by RCL to add the target parameter
	 */
	public function getFeedQuery() {
		return false;
	}

	/**
	 * Return the text to be displayed above the changes
	 *
	 * @param $opts FormOptions
	 * @return String: XHTML
	 */
	public function doHeader( $opts ) {
		global $wgScript, $wgOut;

		//$this->setTopText( $wgOut, $opts );	//-pm- we don't whant comment above the form

		$defaults = $opts->getAllValues();
		$nondefaults = $opts->getChangedValues();
		$opts->consumeValues( array( 'namespace', 'invert', 'tagfilter',
			'categories', 'categories_any' ) );

		$panel = array();
		$panel[] = $this->optionsPanel( $defaults, $nondefaults );
		$panel[] = '<hr />';

		$extraOpts = $this->getExtraOptions( $opts );
		$extraOptsCount = count( $extraOpts );
		$count = 0;
		$submit = ' ' . Xml::submitbutton( wfMsg( 'allpagessubmit' ) );

		$out = Xml::openElement( 'table', array( 'class' => 'mw-recentchanges-table' ) );
		foreach( $extraOpts as $optionRow ) {
			# Add submit button to the last row only
			++$count;
			$addSubmit = $count === $extraOptsCount ? $submit : '';

			$out .= Xml::openElement( 'tr' );
			if( is_array( $optionRow ) ) {
				$out .= Xml::tags( 'td', array( 'class' => 'mw-label' ), $optionRow[0] );
				$out .= Xml::tags( 'td', array( 'class' => 'mw-input' ), $optionRow[1] . $addSubmit );
			} else {
				$out .= Xml::tags( 'td', array( 'class' => 'mw-input', 'colspan' => 2 ), $optionRow . $addSubmit );
			}
			$out .= Xml::closeElement( 'tr' );
		}
		$out .= Xml::closeElement( 'table' );

		$unconsumed = $opts->getUnconsumedValues();
		foreach( $unconsumed as $key => $value ) {
			$out .= Html::hidden( $key, $value );
		}

		$t = $this->getTitle();
		$out .= Html::hidden( 'title', $t->getPrefixedText() );
		$form = Xml::tags( 'form', array( 'action' => $wgScript ), $out );
		$panel[] = $form;
		$panelString = implode( "\n", $panel );

		$wgOut->addHTML(
			Xml::fieldset( wfMsg( 'recentchanges-legend' ), $panelString, array( 'class' => 'rcoptions' ) )
		);

		$this->setBottomText( $wgOut, $opts );
	}

	/**
	 * Get options to be displayed in a form
	 *
	 * @param $opts FormOptions
	 * @return Array
	 */
	function getExtraOptions( $opts ){
		$extraOpts = array();
		$extraOpts['namespace'] = $this->namespaceFilterForm( $opts );

		global $wgAllowCategorizedRecentChanges;
		if( $wgAllowCategorizedRecentChanges ) {
			$extraOpts['category'] = $this->categoryFilterForm( $opts );
		}

		$tagFilter = ChangeTags::buildTagFilterSelector( $opts['tagfilter'] );
		if ( count($tagFilter) )
			$extraOpts['tagfilter'] = $tagFilter;

		wfRunHooks( 'SpecialRecentChangesPanel', array( &$extraOpts, $opts ) );
		return $extraOpts;
	}

	/**
	 * Send the text to be displayed above the options
	 *
	 * @param $out OutputPage
	 * @param $opts FormOptions
	 */
	function setTopText( OutputPage $out, FormOptions $opts ){
		$out->addWikiText( wfMsgForContentNoTrans( 'recentchangestext' ) );
	}

	/**
	 * Send the text to be displayed after the options, for use in
	 * Recentchangeslinked
	 *
	 * @param $out OutputPage
	 * @param $opts FormOptions
	 */
	function setBottomText( OutputPage $out, FormOptions $opts ){}

	/**
	 * Creates the choose namespace selection
	 *
	 * @param $opts FormOptions
	 * @return String
	 */
	protected function namespaceFilterForm( FormOptions $opts ) {
		$nsSelect = Xml::namespaceSelector( $opts['namespace'], '' );
		$nsLabel = Xml::label( wfMsg('namespace'), 'namespace' );
		$invert = Xml::checkLabel( wfMsg('invert'), 'invert', 'nsinvert', $opts['invert'] );
		return array( $nsLabel, "$nsSelect $invert" );
	}

	/**
	 * Create a input to filter changes by categories
	 *
	 * @param $opts FormOptions
	 * @return Array
	 */
	protected function categoryFilterForm( FormOptions $opts ) {
		list( $label, $input ) = Xml::inputLabelSep( wfMsg('rc_categories'),
			'categories', 'mw-categories', false, $opts['categories'] );

		$input .= ' ' . Xml::checkLabel( wfMsg('rc_categories_any'),
			'categories_any', 'mw-categories_any', $opts['categories_any'] );

		return array( $label, $input );
	}

	/**
	 * Filter $rows by categories set in $opts
	 *
	 * @param $rows Array of database rows
	 * @param $opts FormOptions
	 */
	function filterByCategories( &$rows, FormOptions $opts ) {
		$categories = array_map( 'trim', explode( '|' , $opts['categories'] ) );

		if( !count( $categories ) ) {
			return;
		}

		# Filter categories
		$cats = array();
		foreach( $categories as $cat ) {
			$cat = trim( $cat );
			if( $cat == '' ) continue;
			$cats[] = $cat;
		}

		# Filter articles
		$articles = array();
		$a2r = array();
		$rowsarr = array();
		foreach( $rows AS $k => $r ) {
			$nt = Title::makeTitle( $r->rc_namespace, $r->rc_title );
			$id = $nt->getArticleID();
			if( $id == 0 ) continue; # Page might have been deleted...
			if( !in_array( $id, $articles ) ) {
				$articles[] = $id;
			}
			if( !isset( $a2r[$id] ) ) {
				$a2r[$id] = array();
			}
			$a2r[$id][] = $k;
			$rowsarr[$k] = $r;
		}

		# Shortcut?
		if( !count( $articles ) || !count( $cats ) )
			return ;

		# Look up
		$c = new Categoryfinder;
		$c->seed( $articles, $cats, $opts['categories_any'] ? "OR" : "AND" ) ;
		$match = $c->run();

		# Filter
		$newrows = array();
		foreach( $match AS $id ) {
			foreach( $a2r[$id] AS $rev ) {
				$k = $rev;
				$newrows[$k] = $rowsarr[$k];
			}
		}
		$rows = $newrows;
	}

	/**
	 * Makes change an option link which carries all the other options
	 *
	 * @param $title Title
	 * @param $override Array: options to override
	 * @param $options Array: current options
	 * @param $active Boolean: whether to show the link in bold
	 */
	function makeOptionsLink( $title, $override, $options, $active = false ) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$params = $override + $options;
		if ( $active ) {
			return $sk->link( $this->getTitle(), '<strong>' . htmlspecialchars( $title ) . '</strong>',
							  array(), $params, array( 'known' ) );
		} else {
			return $sk->link( $this->getTitle(), htmlspecialchars( $title ), array() , $params, array( 'known' ) );
		}
	}

	/**
	 * Creates the options panel.
	 *
	 * @param $defaults Array
	 * @param $nondefaults Array
	 */
	function optionsPanel( $defaults, $nondefaults ) {
		global $wgLang, $wgUser, $wgRCLinkLimits, $wgRCLinkDays;

		$options = $nondefaults + $defaults;

		$note = '';
		if( !wfEmptyMsg( 'rclegend', wfMsg('rclegend') ) ) {
			$note .= '<div class="mw-rclegend">' . wfMsgExt( 'rclegend', array('parseinline') ) . "</div>\n";
		}
		if( $options['from'] ) {
			$note .= wfMsgExt( 'rcnotefrom', array( 'parseinline' ),
				$wgLang->formatNum( $options['limit'] ),
				$wgLang->timeanddate( $options['from'], true ),
				$wgLang->date( $options['from'], true ),
				$wgLang->time( $options['from'], true ) ) . '<br />';
		}

		# Sort data for display and make sure it's unique after we've added user data.
		$wgRCLinkLimits[] = $options['limit'];
		$wgRCLinkDays[] = $options['days'];
		sort( $wgRCLinkLimits );
		sort( $wgRCLinkDays );
		$wgRCLinkLimits = array_unique( $wgRCLinkLimits );
		$wgRCLinkDays = array_unique( $wgRCLinkDays );

		// limit links
		foreach( $wgRCLinkLimits as $value ) {
			$cl[] = $this->makeOptionsLink( $wgLang->formatNum( $value ),
				array( 'limit' => $value ), $nondefaults, $value == $options['limit'] ) ;
		}
		$cl = $wgLang->pipeList( $cl );

		// day links, reset 'from' to none
		foreach( $wgRCLinkDays as $value ) {
			$dl[] = $this->makeOptionsLink( $wgLang->formatNum( $value ),
				array( 'days' => $value, 'from' => '' ), $nondefaults, $value == $options['days'] ) ;
		}
		$dl = $wgLang->pipeList( $dl );


		// show/hide links
		$showhide = array( wfMsg( 'show' ), wfMsg( 'hide' ) );
		$minorLink = $this->makeOptionsLink( $showhide[1-$options['hideminor']],
			array( 'hideminor' => 1-$options['hideminor'] ), $nondefaults);
		$botLink = $this->makeOptionsLink( $showhide[1-$options['hidebots']],
			array( 'hidebots' => 1-$options['hidebots'] ), $nondefaults);
		$anonsLink = $this->makeOptionsLink( $showhide[ 1 - $options['hideanons'] ],
			array( 'hideanons' => 1 - $options['hideanons'] ), $nondefaults );
		$liuLink   = $this->makeOptionsLink( $showhide[1-$options['hideliu']],
			array( 'hideliu' => 1-$options['hideliu'] ), $nondefaults);
		$patrLink  = $this->makeOptionsLink( $showhide[1-$options['hidepatrolled']],
			array( 'hidepatrolled' => 1-$options['hidepatrolled'] ), $nondefaults);
		$myselfLink = $this->makeOptionsLink( $showhide[1-$options['hidemyself']],
			array( 'hidemyself' => 1-$options['hidemyself'] ), $nondefaults);

		$links[] = wfMsgHtml( 'rcshowhideminor', $minorLink );
		$links[] = wfMsgHtml( 'rcshowhidebots', $botLink );
		$links[] = wfMsgHtml( 'rcshowhideanons', $anonsLink );
		$links[] = wfMsgHtml( 'rcshowhideliu', $liuLink );
		if( $wgUser->useRCPatrol() )
			$links[] = wfMsgHtml( 'rcshowhidepatr', $patrLink );
		$links[] = wfMsgHtml( 'rcshowhidemine', $myselfLink );
		$hl = $wgLang->pipeList( $links );

		// show from this onward link
		$now = $wgLang->timeanddate( wfTimestampNow(), true );
		$tl =  $this->makeOptionsLink( $now, array( 'from' => wfTimestampNow() ), $nondefaults );

		$rclinks = wfMsgExt( 'rclinks', array( 'parseinline', 'replaceafter' ),
			$cl, $dl, $hl );
		$rclistfrom = wfMsgExt( 'rclistfrom', array( 'parseinline', 'replaceafter' ), $tl );
		return "{$note}$rclinks<br />$rclistfrom";
	}
}

