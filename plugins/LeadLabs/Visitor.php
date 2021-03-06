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
 * @see plugins/UserCountry/functions.php
 * @see plugins/UserSettings/functions.php
 * @see plugins/Provider/functions.php
 */

require_once PIWIK_INCLUDE_PATH . '/plugins/Referers/functions.php';
require_once PIWIK_INCLUDE_PATH . '/plugins/UserCountry/functions.php';
require_once PIWIK_INCLUDE_PATH . '/plugins/UserSettings/functions.php';
require_once PIWIK_INCLUDE_PATH . '/plugins/Provider/functions.php';

/**
 *
 * @package Piwik_LeadLabs
 */
class Piwik_LeadLabs_Visitor
{
	const DELIMITER_PLUGIN_NAME = ", ";
	
	function __construct($visitorRawData)
	{
		$this->details = $visitorRawData;
	}

	function getAllVisitorDetails()
	{
		return array(
			'idSite' => $this->getIdSite(),
			'idVisit' => $this->getIdVisit(),
			'visitIp' => $this->getIp(),
			'visitorId' => $this->getVisitorId(),
			'visitorType' => $this->getVisitorReturning(),
		
			'actions' => $this->getNumberOfActions(),
			// => false are placeholders to be filled in API later
			'actionDetails' => false,

			// all time entries
			'serverDate' => $this->getServerDate(),
			'visitLocalTime' => $this->getVisitLocalTime(),
			'firstActionTimestamp' => $this->getTimestampFirstAction(),
			'lastActionTimestamp' => $this->getTimestampLastAction(),
			'lastActionDateTime' => $this->getDateTimeLastAction(),
		
			// standard attributes
			'visitDuration' => $this->getVisitLength(),
			'visitDurationPretty' => $this->getVisitLengthPretty(),
			'visitCount' => $this->getVisitCount(),
			'daysSinceLastVisit' => $this->getDaysSinceLastVisit(),
			'daysSinceFirstVisit' => $this->getDaysSinceFirstVisit(),
			'country' => $this->getCountryName(),
			'country_code' => $this->details['location_country'],
			'continent' => $this->getContinent(),
			'provider' => $this->getProvider(),
			'providerUrl' => $this->getProviderUrl(),
			'referrerType' => $this->getRefererType(),
//			'referrerTypeName' => $this->getRefererTypeName(),
			'referrerName' => $this->getRefererName(),
			'referrerKeyword' => $this->getKeyword(),
			'referrerKeywordPosition' => $this->getKeywordPosition(),
			'referrerUrl' => $this->getRefererUrl(),
			'referrerSearchEngineUrl' => $this->getSearchEngineUrl(),
//			'operatingSystem' => $this->getOperatingSystem(),
			'operatingSystemShortName' => $this->getOperatingSystemShortName(),
//			'browserFamily' => $this->getBrowserFamily(),
//			'browserFamilyDescription' => $this->getBrowserFamilyDescription(),
	 		'browserName' => $this->getBrowser(),
//			'screenType' => $this->getScreenType(),
//			'resolution' => $this->getResolution(),
//			'plugins' => $this->getPlugins(),
		);
	}

	function getVisitorId()
	{
		if(isset($this->details['idvisitor']))
		{
			return bin2hex($this->details['idvisitor']);
		}
		return false;
	}
	
	function getVisitLocalTime()
	{
		return $this->details['visitor_localtime'];
	}
	
	function getVisitCount()
	{
		return $this->details['visitor_count_visits'];
	}
	
	function getDaysSinceLastVisit()
	{
		return $this->details['visitor_days_since_last'];
	}
	
	function getDaysSinceLastEcommerceOrder()
	{
		return $this->details['visitor_days_since_order'];
	}
	function getDaysSinceFirstVisit()
	{
		return $this->details['visitor_days_since_first'];
	}
	
	function getServerDate()
	{
		return date('Y-m-d', strtotime($this->details['visit_last_action_time']));
	}

	function getIp()
	{
		if(isset($this->details['location_ip']))
		{
			return Piwik_IP::N2P($this->details['location_ip']);
		}
		return false;
	}

	function getIdVisit()
	{
		return $this->details['idvisit'];
	}

	function getIdSite()
	{
		return $this->details['idsite'];
	}
	
	function getNumberOfActions()
	{
		return $this->details['visit_total_actions'];
	}

	function getVisitLength()
	{
		return $this->details['visit_total_time'];
	}

	function getVisitLengthPretty()
	{
		return Piwik::getPrettyTimeFromSeconds($this->details['visit_total_time']);
	}

