<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Dmitry Dulepov (dmitry@typo3.org)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This file contains a class with page browser implementation.
 *
 * @author	Dmitry Dulepov [netcreators] <dmitry@typo3.org>
 */


/**
 * This class implements page browser plugin
 *
 * @author	Dmitry Dulepov [netcreators] <dmitry@typo3.org>
 */
class tx_pagebrowse_pi1 extends tslib_pibase {
	// Default plugin variables:
	public $prefixId = 'tx_pagebrowse_pi1';
	public $scriptRelPath = 'pi1/class.tx_pagebrowse_pi1.php';
	public $extKey = 'pagebrowse';
	public $pi_checkCHash = true;				// Required for proper caching! See in the typo3/sysext/cms/tslib/class.tslib_pibase.php
	public $pi_USER_INT_obj = false;

	protected $numberOfPages;
	protected $pageParameterName;
	protected $currentPage;
	protected $pagesBefore = 3;
	protected $pagesAfter = 3;

	const PAGE_FIRST = 0;
	const PAGE_PREV = 1;
	const PAGE_BEFORE = 2;
	const PAGE_CURRENT = 3;
	const PAGE_AFTER = 4;
	const PAGE_NEXT = 5;
	const PAGE_LAST = 6;

	/**
	 * Produces plugin's output.
	 *
	 * @param	string	$content	Unused
	 * @param	array	$conf	Configuration
	 * @return	string	Generated content
	 */
	public function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_loadLL();

		if (!isset($conf['templateFile'])) {
			return $this->pi_wrapInBaseClass($this->pi_getLL('no_ts_template'));
		}

