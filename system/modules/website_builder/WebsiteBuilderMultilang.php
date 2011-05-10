<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  InfinitySoft 2010
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    Website Builder
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


/**
 * Class WebsiteBuilderDatasetImport
 *
 * Website Builder dataset import.
 * @copyright  InfinitySoft 2010
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    Website Builder
 */
class WebsiteBuilderMultilang extends BackendModule
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_website_builder_multilang';
	
	
	/**
	 * Initialise the backend module.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}
	
	
	/**
	 * Generate module
	 */
	protected function compile()
	{
		$this->loadLanguageFile('tl_website_builder_multilang');
		$this->loadDataContainer('tl_page');
		
		if (in_array('changelanguage', $this->Config->getActiveModules()))
		{
			if ($this->Input->post('FORM_SUBMIT') == 'tl_multilang_create')
			{
				$strSource = $this->Input->post('source_language');
				$strTarget = $this->Input->post('target_language');
				$strDNS = $this->Input->post('dns');
				
				$objPage = $this->Database->prepare("SELECT * FROM tl_page WHERE type='root' AND (dns=? OR fallback='1' AND languageRoot=?) AND language=?")
					->execute($strDNS == '-' ? '' : $strDNS, $strDNS == '-' ? '' : $strDNS, $strSource);
				if ($objPage->next())
				{
					$arrMapping = array();
					$this->cloneStructure($objPage, null, $strTarget, $arrMapping);
					
					$objPage = $this->Database->execute("SELECT * FROM tl_page WHERE id IN (" . implode(',', $arrMapping) . ") AND type='forward'");
					while ($objPage->next())
					{
						if (isset($arrMapping[$objPage->jumpTo]))
						{
							$this->Database->prepare("UPDATE tl_page SET jumpTo=? WHERE id=?")
								->execute($arrMapping[$objPage->jumpTo], $objPage->id);
						}
					}
					
					$_SESSION['TL_INFO'][] = $GLOBALS['TL_LANG']['tl_website_builder_multilang']['created'];
				}
				else
				{
					$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['tl_website_builder_multilang']['missing_root'], $strDNS, $strSource);
				}
				
				$this->reload();
			}
			
			$arrSession = $this->Session->get('website_builder_multilang');
			if (!is_array($arrSession))
			{
				$arrSession = array();
			}
			
			if ($this->Input->post('FORM_SUBMIT') == 'tl_multilang')
			{
				$arrSession['dns'] = $this->Input->post('dns');
			}
			
			$objPage = $this->Database->execute("SELECT * FROM tl_page WHERE type='root'");
			$arrPages = array();
			$arrTemp = array();
			
			// collect language root pages, push alternatives into $arrTemp
			while ($objPage->next())
			{
				if ($objPage->fallback)
				{
					if (!$objPage->languageRoot)
					{
						$arrPages[$objPage->dns ? $objPage->dns : '-'] = array
						(
							'root' => $objPage->language,
							'pages' => array
							(
								$objPage->language => $objPage->row()
							)
						);
						continue;
					}
				}
				$arrTemp[] = $objPage->row();
			}
			
			// push 
			while (count($arrTemp))
			{
				$arrPage = array_shift($arrTemp);
				
				if ($arrPage['fallback'])
				{
					if (isset($arrPages[$arrPage['languageRoot']]))
					{
						$arrRef = &$arrPages[$arrPage['languageRoot']];
						if (!isset($arrRef['pages'][$arrPage['language']]))
						{
							$arrRef['pages'][$arrPage['language']] = $arrPage;
						}
						else
						{
							$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['tl_website_builder_multilang']['duplicate_language'],
								$arrPage['languageRoot'], $arrPage['language'], $arrRef['pages'][$arrPage['language']]['id'], $arrPage['id']);
						}
					}
					else
					{
						$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['tl_website_builder_multilang']['missing_root'],
							$arrPage['languageRoot'], $arrPage['id']);
					}
				}
				else
				{
					if (isset($arrPages[$arrPage['dns'] ? $arrPage['dns'] : '-']))
					{
						$arrRef = &$arrPages[$arrPage['dns'] ? $arrPage['dns'] : '-'];
						if (!isset($arrRef['pages'][$arrPage['language']]))
						{
							$arrRef['pages'][$arrPage['language']] = $arrPage;
						}
						else
						{
							$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['tl_website_builder_multilang']['duplicate_language'],
								$arrPage['dns'] ? $arrPage['dns'] : '-', $arrPage['language'], $arrRef['pages'][$arrPage['language']]['id'], $arrPage['id']);
						}
					}
					else
					{
						$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['tl_website_builder_multilang']['missing_fallback'],
							$arrPage['dns'] ? $arrPage['dns'] : '-', $arrPage['id']);
					}
				}
			}
			
			if (!isset($arrSession['dns']) || !isset($arrPages[$arrSession['dns']]))
			{
				$arrDomains = array_keys($arrPages);
				$arrSession['dns'] = array_shift($arrDomains);
			}
			
			if ($arrSession['dns'])
			{
				$arrRef = &$arrPages[$arrSession['dns']];
				
				$arrLanguages = array_keys($arrRef['pages']);
				$arrStructure = array
				(
					0 => array
					(
						'id' => 0,
						'pid' => '0',
						'children' => array(),
						'languages' => array()
					)
				);
				foreach ($arrLanguages as $strLanguage)
				{
					$arrStructure[0]['languages'][$strLanguage] = false;
				}
				
				// build the structure
				$arrMapping = array();
				foreach ($arrRef['pages'] as $strLang=>$arrPage)
				{
					$this->buildStructure($arrRef['root'], $arrPage, $arrStructure, $arrMapping, $arrLanguages);
				}

				// link the children
				foreach ($arrStructure as $intId=>&$arrPageTemp)
				{
					if ($arrPageTemp['master'])
					{
						$intPID = $arrPageTemp['pid'];
						/*
						if (isset($arrMapping[$intPID]))
						{
							$intPID = $arrMapping[$intPID];
						}
						*/
						$arrStructure[$intPID]['children'][] = &$arrPageTemp;
					}
				}

				$this->Template->structure = $arrStructure[0];
			}
			
			$this->Template->dns = $arrSession['dns'];
			$this->Template->pages = $arrPages;
			
			$this->Session->set('website_builder_multilang', $arrSession);
		}
		else
		{
			$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['tl_website_builder_multilang']['missing_changelanguage'];
		}
	}


	protected function buildStructure($strLang, $arrPage, &$arrStructure, &$arrMapping, $arrLanguages)
	{
		$arrStructure[$arrPage['id']] = array
		(
			'id' => $arrPage['id'],
			'pid' => $arrPage['pid'],
			'data' => $arrPage,
			'children' => array(),
			'languages' => array(),
			'master' => true
		);
		foreach ($arrLanguages as $strLanguage)
		{
			$arrStructure[$arrPage['id']]['languages'][$strLanguage] = false;
		}
		$arrStructure[$arrPage['id']]['languages'][$arrPage['language']] = $arrPage;
		
		$arrChildren = array();
		$arrTemp = array();
		$objPage = $this->Database->prepare("SELECT * FROM tl_page WHERE pid=? ORDER BY sorting")
			->execute($arrPage['id']);
		while ($objPage->next())
		{
			if ($objPage->type == 'root')
			{
				$this->buildStructure($strLang, $objPage->row(), $arrStructure, $arrMapping, $arrLanguages);
			}
			else if ($objPage->languageMain)
			{
				$arrMapping[$objPage->id] = $objPage->languageMain;
				
				if ($arrStructure[$objPage->languageMain]['languages'][$objPage->language] == false)
				{
					$arrStructure[$arrPage['id']]['master'] = false;
					$arrStructure[$objPage->languageMain]['languages'][$objPage->language] = $objPage->row();
					$this->buildStructure($strLang, $objPage->row(), $arrStructure, $arrMapping, $arrLanguages);
				}
				else
				{
					$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['tl_website_builder_multilang']['duplicate_page_language'],
						$objPage->title, $objPage->alias, $objPage->id,
						$arrStructure[$objPage->languageMain]['languages'][$objPage->language]['title'], $arrStructure[$objPage->languageMain]['languages'][$objPage->language]['alias'], $arrStructure[$objPage->languageMain]['languages'][$objPage->language]['id'],
						$objPage->language);
				}
			}
			else
			{
				$this->buildStructure($strLang, $objPage->row(), $arrStructure, $arrMapping, $arrLanguages);
			}
		}
		return $arrChildren;
	}

	
	protected function cloneStructure($objPage, $objParent, $strLang, &$arrMapping)
	{
		$arrRow = $objPage->row();
		unset($arrRow['id'], $arrRow['pid'], $arrRow['language'], $arrRow['fallback'], $arrRow['alias']);
		
		$arrRow['pid'] = $objParent ? $objParent->id : 0;
		$arrRow['language'] = $strLang;
		$arrRow['languageMain'] = $objPage->id;
		
		if (!$objParent)
		{
			$intSorting = $this->Database->execute("SELECT MAX(sorting) as sorting FROM tl_page WHERE pid=0")->sorting;
			$intSorting += 128;
			$arrRow['sorting'] = $intSorting;
		}
		
		$intId = $this->Database->prepare("INSERT INTO tl_page %s")
			->set($arrRow)
			->execute()
			->insertId;
		
		$arrMapping[$objPage->id] = $intId;
		
		$objNewPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
			->execute($intId);
		$objChild = $this->Database->prepare("SELECT * FROM tl_page WHERE pid=?")
			->execute($objPage->id);
		while ($objChild->next())
		{
			$this->cloneStructure($objChild, $objNewPage, $strLang, $arrMapping);
		}
		
		// clone the articles
		$objArticle = $this->Database->prepare("SELECT * FROM tl_article WHERE pid=?")
			->execute($objPage->id);
		while ($objArticle->next())
		{
			$arrRow = $objArticle->row();
			unset($arrRow['id'], $arrRow['pid'], $arrRow['alias']);
			
			$arrRow['pid'] = $objNewPage->id;
			
			$intId = $this->Database->prepare("INSERT INTO tl_article %s")
				->set($arrRow)
				->execute()
				->insertId;
			
			// clone the article content
			$objContent = $this->Database->prepare("SELECT * FROM tl_content WHERE pid=?")
				->execute($objArticle->id);
			while ($objContent->next())
			{
				$arrRow = $objContent->row();
				unset($arrRow['id'], $arrRow['pid']);
				
				$arrRow['pid'] = $intId;
				
				$this->Database->prepare("INSERT INTO tl_content %s")
					->set($arrRow)
					->execute();
			}
		}
	}
}

?>