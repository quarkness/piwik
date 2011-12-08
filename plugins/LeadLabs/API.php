<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id$
 *
 * @category Piwik_Plugins
 * @package Piwik_LeadLabs
 */

/**
 * @see plugins/Referers/functions.php
 */
require_once PIWIK_INCLUDE_PATH . '/plugins/LeadLabs/Visitor.php';

/**
 * The LeadLabs! API lets you access complete visit level information about your visitors. Combined with the power of <a href='http://piwik.org/docs/analytics-api/segmentation/' target='_blank'>Segmentation</a>, 
 * you will be able to request visits filtered by any criteria.
 * 
 * The method "getLastVisitsDetails" will return extensive data for each visit, which includes: server time, visitId, visitorId, 
 * visitorType (new or returning), number of pages, list of all pages (and events, file downloaded and outlinks clicked), 
 * custom variables names and values set to this visit, number of goal conversions (and list of all Goal conversions for this visit, 
 * with time of conversion, revenue, URL, etc.), but also other attributes such as: days since last visit, days since first visit, 
 * country, continent, visitor IP,
 * provider, referrer used (referrer name, keyword if it was a search engine, full URL), campaign name and keyword, operating system, 
 * browser, type of screen, resolution, supported browser plugins (flash, java, silverlight, pdf, etc.), various dates & times format to make
 * it easier for API users... and more!
 * 
 * With the parameter <a href='http://piwik.org/docs/analytics-api/segmentation/' target='_blank'>'&segment='</a> you can filter the
 * returned visits by any criteria (visitor IP, visitor ID, country, keyword used, time of day, etc.).
 * 
 * The method "getCounters" is used to return a simple counter: visits, number of actions, number of converted visits, in the last N minutes.
 * 
 * See also the documentation about <a href='http://piwik.org/docs/real-time/' target='_blank'>Real time widget and visitor level reports</a> in Piwik.
 * @package Piwik_LeadLabs
 */
class Piwik_LeadLabs_API
{
	static private $instance = null;
	/**
	 * @return Piwik_LeadLabs_API
	 */
	static public function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * This will return simple counters, for a given website ID, for visits over the last N minutes
	 * 
	 * @param int Id Site
	 * @param int Number of minutes to look back at
	 * 
	 * @return array( visits => N, actions => M, visitsConverted => P )
	 */
	public function getCounters($idSite, $lastMinutes, $segment = false)
	{
		Piwik::checkUserHasViewAccess($idSite);
		$lastMinutes = (int)$lastMinutes;
		
		$select = "count(*) as visits,
				SUM(log_visit.visit_total_actions) as actions,
				SUM(log_visit.visit_goal_converted) as visitsConverted";
		
		$from = "log_visit";
		
		$where = "log_visit.idsite = ?
				AND log_visit.visit_last_action_time >= ?";
		
		$bind = array(
			$idSite,
			Piwik_Date::factory(time() - $lastMinutes * 60)->toString('Y-m-d H:i:s')
		);
		
		$segment = new Piwik_Segment($segment, $idSite);
		$query = $segment->getSelectQuery($select, $from, $where, $bind);
		
		$data = Piwik_FetchAll($query['sql'], $query['bind']);
		
		// These could be unset for some reasons, ensure they are set to 0
		empty($data[0]['actions']) ? $data[0]['actions'] = 0 : '';
		empty($data[0]['visitsConverted']) ? $data[0]['visitsConverted'] = 0 : '';
		return $data;
	}
	
	/**
	 * The same functionnality can be obtained using segment=visitorId==$visitorId with getLastVisitsDetails
	 * 
	 * @deprecated
	 * @ignore
	 */
	public function getLastVisitsForVisitor( $visitorId, $idSite, $filter_limit = 10 )
	{
		Piwik::checkUserHasViewAccess($idSite);
		$visitorDetails = $this->loadLastVisitorDetailsFromDatabase($idSite, $period = false, $date = false, $segment = false, $filter_limit, $maxIdVisit = false, $visitorId);
		$table = $this->getCleanedVisitorsFromDetails($visitorDetails, $idSite);
		return $table;
	}

	/**
	 * Returns the last visits tracked in the specified website
	 * You can define any number of filters: none, one, many or all parameters can be defined
	 * 
	 * @param int Site ID
	 * @param string (optional) Period to restrict to when looking at the logs
	 * @param string (optional) Date to restrict to
	 * @param int (optional) Number of visits rows to return
	 * @param int (optional) Maximum idvisit to restrict the query to (useful when paginating)
	 * @param int (optional) Minimum timestamp to restrict the query to (useful when paginating or refreshing visits)
	 * 
	 * @return Piwik_DataTable
	 */
	public function getLastVisitsDetails( $idSite, $period = false, $date = false, $segment = false, $filter_limit = false, $maxIdVisit = false, $minTimestamp = false )
	{
		if(empty($filter_limit)) 
		{
			$filter_limit = 10;
		}
		Piwik::checkUserHasViewAccess($idSite);
		$visitorDetails = $this->loadLastVisitorDetailsFromDatabase($idSite, $period, $date, $segment, $filter_limit, $maxIdVisit, $visitorId = false, $minTimestamp); 
		$dataTable = $this->getCleanedVisitorsFromDetails($visitorDetails, $idSite);
		return $dataTable;
	}

