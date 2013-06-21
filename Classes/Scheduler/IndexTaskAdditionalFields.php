<?php

use \TYPO3\CMS\Scheduler;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Party".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

class Tx_CzSimpleCal_Scheduler_IndexTaskAdditionalFields implements Scheduler\AdditionalFieldProviderInterface {
/*
 *
 * implement the tx_scheduler_AdditionalFieldProvider interface
 *
 *
 */

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param	array Values of the fields from the add/edit task form
	 * @param	\TYPO3\CMS\Scheduler\Task\AbstractTask		The task object being eddited. Null when adding a task!
	 * @param	\TYPO3\CMS\Scheduler\Controller\SchedulerModuleController		Reference to the scheduler backend module
	 * @return	array					A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, Scheduler\Task\AbstractTask $task, Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$additionalFields = array();

		if (empty($taskInfo['tx_czsimplecal_minindexage'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['tx_czsimplecal_minindexage'] = $this->minIndexAge; // $task will be null at this point
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['tx_czsimplecal_minindexage'] = $task->minIndexAge;
			} else {
				$taskInfo['tx_czsimplecal_minindexage'] = '';
			}
		}

			// Write the code for the field
		$fieldCode = sprintf(
			'<input type="text" name="tx_scheduler[tx_czsimplecal_minindexage]" id="tx_czsimplecal_minindexage" value="%s" size="30" />',
			htmlspecialchars($taskInfo['tx_czsimplecal_minindexage'])
		);
		$additionalFields[$fieldId] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:cz_simple_cal/Resources/Private/Language/locallang_mod.xml:tx_czsimplecal_scheduler_index.minindexage.label',
			'cshKey'   => '',
			'cshLabel' => 'tx_czsimplecal_minindexage'
		);

		return $additionalFields;
	}

	/**
	 * Validates the additional fields' values
	 *
	 * @param	array					An array containing the data submitted by the add/edit task form
	 * @param	\TYPO3\CMS\Scheduler\Controller\SchedulerModuleController		Reference to the scheduler backend module
	 * @return	boolean					True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		if(empty($submittedData['tx_czsimplecal_minindexage'])) {
			$submittedData['tx_czsimplecal_minindexage'] = null;
		} elseif(!is_string($submittedData['tx_czsimplecal_minindexage'])) {
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:cz_simple_cal/Resources/Private/Language/locallang_mod.xml:tx_czsimplecal_scheduler_index.minindexage.nostring'),
				t3lib_FlashMessage::ERROR
			);
			return false;
		} else {
			if(Tx_CzSimpleCal_Utility_StrToTime::strtotime($submittedData['tx_czsimplecal_minindexage']) === false) {
				$schedulerModule->addMessage(
					sprintf(
						$GLOBALS['LANG']->sL('LLL:EXT:cz_simple_cal/Resources/Private/Language/locallang_mod.xml:tx_czsimplecal_scheduler_index.minindexage.parseerror'),
						$submittedData['tx_czsimplecal_minindexage']
					),
					t3lib_FlashMessage::ERROR
				);
				return false;
			}
		}
		return true;
	}

	/**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param	array					An array containing the data submitted by the add/edit task form
	 * @param	\TYPO3\CMS\Scheduler\Task\AbstractTask		Reference to the scheduler backend module
	 * @return	void
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task){
		$task->minIndexAge = $submittedData['tx_czsimplecal_minindexage'];
	}
}
