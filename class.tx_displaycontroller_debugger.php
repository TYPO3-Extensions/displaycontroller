<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Francois Suter (Cobweb) <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * Debugging output for the 'displaycontroller' extension.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_displaycontroller
 *
 * $Id$
 */
class tx_displaycontroller_debugger implements t3lib_Singleton {
	/**
	 * @var t3lib_PageRenderer Reference to the current page renderer object
	 */
	protected $pageRenderer;
	/**
	 * @var bool Flag to control output of unique content
	 */
	protected $firstCall = TRUE;
	/**
	 * @var string Inline CSS code
	 */
	protected $cssCode = '';
	/**
	 * @var array Flash message class names
	 */
	protected $severityClasses;
	/**
	 * @var int Dump variable counter across all calls
	 */
	protected $counter = 1;

	public function __construct(t3lib_PageRenderer $pageRenderer) {
		$this->pageRenderer = $pageRenderer;
		// Prepare CSS code based on t3skin, if loaded
		if (t3lib_extMgm::isLoaded('t3skin')) {
			$this->cssCode = t3lib_div::getUrl(t3lib_extMgm::extPath('displaycontroller') . 'Resources/Public/Styles/Debugger.css');
			$t3SkinPath = t3lib_extMgm::extPath('t3skin');
			if (version_compare(TYPO3_branch, '6.2', '>=')) {
				$messageSkinningFile = $t3SkinPath  . 'Resources/Public/Css/visual/element_message.css';
				$pathToReplace = '../../../../icons';
			} else {
				$messageSkinningFile = $t3SkinPath  . 'stylesheets/visual/element_message.css';
				$pathToReplace = '../../icons';
			}
			$this->cssCode .= t3lib_div::getUrl($messageSkinningFile);
			// Adjust path to icons
			$replacement = t3lib_div::locationHeaderUrl(TYPO3_mainDir . t3lib_extMgm::extRelPath('t3skin') . 'icons');
			$this->cssCode = str_replace($pathToReplace, $replacement, $this->cssCode);
		}
		// Compatibility only for TYPO3 4.5, @see getMessageClass() below
		if (strpos(TYPO3_version, '4.5') !== FALSE) {
			$this->severityClasses = array(
				t3lib_FlashMessage::NOTICE =>  'notice',
				t3lib_FlashMessage::INFO =>    'information',
				t3lib_FlashMessage::OK =>      'ok',
				t3lib_FlashMessage::WARNING => 'warning',
				t3lib_FlashMessage::ERROR =>   'error',
			);
		}
	}

	/**
	 * Renders all messages and dumps their related data
	 *
	 * @param array $messageQueue List of messages to display
	 * @return string Debug output
	 */
	public function render(array $messageQueue) {
		$debugOutput = '';
		if (count($messageQueue) > 0) {
			// If this is the first debug call, write the necessary CSS code
			if ($this->firstCall) {
				$debugOutput .= '<style>' . $this->cssCode . '</style>';
				$this->firstCall = FALSE;
			}
			// Prepare the output and return it
			$script = '';
			$icons = '';
			foreach ($messageQueue as $messageData) {
				/** @var \TYPO3\CMS\Core\Messaging\FlashMessage $messageObject */
				$messageObject = $messageData['message'];
				// Prepare all the data to dump in JS global variables
				$dumpVariable = '';
				if ($messageData['data'] !== NULL) {
					$dumpVariable = 'dump' . $this->counter;
					$script .= 'var ' . $dumpVariable . ' = ' . json_encode($messageData['data']) . ';';
					$this->counter++;
				}
				// Choose the log method based on severity
				switch ($messageObject->getSeverity()) {
					case 2:
						$logMethod = 'error';
						break;
					case 1:
						$logMethod = 'warn';
						break;
					default:
						$logMethod = 'log';
				}
				// Prepare the output, as a clickable icon and a message
				$label = '<p><strong>' . $messageObject->getTitle() . '</strong>: ' . $messageObject->getMessage() . '</p>';
				$debugLink = '
					<a class="debug-message ' . $this->getMessageClass($messageObject) . '" onclick="console.' . $logMethod .
					'(\'' . $messageObject->getTitle() . ': ' . $messageObject->getMessage() . '\'' . ((empty($dumpVariable)) ? '' : ', ' . $dumpVariable) . '); return false;">&nbsp;</a>
				';
				$icons .= '<div class="icon-group">' . $debugLink . $label . '</div>';
			}
			// Assemble the whole output
			$debugOutput .= '<div class="tx_displaycontroller_debug"><script type="text/javascript">' . $script . '</script>' . $icons . '</div>';
		}

		return $debugOutput;
	}

	/**
	 * Returns the CSS class name corresponding to the message severity.
	 *
	 * This class is a compatibility layer for TYPO3 4.5, where t3lib_FlashMessage didn't yet have
	 * a getClass() method.
	 *
	 * @param t3lib_FlashMessage $message Message object
	 * @return string CSS class name corresponding to the severity
	 */
	protected function getMessageClass($message) {
		if (strpos(TYPO3_version, '4.5') !== FALSE) {
			return 'message-' . $this->severityClasses[$message->getSeverity()];
		} else {
			return $message->getClass();
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller_debugger.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller_debugger.php']);
}

?>