	/**
	 * For an array of visits, query the list of pages for this visit 
	 * as well as make the data human readable
	 */
	private function getCleanedVisitorsFromDetails($visitorDetails, $idSite)
	{
		$table = new Piwik_DataTable();

		$site = new Piwik_Site($idSite);
		$timezone = $site->getTimezone();
		foreach($visitorDetails as $visitorDetail)
		{
			$this->cleanVisitorDetails($visitorDetail, $idSite);
			$visitor = new Piwik_LeadLabs_Visitor($visitorDetail);
			$visitorDetailsArray = $visitor->getAllVisitorDetails();

			$visitorDetailsArray['serverTimestamp'] = $visitorDetailsArray['lastActionTimestamp'];
			$dateTimeVisit = Piwik_Date::factory($visitorDetailsArray['lastActionTimestamp'], $timezone);
			
			$dateTimeVisitFirstAction = Piwik_Date::factory($visitorDetailsArray['firstActionTimestamp'], $timezone);
			$visitorDetailsArray['serverDatePrettyFirstAction'] = $dateTimeVisitFirstAction->getLocalized('%shortDay% %day% %shortMonth%');
			$visitorDetailsArray['serverTimePrettyFirstAction'] = $dateTimeVisitFirstAction->getLocalized('%time%');
			
			$idvisit = $visitorDetailsArray['idVisit'];

			$sqlCustomVariables = '';
			for($i = 1; $i <= Piwik_Tracker::MAX_CUSTOM_VARIABLES; $i++)
			{
				$sqlCustomVariables .= ', custom_var_k' . $i . ', custom_var_v' . $i;
			}
			// The second join is a LEFT join to allow returning records that don't have a matching page title
			// eg. Downloads, Outlinks. For these, idaction_name is set to 0
			$sql = "
				SELECT
					log_action.type as type,
					log_action.name AS url,
					log_action_title.name AS pageTitle,
					log_action.idaction AS pageIdAction,
					log_link_visit_action.idlink_va AS pageId,
					log_link_visit_action.server_time as serverTimePretty
					$sqlCustomVariables
				FROM " .Piwik_Common::prefixTable('log_link_visit_action')." AS log_link_visit_action
					INNER JOIN " .Piwik_Common::prefixTable('log_action')." AS log_action
					ON  log_link_visit_action.idaction_url = log_action.idaction
					LEFT JOIN " .Piwik_Common::prefixTable('log_action')." AS log_action_title
					ON  log_link_visit_action.idaction_name = log_action_title.idaction
				WHERE log_link_visit_action.idvisit = ?
				 ";
			$actionDetails = Piwik_FetchAll($sql, array($idvisit));
			
			foreach($actionDetails as &$actionDetail)
			{
				$customVariablesPage = array();
				for($i = 1; $i <= Piwik_Tracker::MAX_CUSTOM_VARIABLES; $i++)
				{
					if(!empty($actionDetail['custom_var_k'.$i])
						&& !empty($actionDetail['custom_var_v'.$i]))
					{
						$customVariablesPage[$i] = array(
							'customVariableName'.$i => $actionDetail['custom_var_k'.$i],
							'customVariableValue'.$i => $actionDetail['custom_var_v'.$i],
						);
					}
					unset($actionDetail['custom_var_k'.$i]);
					unset($actionDetail['custom_var_v'.$i]);
				}
				if(!empty($customVariablesPage))
				{
					$actionDetail['customVariables'] = $customVariablesPage;
				}
			}

			$actions = $actionDetails;
			
			usort($actions, array($this, 'sortByServerTime'));
			
			$visitorDetailsArray['actionDetails'] = $actions;   
			// Convert datetimes to the site timezone
			foreach($visitorDetailsArray['actionDetails'] as &$details)
			{
				switch($details['type'])
				{
					case Piwik_Tracker_Action_Interface::TYPE_DOWNLOAD:
						$details['type'] = 'download';
					break;
					case Piwik_Tracker_Action_Interface::TYPE_OUTLINK:
						$details['type'] = 'outlink';
					break;
					default:
						$details['type'] = 'action';
					break;
				}
				$dateTimeVisit = Piwik_Date::factory($details['serverTimePretty'], $timezone);
				$details['serverTimePretty'] = $dateTimeVisit->getLocalized('%shortDay% %day% %shortMonth% %time%'); 
			}
			
			$table->addRowFromArray( array(Piwik_DataTable_Row::COLUMNS => $visitorDetailsArray));
		}
		return $table;
	}

	private function sortByServerTime($a, $b)
	{
		$ta = strtotime($a['serverTimePretty']);
		$tb = strtotime($b['serverTimePretty']);
		return $ta < $tb 
					? -1 
					: ($ta == $tb 
						? 0 
						: 1 ); 
	}
	
