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
class WebsiteBuilderDatasetImport extends BackendModule
{
	private static $VAR_REPLACE_REGEXP = '#\$(\w+(?:(?:\.|->)\w+)*(\|\w+)?|\{([^\}\|]+)(\|\w+)?\})#';


	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_website_builder_dataset_import';


	/**
	 * The local variables
	 * @var array
	 */
	protected $arrVariables;


	/**
	 * Placeholder data
	 * @var array
	 */
	protected $arrData;


	/**
	 * Information about "late update" fields, stored as reference of table => field => value
	 * @var array
	 */
	protected $arrLateUpdate;


	/**
	 * List of all created elements ids to roll back the import.
	 * @var array
	 */
	protected $arrCreated;


	/**
	 * Initialise the backend module.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}


	/**
	 * Convert a callback node into a php callback.
	 *
	 * @param DOMNode $domNodeCallback
	 * @param DOMXPath $xpath
	 * @return array
	 */
	protected function convertCallback(DOMNode &$domNodeCallback, DOMXPath &$xpath)
	{
		$strClass = $xpath->evaluate('string(@class)');
		$strMethod = $xpath->evaluate('string(@method)');
		return array($strClass, $strMethod);
	}


	/**
	 * Convert a node set into a widget compatible dca structure.
	 *
	 * @param DOMNode $domNode
	 * @param DOMXPath $xpath
	 * @return array
	 */
	protected function convertXML2Widget(DOMNode $domNode, DOMXPath &$xpath)
	{
		$arrData = array();

		for ($i=0; $i<$domNode->childNodes->length; $i++)
		{
			$nodeChild = $domNode->childNodes->item($i);
			$strName = $nodeChild->localName;

			/* script text elements */
			if (empty($strName))
			{
				continue;
			}

			switch ($strName)
			{
			/* the label */
			case 'label':
				if (!isset($arrData['label']))
				{
					$arrData['label'] = array('', '');
				}
				$arrData['label'][0] = $nodeChild->textContent;
				break;

			/* the description part of the label */
			case 'description':
				if (!isset($arrData['label']))
				{
					$arrData['label'] = array('', '');
				}
				$arrData['label'][1] = $nodeChild->textContent;
				break;

			/* convert options to an array */
			case 'options':
				$arrOptions = array();
				$nodesOption = $xpath->query('wb:option', $nodeChild);
				for ($j=0; $j<$nodesOption; $j++)
				{
					$nodeOption = $nodesOption->item($j);
					$strValue = $xpath->evaluate('string(@value)', $nodeOption);
					$strLabel = $nodeOption->textContent;
					$arrOptions[] = array('value'=>$strValue, 'label'=>$strLabel);
				}
				$arrData['options'] = $arrOptions;
				break;

			/* convert references to a hash map */
			case 'reference':
				$arrRefs = array();
				$nodesRef = $xpath->query('wb:ref', $nodeChild);
				for ($j=0; $j<$nodesRef; $j++)
				{
					$nodeRef = $nodesRef->item($j);
					$strKey = $xpath->evaluate('string(@key)', $nodeRef);
					$strLabel = $nodeRef->textContent;
					$arrRefs[$strKey] = $strLabel;
				}
				$arrData['reference'] = $arrRefs;
				break;

			/* callback fields */
			case 'options_callback';
			case 'inputFieldCallback':
				$arrData[$strName] = $this->convertCallback($nodeChild, $xpath);
				break;

			/* eval is a subset, also convert it to an array */
			case 'eval':
				$arrData['eval'] = $this->convertXML2Widget($nodeChild, $xpath);
				break;

			/* boolean fields */
			case 'helpwizard':
			case 'mandatory':
			case 'fallback':
			case 'multiple':
			case 'submitOnChange':
			case 'nospace':
			case 'allowHtml':
			case 'preserveTags':
			case 'decodeEntities':
			case 'doNotSaveEmpty':
			case 'alwaysSave':
			case 'spaceToUnderscore':
			case 'unique':
			case 'encrypt':
			case 'trailingSlash':
			case 'files':
			case 'filesOnly':
			case 'includeBlankOption':
			case 'findInSet':
				$arrData[$strName] = (trim($nodeChild->textContent) == 'true');
				break;

			/* a simple text containing field */
			default:
				$arrData[$strName] = $nodeChild->textContent;
			}
		}
		return $arrData;
	}


