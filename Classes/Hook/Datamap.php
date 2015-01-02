<?php 

class Tx_CzSimpleCal_Hook_Datamap {

	/**
	 * @var Tx_CzSimpleCal_Domain_Repository_EventRepository
	 */
	protected $eventRepository;
	
	/**
	 * the extbase framework is not initialized in the constructor anymore
	 * because initializing the framework is costy
	 * and this class is *always* instanciated when *any* record
	 * is created or updated
	 */
	public function __construct() {
		/* don't do any extbasy stuff here! */
	}
	
	/**
	 * implements the hook processDatamap_afterDatabaseOperations that gets invoked
	 * when a form in the backend was saved and written to the database.
	 * 
	 * Here we will do the caching of recurring events
	 * 
	 * @param string $status
	 * @param string $table
	 * @param integer $id
	 * @param array $fieldArray
	 * @param t3lib_TCEmain $tce
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $tce) {
		$GLOBALS['LANG']->includeLLFile('EXT:cz_simple_cal/Resources/Private/Language/locallang_mod.xml');
		if ($table == 'tx_czsimplecal_domain_model_event') {
			//if: an event was changed
			
			if($status == 'new') {
				// if: record is new
				$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Object_ObjectManager');
				$indexer = $objectManager->get('Tx_CzSimpleCal_Indexer_Event');
				
				// get the uid of the new record
				if(!is_numeric($id)) {
					$id = $tce->substNEWwithIDs[$id];
				}
				
				// create the slug
				
				$event = $this->fetchEventObject($id);
				$event->generateSlug();
				$this->getEventRepository()->update($event);
				
				// index events
				$indexer->create($event);
				
				$this->addFlashMessage($GLOBALS['LANG']->getLL('flashmessages.tx_czsimplecal_domain_model_event.create'),'', \TYPO3\CMS\Core\Messaging\FlashMessage::OK);

				
				
			} else {
				if($this->haveFieldsChanged(Tx_CzSimpleCal_Domain_Model_Event::getFieldsRequiringReindexing(), $fieldArray)) {
					//if: record was updated and a value that requires re-indexing was changed
					$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Object_ObjectManager');
					$indexer = $objectManager->get('Tx_CzSimpleCal_Indexer_Event');
					$indexer->update($id);
					$this->addFlashMessage($GLOBALS['LANG']->getLL('flashmessages.tx_czsimplecal_domain_model_event.updateAndIndex'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::OK);
					
				} else {
					$this->addFlashMessage($GLOBALS['LANG']->getLL('flashmessages.tx_czsimplecal_domain_model_event.updateNoIndex'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
				}
			}
		}
	}

	protected function addFlashMessage($messageBody, $messageTitle = '', $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK, $storeInSession = TRUE) {
		/* @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $messageBody, $messageTitle, $severity, $storeInSession
		);
		$flashMessageQueue = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue', 'core.template.flashMessages');
		$flashMessageQueue->enqueue($flashMessage);
	}
	
	/**
	 * treat the values before handling by t3lib_TCEmain
	 * 
	 * We replace empty values with our custom NULL values here for dates and times
	 * 
	 * @param array $fieldArray
	 * @param string $table
	 * @param integer $id
	 * @param t3lib_TCEmain $tce
	 */
	public function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, $tce) {
		if($table == 'tx_czsimplecal_domain_model_event' || $table == 'tx_czsimplecal_domain_model_exception') {
			
			foreach(array('start_time', 'end_date', 'end_time', 'recurrance_until') as $fieldName) {
				if(array_key_exists($fieldName, $fieldArray)) {
					/* 
					 * this must be an empty string, not "0"!
					 *  - empty strings are created by the clear field button introduced with TYPO3 4.5 and by deleting a value
					 *  - "0" means midnight, so don't strip it
					 */
					if($fieldArray[$fieldName] === '') {
						$fieldArray[$fieldName] = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['default'];
					}
				}
			}
		}
	}
	
	/**
	 * implement the hook processDatamap_postProcessFieldArray that gets invoked
	 * right before a dataset is written to the database
	 * 
	 * @param $status
	 * @param $table
	 * @param $id
	 * @param $fieldArray
	 * @param $tce
	 * @return null
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, $tce) {
		
		if($table == 'tx_czsimplecal_domain_model_event' || $table == 'tx_czsimplecal_domain_model_exception') {
			// store the timezone to the database
			$fieldArray['timezone'] = date('T');
		}
	}
	
	/**
	 * check if fields have been changed in the record 
	 * 
	 * @param $fields
	 * @return boolean
	 */
	protected function haveFieldsChanged($fields, $inArray) {
		$criticalFields = array_intersect(
			array_keys($inArray),
			$fields
		);
		return !empty($criticalFields);
	}
	
	/**
	 * get an event object by its uid
	 * 
	 * @param integer $id
	 * @throws InvalidArgumentException
	 * @return Tx_CzSimpleCal_Domain_Model_Event
	 */
	protected function fetchEventObject($id) {
		$event = $this->getEventRepository()->findOneByUidEverywhere($id);
		if(empty($event)) {
			$event = $this->getEventRepository()->findByIdentifier($id);
			if(empty($event)) {
				throw new InvalidArgumentException(sprintf('An event with uid %d could not be found.', $id));
			}
		}
		return $event;
	}
	
	
	/**
	 * get the event repository
	 * 
	 * this wrapper is needed so we just initialize the extbase framework if it is needed
	 * 
	 * @see __construct()
	 * @return Tx_CzSimpleCal_Domain_Repository_EventRepository
	 */
	protected function getEventRepository() {
		if(is_null($this->eventRepository)) {
			$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Object_ObjectManager');
			$this->eventRepository = $objectManager->get('Tx_CzSimpleCal_Domain_Repository_EventRepository');
		}
		return $this->eventRepository;
	}
	
}