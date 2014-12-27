<?php 

/**
 * a class that handles indexing of events
 * 
 * @author Christian Zenker <christian.zenker@599media.de>
 */
class Tx_CzSimpleCal_Indexer_Event {
	
	static private
		$eventTable = 'tx_czsimplecal_domain_model_event',
		$eventIndexTable = 'tx_czsimplecal_domain_model_eventindex'
	;
	
	/**
	 * @var Tx_CzSimpleCal_Domain_Repository_EventRepository
	 * @inject
	 */
	protected $eventRepository = null;
	
	/**
	 * @var Tx_CzSimpleCal_Domain_Repository_EventIndexRepository
	 * @inject
	 */
	protected $eventIndexRepository = null;
	
	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 * @inject
	 */
	protected $persistenceManager = null;

	/**
	 * constructor
	 *
	 */
	public function __construct() {
		//\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Dispatcher');
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->eventIndexRepository = $objectManager->get('Tx_CzSimpleCal_Domain_Repository_EventIndexRepository');
		$this->eventRepository = $objectManager->get('Tx_CzSimpleCal_Domain_Repository_EventRepository');
		$this->persistenceManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
	}
	
	/**
	 * destructor
	 * 
	 * this will persist all changes
	 */
	public function __destruct() {
		$this->persistenceManager->persistAll();
	}
	
	/**
	 * create an eventIndex
	 * 
	 * @param integer|Tx_CzSimpleCal_Domain_Model_Event $event
	 */
	public function create($event) {
		if(is_integer($event)) {
			$event = $this->fetchEventObject($event);
		}
		
		$this->doCreate($event);
	}
	
	/**
	 * update an eventIndex
	 * 
	 * @param integer|Tx_CzSimpleCal_Domain_Model_Event $event
	 */
	public function update($event) {
		if(is_integer($event)) {
			$event = $this->fetchEventObject($event);
		}
		
		$this->doDelete($event);
		$this->doCreate($event);
		
	}
	
	/**
	 * delete the eventIndex 
	 * 
	 * @param integer|Tx_CzSimpleCal_Domain_Model_Event $event
	 */
	public function delete($event) {
		if(is_integer($event)) {
			$event = $this->fetchEventObject($event);
		}
		
		$this->doDelete($event);
	}
	
	/**
	 * delete an event
	 * 
	 * @param Tx_CzSimpleCal_Domain_Model_Event $event
	 */
	protected function doDelete($event) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			self::$eventIndexTable,
			'event = '.$event->getUid()
		);
	}
	
	/**
	 * create the indexes
	 * 
	 * @param Tx_CzSimpleCal_Domain_Model_Event $event
	 */
	protected function doCreate($event) {
		$event->setLastIndexed(new DateTime());
		$this->eventRepository->update($event);
		
		if(!$event->isEnabled()) {
			return;
		}
		// get all recurrances...
		foreach($event->getRecurrances() as $recurrance) {
			// ...and store them to the repository
			$instance = Tx_CzSimpleCal_Domain_Model_EventIndex::fromArray(
				$recurrance
			);
			
			$this->eventIndexRepository->add(
				$instance
			);
		}
		
		// store everything to database manually to allow correct unique hash creation when using scheduler
//		$this->persistenceManager->persistAll();
	}
	
	/**
	 * get an event object by its uid
	 * 
	 * @param integer $id
	 * @throws InvalidArgumentException
	 * @return Tx_CzSimpleCal_Domain_Model_Event
	 */
	protected function fetchEventObject($id) {
		$event = $this->eventRepository->findOneByUidEverywhere($id);
		if(empty($event)) {
			throw new InvalidArgumentException(sprintf('An event with uid %d could not be found.', $id));
		}
		return $event;
	}
}