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
 *
 * @package Piwik_LeadLabs
 */
class Piwik_LeadLabs extends Piwik_Plugin
{
	public function getInformation()
	{
		return array(
			'description' => Piwik_Translate('LeadLabs_PluginDescription'),
			'author' => 'Piwik',
			'author_homepage' => 'http://piwik.org/',
			'version' => Piwik_Version::VERSION,
		);
	}

	function getListHooksRegistered()
	{
		return array(
			'AssetManager.getJsFiles' => 'getJsFiles',
			'AssetManager.getCssFiles' => 'getCssFiles',
			'WidgetsList.add' => 'addWidget',
			'Menu.add' => 'addMenu',
		);
	}
	
	function getCssFiles( $notification )
	{
		$cssFiles = &$notification->getNotificationObject();
		
		$cssFiles[] = "plugins/LeadLabs/templates/live.css";
	}	
	
	function getJsFiles( $notification )
	{
		$jsFiles = &$notification->getNotificationObject();
		
		$jsFiles[] = "plugins/LeadLabs/templates/scripts/spy.js";
		$jsFiles[] = "plugins/LeadLabs/templates/scripts/live.js";
	}

	function addMenu()
	{
		Piwik_AddMenu('General_Visitors', 'LeadLabs_VisitorLog', array('module' => 'LeadLabs', 'action' => 'getVisitorLog'));
	}

	public function addWidget() 
	{
		Piwik_AddWidget('LeadLabs!', 'LeadLabs_VisitorsInRealTime', 'LeadLabs', 'widget');
	}

}