	/**
	 * Import datarows into the database.
	 *
	 * @param DOMNode $domNode
	 * @param DOMXPath $xpath
	 * @param mixed $varPid
	 * @throws Exception
	 */
	protected function importDatarow(DOMNode $domNode, DOMXPath &$xpath, $varPid = false)
	{
        $objDatabase = Database::getInstance();

        /** @var string $strVar */
        $strVar = $xpath->evaluate('string(@var)', $domNode);
		$strTable = $xpath->evaluate('string(@table)', $domNode);

        // break if table does not exists
        if (!$objDatabase->tableExists($strTable)) {
            throw new Exception('Table "' . $strTable . '" does not exists!');
        }

        // add new row to database
        $insertId = $objDatabase
            ->query("INSERT INTO {$strTable} (id) VALUES (NULL)")
            ->insertId;

        // read new record object
        $objRecord = (object) $objDatabase
            ->prepare("SELECT * FROM {$strTable} WHERE id=?")
            ->execute($insertId)
            ->fetchAssoc();

        // set tstamp
        $objRecord->tstamp = time();

		// store as created row
		$this->arrCreated[$strTable][] = $objRecord->id;

		// load default values
		$this->loadDataContainer($strTable);
		if (is_array($GLOBALS['TL_DCA'][$strTable]['fields']))
		{
			foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strName => $arrField)
			{
				if (isset($arrField['default']))
				{
					$objRecord->$strName = $arrField['default'];
				}
			}
		}

        // set the records pid
		if ($varPid)
		{
			$objRecord->pid = $varPid;
		}

        // collection of records that should be updated lately
		$arrLateUpdate = array();