	function getVisitorReturning()
	{
		$type = $this->details['visitor_returning'];
		 return $type == 2 
		 		? 'returningCustomer' 
		 		: ($type == 1 
		 			? 'returning' 
		 			: 'new');
	}

	function getTimestampFirstAction()
	{
		return strtotime($this->details['visit_first_action_time']);
	}

	function getTimestampLastAction()
	{
		return strtotime($this->details['visit_last_action_time']);
	}

	function getCountryName()
	{
		return Piwik_CountryTranslate($this->details['location_country']);
	}

	function getCountryFlag()
	{
		return Piwik_getFlagFromCode($this->details['location_country']);
	}

	function getContinent()
	{
		return Piwik_ContinentTranslate($this->details['location_continent']);
	}

	function getCustomVariables()
	{
		$customVariables = array();
		for($i = 1; $i <= Piwik_Tracker::MAX_CUSTOM_VARIABLES; $i++)
		{
			if(!empty($this->details['custom_var_k'.$i])
				&& !empty($this->details['custom_var_v'.$i]))
			{
				$customVariables[$i] = array(
					'customVariableName'.$i => $this->details['custom_var_k'.$i],
					'customVariableValue'.$i => $this->details['custom_var_v'.$i],
				);
			}
		}
		return $customVariables;
	}
	
	function getRefererType()
	{
	    return Piwik_getRefererTypeFromShortName($this->details['referer_type']);
	}

	function getRefererTypeName()
	{
		return Piwik_getRefererTypeLabel($this->details['referer_type']);
	}

	function getKeyword()
	{
		return urldecode($this->details['referer_keyword']);
	}

	function getRefererUrl()
	{
		return $this->details['referer_url'];
	}
	
	function getKeywordPosition()
	{
		if($this->getRefererType() == 'search'
			&& strpos($this->getRefererName(), 'Google') !== false)
		{
			$url = $this->getRefererUrl();
			$url = @parse_url($url);
			if(empty($url['query']))
			{
				return null;
			}
			$position = Piwik_Common::getParameterFromQueryString($url['query'], 'cd');
			if(!empty($position))
			{
				return $position;
			}
		}
		return null;
	}

	function getRefererName()
	{
		return urldecode($this->details['referer_name']);
	}

	function getSearchEngineUrl()
	{
		if($this->getRefererType() == 'search'
		    && !empty($this->details['referer_name']))
		{
			return Piwik_getSearchEngineUrlFromName($this->details['referer_name']);
		}
		return null;
	}

	function getPlugins()
	{
		$plugins = array(
	 		'config_pdf',
	 		'config_flash',
	 		'config_java',
	 		'config_director',
	 		'config_quicktime',
	 		'config_realplayer',
	 		'config_windowsmedia',
	 		'config_gears',
	 		'config_silverlight',
		);
		$pluginShortNames = array();
		foreach($plugins as $plugin)
		{
			if($this->details[$plugin] == 1)
			{
				$pluginShortName = substr($plugin, 7);
				$pluginShortNames[] = $pluginShortName;
			}
		}
		return implode(self::DELIMITER_PLUGIN_NAME, $pluginShortNames);
	}

	function getOperatingSystem()
	{
		return Piwik_getOSLabel($this->details['config_os']);
	}

	function getOperatingSystemShortName()
	{
		return Piwik_getOSShortLabel($this->details['config_os']);
	}

	function getBrowserFamilyDescription()
	{
		return Piwik_getBrowserTypeLabel($this->getBrowserFamily());
	}

	function getBrowserFamily()
	{
		return Piwik_getBrowserFamily($this->details['config_browser_name']);
	}

	function getBrowser()
	{
		return Piwik_getBrowserLabel($this->details['config_browser_name'] . ";" . $this->details['config_browser_version']);
	}

	function getScreenType()
	{
		return Piwik_getScreenTypeFromResolution($this->details['config_resolution']);
	}

	function getResolution()
	{
		return $this->details['config_resolution'];
	}

	function getProvider()
	{
		return Piwik_getHostnameName( @$this->details['location_provider']);
	}

	function getProviderUrl()
	{
		return Piwik_getHostnameUrl( @$this->details['location_provider']);
	}

	function getDateTimeLastAction()
	{
		return date('c', strtotime($this->details['visit_last_action_time']));
	}

	
	function getVisitEcommerceStatus()
	{
		return Piwik_API_API::getVisitEcommerceStatusFromId($this->details['visit_goal_buyer']);
	}
	
	function isVisitorGoalConverted()
	{
		return $this->details['visit_goal_converted'];
	}
}