		$this->init();
		return $this->pi_wrapInBaseClass($this->createPageBrowser());
	}

	/**
	 * Initializes the plugin.
	 *
	 * @return	void
	 */
	function init() {
		// Call pre-init hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preInit'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['preInit'] as $userFunc) {
				$params = array(
					'pObj' => &$this,
				);
				t3lib_div::callUserFunction($userFunc, $params, $this);
			}
		}

		$this->numberOfPages = intval($this->cObj->stdWrap($this->conf['numberOfPages'], $this->conf['numberOfPages.']));
		if (!($pageParameterName = trim($this->conf['pageParameterName']))) {
			$this->pageParameterName = $this->prefixId . '[page]';
			$this->currentPage = max(0, intval($this->piVars['page']));
		}
		else {
			$parts = t3lib_div::trimExplode('|', $pageParameterName, 2);
			if (count($parts) == 2) {
				$this->pageParameterName = $parts[0] . '[' . $parts[1] . ']';
				$vars = t3lib_div::_GP($parts[0]);
				$this->currentPage = max(0, intval($vars[$parts[1]]));
			}
			else {
				$this->pageParameterName = $pageParameterName;
				$this->currentPage = max(0, intval(t3lib_div::_GP($pageParameterName)));
			}
		}

		if (self::testInt($this->conf['pagesBefore'])) {
			$this->pagesBefore = intval($this->conf['pagesBefore']);
		}
		if (self::testInt($this->conf['pagesAfter'])) {
			$this->pagesAfter = intval($this->conf['pagesAfter']);
		}

		$this->adjustForForcedNumberOfLinks();

		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);

		// Call post-init hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['postInit'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['postInit'] as $userFunc) {
				$params = array(
					'pObj' => &$this,
				);
				t3lib_div::callUserFunction($userFunc, $params, $this);
			}
		}

		$this->addHeaderParts();
	}

	/**
	 * If a certain number of links should be displayed, adjust before and after
	 * amounts accordingly.
	 *
	 * @return void
	 */
	protected function adjustForForcedNumberOfLinks() {
		$forcedNumberOfLinks = intval($this->cObj->stdWrap($this->conf['numberOfLinks'], $this->conf['numberOfLinks.']));
		if ($forcedNumberOfLinks > $this->numberOfPages) {
			$forcedNumberOfLinks = $this->numberOfPages;
		}
		$totalNumberOfLinks = min($this->currentPage, $this->pagesBefore) +
				min($this->pagesAfter, $this->numberOfPages - $this->currentPage) + 1;
		if ($totalNumberOfLinks <= $forcedNumberOfLinks) {
			$delta = intval(ceil(($forcedNumberOfLinks - $totalNumberOfLinks)/2));
			$incr = ($forcedNumberOfLinks & 1) == 0 ? 1 : 0;
			if ($this->currentPage - ($this->pagesBefore + $delta) < 1) {
				// Too little from the right to adjust
				$this->pagesAfter = $forcedNumberOfLinks - $this->currentPage - 1;
				$this->pagesBefore = $forcedNumberOfLinks - $this->pagesAfter - 1;
			}
			elseif ($this->currentPage + ($this->pagesAfter + $delta) >= $this->numberOfPages) {
				$this->pagesBefore = $forcedNumberOfLinks - ($this->numberOfPages - $this->currentPage);
				$this->pagesAfter = $forcedNumberOfLinks - $this->pagesBefore - 1;
			}
			else {
				$this->pagesBefore += $delta;
				$this->pagesAfter += $delta - $incr;
			}
		}
	}

	/**
	 * Adds header parts from the template to the TSFE.
	 * It fetches subpart identified by ###HEADER_ADDITIONSS### and replaces ###SITE_REL_PATH### with site-relative part to the extension.
	 *
	 * @param	string		$ref	Reference
	 * @param	string		$subpart	Subpart from template to add.
	 * @param	array		$conf	Configuration
	 * @return	void
	 */
	protected function addHeaderParts() {
		$subPart = $this->cObj->getSubpart($this->templateCode, '###HEADER_ADDITIONS###');
		$key = $this->prefixId . '_' . md5($subPart);
		if (!isset($GLOBALS['TSFE']->additionalHeaderData[$key])) {
			$GLOBALS['TSFE']->additionalHeaderData[$key] =
				$this->cObj->substituteMarkerArray($subPart, array(
					'###SITE_REL_PATH###' => $GLOBALS['TSFE']->config['config']['absRefPrefix'] .
						t3lib_extMgm::siteRelPath($this->extKey),
				));
		}
	}

	/**
	 * Produces the page browser HTML
	 *
	 * @return	string	Generated content
	 */
	protected function createPageBrowser() {
		$out = '';
		if ($this->numberOfPages > 1) {
			// Set up
			$markers = array(
				'###TEXT_FIRST###' => htmlspecialchars($this->pi_getLL('text_first')),
				'###TEXT_NEXT###' => htmlspecialchars($this->pi_getLL('text_next')),
				'###TEXT_PREV###' => htmlspecialchars($this->pi_getLL('text_prev')),
				'###TEXT_LAST###' => htmlspecialchars($this->pi_getLL('text_last')),
			);
			$subPartMarkers = array();
			$subPart = $this->cObj->getSubpart($this->templateCode, '###PAGE_BROWSER###');

			// First page link
			if ($this->currentPage == 0) {
				$subPartMarkers['###ACTIVE_FIRST###'] = '';
			}
			else {
				$markers['###FIRST_LINK###'] = $this->getPageLink(0, self::PAGE_FIRST);
				$subPartMarkers['###INACTIVE_FIRST###'] = '';
			}
			// Prev page link
			if ($this->currentPage == 0) {
				$subPartMarkers['###ACTIVE_PREV###'] = '';
			}
			else {
				$markers['###PREV_LINK###'] = $this->getPageLink($this->currentPage - 1, self::PAGE_PREV);
				$subPartMarkers['###INACTIVE_PREV###'] = '';
			}
			// Next link
			if ($this->currentPage >= $this->numberOfPages - 1) {
				$subPartMarkers['###ACTIVE_NEXT###'] = '';
			}
			else {
				$markers['###NEXT_LINK###'] = $this->getPageLink($this->currentPage + 1, self::PAGE_NEXT);
				$subPartMarkers['###INACTIVE_NEXT###'] = '';
			}
			// Last link
			if ($this->currentPage == $this->numberOfPages - 1) {
				$subPartMarkers['###ACTIVE_LAST###'] = '';
			}
			else {
				$markers['###LAST_LINK###'] = $this->getPageLink($this->numberOfPages - 1, self::PAGE_LAST);
				$subPartMarkers['###INACTIVE_LAST###'] = '';
			}

			// Page links
			$actPageLinkSubPart = trim($this->cObj->getSubpart($subPart, '###CURRENT###'));
			$inactPageLinkSubPart = trim($this->cObj->getSubpart($subPart, '###PAGE###'));
			$pageLinks = '';
			$start = max($this->currentPage - $this->pagesBefore, 0);
			$end = min($this->numberOfPages, $this->currentPage + $this->pagesAfter + 1);
			for ($i = $start; $i < $end; $i++) {
				$template = ($i == $this->currentPage ? $actPageLinkSubPart : $inactPageLinkSubPart);
				$pageType = ($i < $this->currentPage ? self::PAGE_BEFORE :
					($i > $this->currentPage ? self::PAGE_AFTER : self::PAGE_CURRENT));
				$localMarkers = array(
					'###NUMBER###' => $i,
					'###NUMBER_DISPLAY###' => $i + 1,
					'###LINK###' => $this->getPageLink($i, $pageType),
				);
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['pageLinkMarkers'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['pageLinkMarkers'] as $userFunc) {
						$params = array(
							'markers' => &$localMarkers,
							'page' => $i,
							'pObj' => &$this
						);
						t3lib_div::callUserFunction($userFunc, $params, $this);
					}
				}
				$pageLinks .= $this->cObj->substituteMarkerArray($template, $localMarkers);
			}
			$subPartMarkers['###PAGE###'] = $pageLinks;
			$subPartMarkers['###CURRENT###'] = '';

			// Less pages part
			if ($start == 0 || !$this->conf['enableLessPages']) {
				$subPartMarkers['###LESS_PAGES###'] = '';
			}
			// More pages part
			if ($end == $this->numberOfPages || !$this->conf['enableMorePages']) {
				// We have all pages covered. Remove this part.
				$subPartMarkers['###MORE_PAGES###'] = '';
			}

			// Extra markers hook
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['additionalMarkers'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['additionalMarkers'] as $userFunc) {
					$params = array(
						'currentPage' => $this->currentPage,
						'markers' => &$markers,
						'numberOfPages' => $this->numberOfPages,
						'pObj' => &$this,
						'subparts' => &$subPartMarkers
					);
					t3lib_div::callUserFunction($userFunc, $params, $this);
				}
			}

			// Compile all together
			$out = $this->cObj->substituteMarkerArrayCached($subPart, $markers, $subPartMarkers);
			// Remove all comments
			$out = preg_replace('/<!--\s*###.*?-->/', ' ', $out);
			// Remove excessive spacing
			$out = preg_replace('/\s{2,}/', ' ', $out);
		}
		return $out;
	}

	/**
	 * Generates page link. Keeps all current URL parameters except for cHash and tx_pagebrowse_pi1[page].
	 *
	 * @param	int		$page	Page number starting from 1
	 * @param	int		$pageType	One of PAGE_xxx constants
	 * @return	string		Generated link
	 */
	protected function getPageLink($page, $pageType) {
		// Prepare query string. We do both urlencoded and non-encoded version
		// because older TYPO3 versions use unencoded parameter names
		$queryConf = array(
			'exclude' => $this->pageParameterName . ',' .
							rawurlencode($this->pageParameterName) .
							',cHash',
		);
		$additionalParams = urldecode($this->cObj->getQueryArguments($queryConf));

		// Add page number
		if ($page > 0) {
			$additionalParams .= '&' . $this->pageParameterName . '=' . $page;
		}

		// Add extra query string from config
		$extraQueryString = trim($this->conf['extraQueryString']);
		if (is_array($this->conf['extraQueryString.'])) {
			$extraQueryString = $this->cObj->stdWrap($extraQueryString, $this->conf['extraQueryString.']);
		}
		if (strlen($extraQueryString) > 2 && $extraQueryString{0} == '&') {
			$additionalParams .= $extraQueryString;
		}

		// Call extra parameter hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['additionalParameters'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['additionalParameters'] as $userFunc) {
				$params = array(
					'pObj' => &$this,
					'additionalParameters' => $additionalParams,
					'pageType' => $pageType,
					'pageNumber' => $page,
				);
				$additionalParams = t3lib_div::callUserFunction($userFunc, $params, $this);
			}
		}
		// Assemble typolink configuration
		$conf = array(
			'parameter' => $GLOBALS['TSFE']->id,
			'additionalParams' => $additionalParams,
			'useCacheHash' => (strlen($additionalParams) > 1) && !$this->conf['disableCacheHash'],
		);
		return htmlspecialchars($this->cObj->typoLink_URL($conf));
	}

	/**
	 * Tests if the value can be interpreted as integer.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	static protected function testInt($value) {
		if (class_exists('\\TYPO3\\CMS\\Core\\Utility\\MathUtility')) {
			$result = \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($value);
		} elseif (class_exists('t3lib_utility_Math')) {
			$result = t3lib_utility_Math::canBeInterpretedAsInteger($value);
		} else {
			$result = t3lib_div::testInt($value);
		}
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagebrowse/pi1/class.tx_pagebrowse_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagebrowse/pi1/class.tx_pagebrowse_pi1.php']);
}

?>
