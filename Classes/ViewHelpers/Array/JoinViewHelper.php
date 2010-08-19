<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * 
 *
 * @version $Id: JoinViewHelper.php$
 * @package Fluid
 * @subpackage ViewHelpers
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 *
 */
class Tx_CzSimpleCal_ViewHelpers_Array_JoinViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @param array $items
	 * @param string $by
	 * @param boolean $removeEmpty
	 * @return string Rendered result
	 */
	public function render($items=null, $by=', ', $removeEmpty = false) {
		if(is_null($items)) {
			$items = $this->getItems();
		}
		
		if($removeEmpty) {
			$items = $this->removeEmpty($items);
		}
		
		return implode($by, $items);
	}
	
	/**
	 * get items from the nodes
	 * 
	 * @return array
	 */
	protected function getItems() {
		$viewHelperName = get_class($this);
		$key = 'items';
		
		if($this->viewHelperVariableContainer->exists($viewHelperName, $key)) {
			$temp = $this->viewHelperVariableContainer->get($viewHelperName, $key);
		}
		$this->viewHelperVariableContainer->addOrUpdate($viewHelperName, $key, array());
		
		$this->renderChildren();
		
		$return = $this->viewHelperVariableContainer->get($viewHelperName, $key);
		
		$this->viewHelperVariableContainer->remove($viewHelperName, $key);
		if(isset($temp)) {
			$this->viewHelperVariableContainer->add($viewHelperName, $key, $temp);
		}
		
		return $return;
	}
	
	/**
	 * remove empty values from array
	 * 
	 * @param array $items
	 * @return array
	 */
	protected function removeEmpty($items) {
		foreach($items as $key=>$value) {
			if(empty($value)) {
				unset($items[$key]);
			}
		}
		return $items;
	}

}

?>