		// fill the data fields
		$nodesField = $xpath->query('wb:field', $domNode);
		for ($i=0; $i<$nodesField->length; $i++)
		{
			$nodeField = $nodesField->item($i);

			$strName = $xpath->evaluate('string(@name)', $nodeField);

            // continue if field does not exists
            if (!$objDatabase->fieldExists($strName, $strTable)) {
                $_SESSION['TL_ERROR'][] = 'Field "' . $strTable . '.' . $strName . '" does not exists, skip field!';
                continue;
            }

			$blnInherit = $xpath->evaluate('boolean(@inherit)', $nodeField);
			$strInheritField = $xpath->evaluate('string(@inheritField)', $nodeField);
			$strInheritTable = $xpath->evaluate('string(@inheritTable)', $nodeField);
			$blnEval = $xpath->evaluate('boolean(@eval)', $nodeField);
			$blnEvalUser = $xpath->evaluate('boolean(@eval-user)', $nodeField);
			$blnForceArray = $xpath->evaluate('boolean(@force-array)', $nodeField);
			$blnNovars = $xpath->evaluate('boolean(@novars)', $nodeField);
			$strValue = $nodeField->textContent;

			// inherit from parent
			if ($blnInherit)
			{
				if (!$strInheritField)
				{
					$strInheritField = $strName;
				}
				if (!$strInheritTable)
				{
					$strInheritTable = $strTable;
				}
				if ($varPid)
				{
					$strPid = $varPid;
				}
				else
				{
					$strPid = $objRecord->pid;
				}
				if (strlen($strPid))
				{
					$objParent = $objDatabase->prepare("
							SELECT
								*
							FROM
								$strInheritTable
							WHERE
								id=?")
						->executeUncached($strPid);
					if ($objParent->next())
					{
						$strValue = $objParent->$strInheritField;
					}
					else
					{
						throw new Exception('Could not inherit "' . $strInheritTable . "." . $strInheritField . '" as "' . $strTable . "." . $strName . '", row id "' . $strPid . '" was not found!');
					}
				}
			}

			// replace all variables
			if (!$blnNovars)
			{
				try
				{
					$strValue = preg_replace_callback(self::$VAR_REPLACE_REGEXP, array(&$this, 'replaceVariable'), $strValue);
				}
				// variable was not found
				catch (Exception $e)
				{
					// also add to late update array
					$arrLateUpdate[$strName] = array
					(
						'eval' => $blnEval,
						'forceArray' => $blnForceArray,
						'value' => $strValue
					);
					continue;
				}
			}

			// evaluate as php code
			if ($blnEval)
			{
				if (false === ($varEvaluatedValue = eval('return '.$strValue.';')))
				{
					unset($varEvaluatedValue);
					throw new Exception('Evaluation of value "' . htmlentities($strValue) . '" failed for field "' . $strName . '"!');
				}
				else
				{
					$strValue = $varEvaluatedValue;
					unset($varEvaluatedValue);
				}
			}

			// evaluate as user assigning php code
			if ($blnEvalUser)
			{
				if (false === eval($strValue))
				{
					unset($varEvaluatedValue);
					throw new Exception('Evaluation of value "' . htmlentities($strValue) . '" failed for field "' . $strName . '"!');
				}
				else if (!isset($varEvaluatedValue))
				{
					unset($varEvaluatedValue);
					throw new Exception('Evaluation of value "' . htmlentities($strValue) . '" for field "' . $strName . '" have to set the $varEvaluatedValue variable!');
				}
				else
				{
					$strValue = $varEvaluatedValue;
					unset($varEvaluatedValue);
				}
			}

			// force build of an array
			if ($blnForceArray && !is_array($strValue))
			{
				$strValue = array($strValue);
			}

			$objRecord->$strName = $strValue;
		}

		if ($objDatabase->fieldExists('alias', $strTable) && empty($objRecord->alias))
		{
			$strAlias = standardize(trim(empty($objRecord->name) ? $objRecord->title : $objRecord->name));

			if ($strAlias)
			{
				$objAlias = $objDatabase->prepare("
						SELECT
							*
						FROM
							$strTable
						WHERE
							alias=?")
					->executeUncached($strAlias);
				if ($objAlias->numRows > 0)
				{
					$strAlias .= '.' . $objRecord->id;
				}

				$objRecord->alias = $strAlias;
			}
		}

		// resorting the rows
		if ($objDatabase->fieldExists('sorting', $strTable))
		{
			if ($objDatabase->fieldExists('pid', $strTable))
			{
				$objSorting = $objDatabase->prepare("
						SELECT
							MAX(sorting) as sorting
						FROM
							$strTable
						WHERE
							pid=?")
					->executeUncached($objRecord->pid);
			}
			else
			{
				$objSorting = $objDatabase->executeUncached("
						SELECT
							MAX(sorting) as sorting
						FROM
							$strTable");
			}
			$objRecord->sorting = ($objSorting->sorting > 0 ? $objSorting->sorting : 0) + 128;
		}

		// store the new data
        $objDatabase
            ->prepare("UPDATE {$strTable} %s WHERE id=?")
            ->set((array) $objRecord)
            ->execute($objRecord->id);

		// set the local var
		if ($strVar)
		{
			$this->arrVariables[$strVar] = $objRecord;
		}

		// add late update variables
		if (count($arrLateUpdate))
		{
			if (!isset($this->arrLateUpdate[$strTable]))
			{
				$this->arrLateUpdate[$strTable] = array();
			}
			$this->arrLateUpdate[$strTable][$objRecord->id] = $arrLateUpdate;
		}

		// import the child records
		$nodesChild = $xpath->query('wb:child', $domNode);
		for ($i=0; $i<$nodesChild->length; $i++)
		{
			$this->importDatarow($nodesChild->item($i), $xpath, $objRecord->id);
		}
	}


	/**
	 * Callback method for preg_replace_callback to replace variables.
	 *
	 * @param array $arrMatches
	 * @return string
	 * @throws Exception
	 */
	protected function replaceVariable($arrMatches)
	{
		if (!empty($arrMatches[3]))
		{
			$strKey = $arrMatches[3];
		}
		else
		{
			$strKey = $arrMatches[1];
		}

		if (!empty($arrMatches[4]))
		{
			$strFunction = substr($arrMatches[4], 1);
		}
		elseif (!empty($arrMatches[2]))
		{
			$strFunction = substr($arrMatches[2], 1);
		}
		else
		{
			$strFunction = false;
		}

		$arrKeys = preg_split('#\.|->#', $strKey);
		$varValue = null;

		if (count($arrKeys) > 1)
		{
			$strOriginalKey = $strKey;
			$varTmp = $this->arrVariables;
			foreach ($arrKeys as $strKey)
			{
				if ($strKey == 'this')
				{
					$varTmp = $this;
				}
				else if (is_array($varTmp) && isset($varTmp[$strKey]))
				{
					$varTmp = $varTmp[$strKey];
				}
				else if (is_object($varTmp))
				{
					$varTmp = $varTmp->$strKey;
				}
				else
				{
					throw new Exception('Variable part "' . $strKey . '" from "' . $strOriginalKey . '" not available.<br/><span style="white-space:pre">' . htmlentities($arrMatches[0]) . '</span><br/><span style="white-space:pre">' . htmlentities(print_r(array_keys($this->arrVariables), true)) . '</span>');
				}
			}
			$varValue = $varTmp;
		}
		else if (isset($this->arrVariables[$strKey]))
		{
			$varValue = $this->arrVariables[$strKey];
		}
		else if (isset($GLOBALS['TL_CONFIG'][$strKey]))
		{
			$varValue = $GLOBALS['TL_CONFIG'][$strKey];
		}

		if ($varValue !== null)
		{
			$varValue = $this->applyFunction($strFunction, $varValue);

			if (is_array($varValue))
			{
				return serialize($varValue);
			}
			else if (is_object($varValue))
			{
				return $varValue->id;
			}
			else
			{
				return $varValue;
			}
		}

		throw new Exception('Variable "' . $strKey . '" not available.<br/><span style="white-space:pre">' . htmlentities($arrMatches[0]) . '</span><br/><span style="white-space:pre">' . htmlentities(print_r(array_keys($this->arrVariables), true)) . '</span>');
	}


	protected function applyFunction($strFunction, $varValue)
	{
		if (!$strFunction)
		{
			return $varValue;
		}

		if (is_array($varValue))
		{
			foreach ($varValue as $k=>$v)
			{
				$varValue[$k] = $this->applyFunction($strFunction, $v);
			}
			return $varValue;
		}
		else
		{
			return call_user_func($strFunction, $varValue);
		}
	}


	/**
	 * Generate the virtual dca structure.
	 */
	protected function generateDCA()
	{
		if (strlen($this->Input->get('dataset')))
		{
			$arrDatasets = deserialize($this->Session->get('datasets'));
			$arrDataset = $arrDatasets[$this->Input->get('dataset')];
			if (is_array($arrDataset))
			{
				$doc = new DOMDocument();
				$doc->loadXML($arrDataset['xml']);

				// only accept valid xml
				if ($doc->schemaValidate(TL_ROOT . '/system/modules/website_builder/config/website_builder.xsd'))
				{
					$xpath = new DOMXPath($doc);
					$xpath->registerNamespace('wb', 'http://www.infinitysoft.de/contao/website_builder');

					$this->generateWidgets($doc, $xpath);
				}
			}
		}
	}


	/**
	 * Create directory path.
	 *
	 * @param string $strPath
	 */
	protected function mkdirs($strPath)
	{
		if (!is_dir($strPath))
		{
			$this->mkdirs(dirname($strPath));
			mkdir($strPath);
		}
	}


	/**
	 * Generate module
	 */
	protected function compile()
	{
        $objDatabase = Database::getInstance();

		$this->loadLanguageFile('tl_website_builder_dataset_import');

		if (strlen($this->Input->get('dataset')))
		{
			$arrDatasets = deserialize($this->Session->get('datasets'));
			$arrDataset = $arrDatasets[$this->Input->get('dataset')];
			if (is_array($arrDataset))
			{
				$doc = new DOMDocument();
				$doc->loadXML($arrDataset['xml']);

				// only accept valid xml
				if ($doc->schemaValidate(TL_ROOT . '/system/modules/website_builder/config/website_builder.xsd'))
				{
					$xpath = new DOMXPath($doc);
					$xpath->registerNamespace('wb', 'http://www.infinitysoft.de/contao/website_builder');

					$arrWidgets = array();

					$nodesVariable = $xpath->query('wb:variable');
					for ($i=0; $i<$nodesVariable->length; $i++)
					{
						$nodeVariable = $nodesVariable->item($i);

						$strName = $xpath->evaluate('string(@name)', $nodeVariable);
						$arrData = $this->convertXML2Widget($nodeVariable, $xpath);

						// default inputType
						if (empty($arrData['inputType']))
						{
							$arrData['inputType'] = 'text';
						}
						if (!isset($arrData['eval']['mandatory']))
						{
							$arrData['eval']['mandatory'] = true;
						}
						$arrData['eval']['required'] = $arrData['eval']['mandatory'];

						// optional values
						if (!$arrData['eval']['required'])
						{
							$arrData['label'][0] .= ' (optional)';
						}

						$strClass = $GLOBALS['BE_FFL'][$arrData['inputType']];
						if (!$strClass)
						{
							$_SESSION['TL_ERROR'][] = 'Unknown input type: "' . $strClass . '" given for field "' . $strName . '"!';
							$this->redirect('contao/main.php?do=dataset_import');
						}

						$arrWidget = $this->prepareForWidget($arrData, $strName, '', $strName, 'tl_dataset_import');
						$objWidget = new $strClass($arrWidget);

						// default value
						if ($arrData['default'] && $this->Input->post('FORM_SUBMIT') != 'dataset_import')
						{
							$objWidget->value = $arrData['default'];
						}

						$arrWidgets[$strName] = $objWidget;
					}

					if (	!count($arrWidgets) // no variables
						||	$this->Input->post('FORM_SUBMIT') == 'dataset_import')
					{
						$this->arrData = array();
						$this->arrVariables = array();
						$this->arrLateUpdate = array();
						$this->arrCreated = array();

						$blnDoNotSubmit = false;
						foreach ($arrWidgets as $strName => $objWidget)
						{
							$objWidget->validate();
							$this->arrVariables[$strName] = $objWidget->value;
							if ($objWidget->hasErrors())
							{
								$blnDoNotSubmit = true;
							}
						}

						// the final import
						if (!$blnDoNotSubmit)
						{
							try
							{
								$nodeDataset = $doc->documentElement;

								// replace placeholders
								$nodesData = $xpath->evaluate('wb:data', $nodeDataset);
								foreach ($nodesData as $nodeData)
								{
									$strPlace = $xpath->evaluate('string(@place)', $nodeData);
									$nodesPlaceholders = $xpath->evaluate('//wb:placeholder[@name="' . $strPlace . '"]', $nodeDataset);
									foreach ($nodesPlaceholders as $nodePlaceholder)
									{
										$nodeParent = $nodePlaceholder->parentNode;
										foreach ($nodeData->childNodes as $nodeChild)
										{
											$nodeParent->insertBefore($nodeChild->cloneNode(true), $nodePlaceholder);
										}
										$nodeParent->removeChild($nodePlaceholder);
									}
								}

								// import rows
								$nodesRow = $xpath->evaluate('wb:row', $nodeDataset);

								for ($i=0; $i<$nodesRow->length; $i++)
								{
									$this->importDatarow($nodesRow->item($i), $xpath);
								}

								foreach ($this->arrLateUpdate as $strTable => $arrTable)
								{
									foreach ($arrTable as $strId => $arrLateUpdate)
									{
                                        // read current record
                                        $objRecord = (object) $objDatabase
                                            ->prepare("SELECT * FROM {$strTable} WHERE id=?")
                                            ->execute($strId)
                                            ->fetchAssoc();

										foreach ($arrLateUpdate as $strName => $arrValue)
										{
											// no try-catch here, because if variable is not available here, i can not fallback!
											$strValue = preg_replace_callback(self::$VAR_REPLACE_REGEXP, array(&$this, 'replaceVariable'), $arrValue['value']);

											if ($arrValue['eval'])
											{
												if (false === ($strEvaluatedValue = eval('return '.$strValue.';')))
												{
													throw new Exception('Evaluation of value "' . htmlentities($strValue) . '" failed for field "' . $strName . '"!');
												}
												else
												{
													$strValue = $strEvaluatedValue;
												}
											}

											if ($arrValue['forceArray'] && !is_array($strValue))
											{
												$strValue = array($strValue);
											}

											$objRecord->$strName = $strValue;
										}

                                        // store the updated data
                                        $objDatabase
                                            ->prepare("UPDATE {$strTable} %s WHERE id=?")
                                            ->set((array) $objRecord)
                                            ->execute($objRecord->id);

										// and destroy connector
										unset($objConnector);
									}
								}

								$nodesMkdir = $xpath->evaluate('wb:mkdir', $nodeDataset);
								for ($i=0; $i<$nodesMkdir->length; $i++)
								{
									$strPath = $nodesMkdir->item($i)->textContent;
									$strPath = preg_replace_callback(self::$VAR_REPLACE_REGEXP, array(&$this, 'replaceVariable'), $strPath);
									// create the directory
									$this->mkdirs(TL_ROOT . '/' . $strPath);
								}

								$nodesMkfile = $xpath->evaluate('wb:mkfile', $nodeDataset);
								for ($i=0; $i<$nodesMkfile->length; $i++)
								{
									$strPath = $nodesMkfile->item($i)->textContent;
									$strPath = preg_replace_callback(self::$VAR_REPLACE_REGEXP, array(&$this, 'replaceVariable'), $strPath);
									// create parent directory
									$this->mkdirs(dirname(TL_ROOT . '/' . $strPath));
									// create the file
									file_put_contents(TL_ROOT . '/' . $strPath, "");
								}

								$nodesLoad = $xpath->evaluate('wb:load', $nodeDataset);
								for ($i=0; $i<$nodesLoad->length; $i++)
								{
									$nodeLoad = $nodesLoad->item($i);
									$strTarget = preg_replace_callback(self::$VAR_REPLACE_REGEXP, array(&$this, 'replaceVariable'), $xpath->evaluate('string(@target)', $nodeLoad));
									$blnUnzip = preg_replace_callback(self::$VAR_REPLACE_REGEXP, array(&$this, 'replaceVariable'), $xpath->evaluate('boolean(@unzip)', $nodeLoad));
									$strSource = preg_replace_callback(self::$VAR_REPLACE_REGEXP, array(&$this, 'replaceVariable'), $nodeLoad->textContent);

									$strName = basename($strSource);
									// absolutize source
									if (!(	preg_match('#^https?://#', $strSource)
										||	preg_match('#^/#', $strSource)))
									{
										$strSource = TL_ROOT . '/' . $strSource;
									}

									if (false === @copy($strSource, TL_ROOT . '/' . $strTarget . '/' . $strName))
									{
										throw new Exception('Copy "' . $strSource . '" to "' . $strTarget . '/' . $strName . '" failed!');
									}

									if ($blnUnzip)
									{
										$zipReader = new ZipReader($strTarget . '/' . $strName);
										while ($zipReader->next())
										{
											$this->mkdirs(TL_ROOT . '/' . $strTarget . '/' . $zipReader->file_dirname);
											file_put_contents(TL_ROOT . '/' . $strTarget . '/' . $zipReader->file_name, $zipReader->unzip());
										}
										unset($zipReader);
										@unlink(TL_ROOT . '/' . $strTarget . '/' . $strName);
									}
								}

								$this->log(preg_replace('#</?strong>#', '"', $_SESSION['TL_INFO'][] = sprintf($GLOBALS['TL_LANG']['tl_website_builder_dataset_import']['success'], $xpath->evaluate('string(wb:name/text())', $nodeDataset))), 'WebsiteBuilderDatasetImport compile()', TL_INFO);
								$this->redirect('contao/main.php?do=dataset_import');
							}
							catch(Exception $e)
							{
								// delete all created rows!
								foreach ($this->arrCreated as $strTable => $arrIds)
								{
									if (count($arrIds))
									{
										$objDatabase->executeUncached("DELETE FROM $strTable WHERE id IN (" . implode(',', $arrIds) . ")");
									}
								}
								$_SESSION['TL_ERROR'][] = $e->getMessage();
							}
						}

                        $this->redirect('contao/main.php?do=dataset_import');
					}

					$this->Template->variables = $arrWidgets;
					return;
				}
			}
			$this->redirect('contao/main.php?do=dataset_import');
		}

        $arrDatasets = $this->loadDatasets();

		// no datasets configured
        if ($arrDatasets === null) {
			$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['tl_website_builder_dataset_import']['missing_configuration'];
        }

        // add datasets for selection
        else if (count($arrDatasets))
        {
            $this->Session->set('datasets', serialize($arrDatasets));
            $this->Template->datasets = $arrDatasets;
        }

        // no datasets found
        else
        {
            $_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['tl_website_builder_dataset_import']['faulty_configuration'];
        }
	}

    protected function loadDatasets()
    {
        $arrDatasets = array();

		// no operation, list importable datasets
		$GLOBALS['TL_CONFIG']['website_builder_datasets'] = deserialize($GLOBALS['TL_CONFIG']['website_builder_datasets'], true);
		if (is_array($GLOBALS['TL_CONFIG']['website_builder_datasets']) && count($GLOBALS['TL_CONFIG']['website_builder_datasets']))
		{
			for ($n=0; $n<count($GLOBALS['TL_CONFIG']['website_builder_datasets']); $n++)
			{
				$strDataset = $GLOBALS['TL_CONFIG']['website_builder_datasets'][$n];

				if (	preg_match('#^https?://#', $strDataset)
					||	preg_match('#^/#', $strDataset)
					||	file_exists($strDataset = TL_ROOT . '/' . $strDataset))
				{
					$strXML = @file_get_contents($strDataset);
					// read the xml
					if ($strXML)
					{
						$doc = new DOMDocument();
						// load the xml
						if (false !== @$doc->loadXML($strXML))
						{
							// only accept valid files
							if ($doc->schemaValidate(TL_ROOT . '/system/modules/website_builder/config/website_builder.xsd'))
							{
								$xpath = new DOMXPath($doc);
								$xpath->registerNamespace('wb', 'http://www.infinitysoft.de/contao/website_builder');

								$nodesImport = $xpath->query('//wb:import');
								for ($i=0; $i<$nodesImport->length; $i++)
								{
									$strImport = $nodesImport->item($i)->textContent;
									if (!(	preg_match('#^https?://#', $strImport)
										||	preg_match('#^/#', $strImport)
										||	file_exists(TL_ROOT . '/' . $strDataset)))
									{
										$strImport = preg_replace('#/[^/]*$#', '/', $strDataset) . $strImport;
									}
									$GLOBALS['TL_CONFIG']['website_builder_datasets'][] = $strImport;
								}

								$nodesDataset = $xpath->query('//wb:dataset');
								for ($i=0; $i<$nodesDataset->length; $i++)
								{
									$nodeDataset = $nodesDataset->item($i);

									$strId = $xpath->evaluate('string(@id)', $nodeDataset);
									if ($strId)
									{
										$strKey = $strId;
									}
									else
									{
										do
										{
											$strKey = substr(md5(time()*rand()), 0, 8);
										}
										while (isset($arrDatasets[$strKey]));
									}

									$arrDatasets[$strKey] = array(
										'id'          => $strKey,
										'extends'     => $xpath->evaluate('string(@extends)', $nodeDataset),
										'abstract'    => $xpath->evaluate('string(@abstract)', $nodeDataset),
										'xml'         => $doc->saveXML($nodeDataset)
									);
								}

								foreach ($arrDatasets as $strKey=>&$arrDataset)
								{
									$this->extendDataset($strKey, $arrDataset, $arrDatasets);
								}

								foreach ($arrDatasets as $strKey=>&$arrDataset)
								{
									$doc = new DOMDocument();
									$doc->loadXML($arrDataset['xml']);
									$xpath = new DOMXPath($doc);
									$xpath->registerNamespace('wb', 'http://www.infinitysoft.de/contao/website_builder');

									$arrDataset['name']        = $xpath->evaluate('string(wb:name/text())', $doc->documentElement);
									$arrDataset['description'] = $xpath->evaluate('string(wb:description/text())', $doc->documentElement);
								}
							}
						}
					}
				}
			}
		}
        else {
            return null;
        }

        return $arrDatasets;
    }

	/**
	 * Extend a dataset
	 */
	protected function extendDataset($strKey, &$arrDataset, &$arrDatasets)
	{
		if ($arrDataset['extends'])
		{
			$strExtends = $arrDataset['extends'];
			if ($arrDatasets[$strExtends])
			{
				$this->extendDataset($strExtends, $arrDatasets[$strExtends], $arrDatasets);

				$doc = new DOMDocument();
				$nodeDataset = $doc->createElementNS('http://www.infinitysoft.de/contao/website_builder', 'dataset');
				$doc->appendChild($nodeDataset);

				$docExtension = new DOMDocument();
				$docExtension->loadXML($arrDataset['xml']);
				$xpathExtension = new DOMXPath($docExtension);
				$xpathExtension->registerNamespace('wb', 'http://www.infinitysoft.de/contao/website_builder');

				$docBase = new DOMDocument();
				$docBase->loadXML($arrDatasets[$strExtends]['xml']);
				$xpathBase = new DOMXPath($docBase);
				$xpathBase->registerNamespace('wb', 'http://www.infinitysoft.de/contao/website_builder');

				// singleton elements
				foreach (array('wb:name', 'wb:description') as $strElement)
				{
					$nodeElement = $xpathExtension->query($strElement, $docExtension->documentElement);
					if ($nodeElement->length > 0)
					{
						$nodeDataset->appendChild($doc->importNode($nodeElement->item(0), true));
					}
					else
					{
						$nodeElement = $xpathBase->query($strElement, $docBase->documentElement);
						if ($nodeElement->length > 0)
						{
							$nodeDataset->appendChild($doc->importNode($nodeElement->item(0), true));
						}
					}
				}

				// append elements
				foreach (array('wb:variable', 'wb:group', 'wb:data', 'wb:row', 'wb:mkdir', 'wb:mkfile', 'wb:load') as $strElement)
				{
					$nodeElements = $xpathExtension->query($strElement, $docExtension->documentElement);
					foreach ($nodeElements as $nodeElement)
					{
						$nodeDataset->appendChild($doc->importNode($nodeElement, true));
					}

					$nodeElements = $xpathBase->query($strElement, $docBase->documentElement);
					foreach ($nodeElements as $nodeElement)
					{
						$nodeDataset->appendChild($doc->importNode($nodeElement, true));
					}
				}

				$arrDataset['xml'] = $doc->saveXML();
			}
			unset($arrDataset['extends']);
		}
	}


	/**
	 * HOOK to create the virtual dca.
	 *
	 * @param string $strName
	 */
	public function hookLoadDataContainer($strName)
	{
		if ($strName == 'tl_dataset_import')
		{
			$this->generateDCA();
		}
	}
}

?>