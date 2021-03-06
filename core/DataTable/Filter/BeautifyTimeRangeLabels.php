<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id$
 * 
 * @category Piwik
 * @package Piwik
 */

/**
 * A DataTable filter replaces range labels that are in seconds with
 * prettier, human-friendlier versions.
 *
 * This filter customizes the behavior of the BeautifyRangeLabels filter
 * so range values that span values that are less than one minute are
 * displayed in seconds but other ranges are displayed in minutes.
 */
class Piwik_DataTable_Filter_BeautifyTimeRangeLabels extends Piwik_DataTable_Filter_BeautifyRangeLabels
{
	/**
	 * A format string used to create pretty range labels when the range's
	 * lower bound is between 0 and 60.
	 * 
	 * This format string must take two numeric parameters, one for each
	 * range bound.
	 */
	protected $labelSecondsPlural;

	/**
	 * Constructor.
	 *
	 * @param Piwik_DataTable $table The DataTable this filter will run over.
	 * @param string $labelSecondsPlural A string to use when beautifying range labels
	 *                                   whose lower bound is between 0 and 60. Must be
	 *                                   a format string that takes two numeric params.
	 * @param string $labelMinutesSingular A string to use when replacing a range that
	 *                                     equals 60-60 (or 1 minute - 1 minute).
	 * @param string $labelMinutesPlural A string to use when replacing a range that
	 *                                   spans multiple minutes. This must be a
	 *                                   format string that takes one string parameter.
	 */
	public function __construct( $table, $labelSecondsPlural, $labelMinutesSingular, $labelMinutesPlural )
	{
		parent::__construct($table, $labelMinutesSingular, $labelMinutesPlural);
		
		$this->labelSecondsPlural = $labelSecondsPlural;
	}

	/**
	 * Beautifies and returns a range label whose range spans over one unit, ie
	 * 1-1, 2-2 or 3-3.
	 *
	 * If the lower bound of the range is less than 60 the pretty range label
	 * will be in seconds. Otherwise, it will be in minutes.
	 *
	 * @param string $oldLabel The original label value.
	 * @param int $lowerBound The lower bound of the range.
	 * @return string The pretty range label.
	 */
	public function getSingleUnitLabel( $oldLabel, $lowerBound )
	{
		if ($lowerBound < 60)
		{
			return sprintf($this->labelSecondsPlural, $lowerBound, $lowerBound);
		}
		else if ($lowerBound == 60)
		{
			return $this->labelSingular;
		}
		else
		{
			return sprintf($this->labelPlural, ceil($lowerBound / 60));
		}
	}
	
	/**
	 * Beautifies and returns a range label whose range is bounded and spans over
	 * more than one unit, ie 1-5, 5-10 but NOT 11+.
	 *
	 * If the lower bound of the range is less than 60 the pretty range label
	 * will be in seconds. Otherwise, it will be in minutes.
	 *
	 * @param string $oldLabel The original label value.
	 * @param int $lowerBound The lower bound of the range.
	 * @param int $upperBound The upper bound of the range.
	 * @return string The pretty range label.
	 */
	public function getRangeLabel( $oldLabel, $lowerBound, $upperBound )
	{
		if ($lowerBound < 60)
		{
			return sprintf($this->labelSecondsPlural, $lowerBound, $upperBound);
		}
		else
		{
			return sprintf($this->labelPlural, ceil($lowerBound / 60)."-".ceil($upperBound / 60));
		}
	}

	/**
	 * Beautifies and returns a range label whose range is unbounded, ie
	 * 5+, 10+, etc.
	 *
	 * If the lower bound of the range is less than 60 the pretty range label
	 * will be in seconds. Otherwise, it will be in minutes.
	 * 
	 * @param string $oldLabel The original label value.
	 * @param int $lowerBound The lower bound of the range.
	 * @return string The pretty range label.
	 */
	public function getUnboundedLabel( $oldLabel, $lowerBound )
	{
		if ($lowerBound < 60)
		{
			return sprintf($this->labelSecondsPlural, $lowerBound);
		}
		else
		{
			return sprintf($this->labelPlural, "".ceil($lowerBound / 60).urlencode('+'));
		}
	}
}
