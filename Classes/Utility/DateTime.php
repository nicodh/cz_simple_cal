<?php 

/**
 * an extension of the DateTime object
 * 
 * @author Christian Zenker <christian.zenker@599media.de>
 */
class Tx_CzSimpleCal_Utility_DateTime extends DateTime {

	public function __construct($dateTime = null, $timezone = null) {
		$time = Tx_CzSimpleCal_Utility_StrToTime::doSubstitutions($dateTime);
		
		$time = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $time, true);
		
		$thisTime = Tx_CzSimpleCal_Utility_StrToTime::strftime(array_shift($time));
		
		if(is_null($timezone)) {
			parent::__construct($thisTime);
		} else {
			parent::__construct($thisTime, $timezone);
		}
		
		if(!empty($time)) {
			$this->doModify($time);
		}
	}
	
//	protected function doModify($modify) {
//		var_dump(strftime($modify, $this->getTimestamp()));
//		
//		$time = strtotime(strftime($modify, $this->getTimestamp()), $this->getTimestamp());
//	}
	
	
	/**
	 * apply modifications on the date
	 * @param array $dateTime
	 */
	protected function doModify($time) {		
		$timezone = date_default_timezone_get();
		$timezonename = $this->getTimezone()->getName();
		if($timezonename === '+00:00') {
			//generated by "@1234567890" notation
			$timezonename = 'UTC';
		} else {
			$temp = timezone_name_from_abbr($timezonename);
			if($temp !== false) {
				$timezonename = $temp;
			}
		}
		date_default_timezone_set($timezonename);
		
		
		$now = $this->getTimestamp();
		
		foreach($time as $part) {
			$now = strtotime(strftime($part, $now), $now);
		}
		$this->setDate(date('Y', $now), date('n', $now), date('j', $now));
		$this->setTime(date('H', $now), date('i', $now), date('s', $now));
		
		date_default_timezone_set($timezone);
	}

	public function modify($dateTime) {
		$time = Tx_CzSimpleCal_Utility_StrToTime::doSubstitutions($dateTime);
		$time = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $time, true);
		$this->doModify($time);
	}
//	
	public function getTimestamp() {
		return $this->format('U');
	}
	
}