	private function loadLastVisitorDetailsFromDatabase($idSite, $period = false, $date = false, $segment = false, $filter_limit = false, $maxIdVisit = false, $visitorId = false, $minTimestamp = false)
	{
//		var_dump($period); var_dump($date); var_dump($filter_limit); var_dump($maxIdVisit); var_dump($visitorId);
//var_dump($minTimestamp);
		if(empty($filter_limit))
		{
			$filter_limit = 100;
		}
		$where = $whereBind = array();
		$where[] = "log_visit.idsite = ? ";
		$whereBind[] = $idSite;
		$orderBy = "idsite, visit_last_action_time DESC";
		$orderByParent = "sub.visit_last_action_time DESC";
		if(!empty($visitorId))
		{
			$where[] = "log_visit.idvisitor = ? ";
			$whereBind[] = @Piwik_Common::hex2bin($visitorId);
		}

		if(!empty($maxIdVisit))
		{
			$where[] = "log_visit.idvisit < ? ";
			$whereBind[] = $maxIdVisit;
			$orderBy = "idvisit DESC";
			$orderByParent = "sub.idvisit DESC";
		}
		
		if(!empty($minTimestamp))
		{
			$where[] = "log_visit.visit_last_action_time > ? ";
			$whereBind[] = date("Y-m-d H:i:s", $minTimestamp);
		}
		
		// If no other filter, only look at the last 24 hours of stats
		if(empty($visitorId)
			&& empty($maxIdVisit)
			&& empty($period) 
			&& empty($date))
		{
			$period = 'day';
			$date = 'yesterdaySameTime';
		}

		// SQL Filter with provided period
		if (!empty($period) && !empty($date))
		{
			$currentSite = new Piwik_Site($idSite);
			$currentTimezone = $currentSite->getTimezone();
		
			$dateString = $date;
			if($period == 'range') 
			{ 
				$processedPeriod = new Piwik_Period_Range('range', $date);
				if($parsedDate = Piwik_Period_Range::parseDateRange($date))
				{
					$dateString = $parsedDate[2];
				}
			}
			else
			{
				$processedDate = Piwik_Date::factory($date);
				if($date == 'today'
					|| $date == 'now'
					|| $processedDate->toString() == Piwik_Date::factory('now', $currentTimezone)->toString())
				{
					$processedDate = $processedDate->subDay(1);
				}
				$processedPeriod = Piwik_Period::factory($period, $processedDate); 
			}
			$dateStart = $processedPeriod->getDateStart()->setTimezone($currentTimezone);
			$where[] = "log_visit.visit_last_action_time >= ?";
			$whereBind[] = $dateStart->toString('Y-m-d H:i:s');
			
			if(!in_array($date, array('now', 'today', 'yesterdaySameTime'))
				&& strpos($date, 'last') === false
				&& strpos($date, 'previous') === false
				&& Piwik_Date::factory($dateString)->toString('Y-m-d') != Piwik_Date::factory('now', $currentTimezone)->toString())
			{
				$dateEnd = $processedPeriod->getDateEnd()->setTimezone($currentTimezone);
				$where[] = " log_visit.visit_last_action_time <= ?";
				$dateEndString = $dateEnd->addDay(1)->toString('Y-m-d H:i:s');
				$whereBind[] = $dateEndString;
			}
		}

		if(count($where) > 0)
		{
			$where = join(" 
				AND ", $where);
		}
		else
		{
			$where = false;
		}

		$segment = new Piwik_Segment($segment, $idSite);
		
		// Subquery to use the indexes for ORDER BY
		$select = "log_visit.*";
		$from = "log_visit";
		$subQuery = $segment->getSelectQuery($select, $from, $where, $whereBind, $orderBy);
		
		$sqlLimit = $filter_limit >= 1 ? " LIMIT ".(int)$filter_limit : "";
		
		// Group by idvisit so that a visitor converting 2 goals only appears once
		$sql = "
			SELECT sub.* 
			FROM ( 
				".$subQuery['sql']."
				$sqlLimit
			) AS sub
			GROUP BY sub.idvisit
			ORDER BY $orderByParent
		"; 
		
		try {
			$data = Piwik_FetchAll($sql, $subQuery['bind']);
		} catch(Exception $e) {
			echo $e->getMessage();exit;
		}
		
//var_dump($whereBind);	echo($sql);
//var_dump($data);
		return $data;
	}
	

	/**
	 * Removes fields that are not meant to be displayed (md5 config hash)
	 * Or that the user should only access if he is super user or admin (cookie, IP)
	 *
	 * @return void
	 */
	private function cleanVisitorDetails( &$visitorDetails, $idSite )
	{
		$toUnset = array('config_id');
		if(Piwik::isUserIsAnonymous())
		{
			$toUnset[] = 'idvisitor';
			$toUnset[] = 'location_ip';
		}
		foreach($toUnset as $keyName)
		{
			if(isset($visitorDetails[$keyName]))
			{
				unset($visitorDetails[$keyName]);
			}
		}
	}
}
