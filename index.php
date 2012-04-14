<?php
require 'errorHandling.php';
require 'class.Template.php';

function run()
{
	$oTemplate = CssSnifferTemplate::fromFile('main.html');

	$oSniffer = new CssSniffer();
	$oSniffer->setCssDirectory('/home/ben/Desktop/dev/DCPF/new/www/rsrc/css');
	$oSniffer->parse();

	$oTemplate->setSniffer($oSniffer);

	echo $oTemplate;
}

#EOF

class CssSniffer
{
	protected $m_sCssDirectory;

	protected $m_aSelectorList = array();

	protected $m_aFileList;

	protected $m_aFiles;
	
	protected $m_aCounters = array(
		  'Elements' => array()
		, 'Classes'  => array()
		, 'IDs' 	 => array()
		, 'Pseudo'	 => array()
		, 'Attribute'	 => array()
	);

	public function setCssDirectory($p_sCssDirectory)
	{
		$this->m_sCssDirectory = $p_sCssDirectory;
	}

	public function getCssDirectory()
	{
		return $this->m_sCssDirectory;
	}

	public function getSelectorList()
	{
		return $this->m_aSelectorList;
	}

	public function getFileList()
	{
		return $this->m_aFileList;
	}

	public function parse()
	{
		if(!isset($this->m_sCssDirectory))
		{
			throw new UnexpectedValueException('No directory given to parse');
		}
		else if(!is_dir($this->m_sCssDirectory))
		{
			throw new InvalidArgumentException('Given directory does not exist');
		}
		else
		{
			$iterator = new RecursiveDirectoryIterator($this->m_sCssDirectory);
			foreach($iterator as $t_oFileInfo)
			{
				/** @var $t_oFileInfo SplFileInfo */
                $extension = function_exists('SplFileInfo::getExtension')?$t_oFileInfo->getExtension():substr($t_oFileInfo->getBasename(),strrpos($t_oFileInfo->getBasename(), '.')+1);
                if($t_oFileInfo->isFile() && $extension === 'css')
				{
                    $this->m_aFileList[] = $t_oFileInfo;
					$aSelectorList = $this->parseFile($t_oFileInfo);

					$this->m_aSelectorList = array_merge($this->m_aSelectorList, $aSelectorList);
				}#if
			}#foreach
		}#if
	}

	protected function parseFile(SplFileInfo $p_oFileInfo)
	{
		$oFileObject = $p_oFileInfo->openFile();
		$sContent = '';

		while (!$oFileObject->eof()) {
            $sContent .= $oFileObject->fgets();
		}

		// Strip out comments
		$sContent = preg_replace('#(\\/\\*.*?\\*\\/)#ism', '', $sContent);

		return $this->grabSelectors($sContent, $oFileObject->getFilename());
	}

	private function grabSelectors($sContent, $p_sFileName)
	{
		$aFinalList = array();

		$sPattern = '#\s*(?P<SELECTORS>[^@;\\\\]*?)\s*{(?P<RULES>[^}]*)}#';
		if(preg_match_all($sPattern, $sContent, $aMatches) > 0)
		{
			$aSelectors = $aMatches['SELECTORS'];
			foreach($aSelectors as $t_sSelector)
			{
				$aSelectorList = explode(',', $t_sSelector);
				foreach($aSelectorList as $sSelector)
				{
					$sSelector = trim($sSelector);
					$sLastSelector = trim(substr($sSelector, strrpos($sSelector, ' ')));

					//@TODO: Better logic is needes as all three selectors could be combined, only the last ID/Class should take precendence
					if(strrpos($sLastSelector, ':') !== false)
					{
						$this->m_aCounters['Pseudo'][$sLastSelector] = $sLastSelector;
					}
					elseif(strrpos($sLastSelector, '[') !== false)
					{
						$this->m_aCounters['Attribute'][$sLastSelector] = $sLastSelector;
					}
					elseif(strrpos($sLastSelector, '.') !== false)
					{
						$this->m_aCounters['Classes'][$sLastSelector] = $sLastSelector;
					}
					elseif(strrpos($sLastSelector, '#') !== false)
					{
						$this->m_aCounters['IDs'][$sLastSelector] = $sLastSelector;
					}
					else
					{
						$this->m_aCounters['Elements'][$sLastSelector] = $sLastSelector;
					}
					
					$aFinalList[$sLastSelector][$p_sFileName][] = $sSelector;
				}
			}
		}

		return $aFinalList;
	}

	public function getFileCount()
	{
		return count($this->m_aFileList);
	}

	public function getCounters()
	{
		return $this->m_aCounters;
	}
}


class CssSnifferTemplate extends Template
{

	/**
	 * @var CssSniffer
	 */
	protected $m_oSniffer;

	public function setSniffer(CssSniffer $p_oSniffer)
	{
		$this->m_oSniffer = $p_oSniffer;
	}

	public function getSniffer()
	{
		return $this->m_oSniffer;
	}

	public function __toString()
	{
		if(isset($this->m_oSniffer))
		{
			$oSniffer = $this->m_oSniffer;

            $iBaseSelectors = count($oSniffer->getSelectorList());
            $iTotalSelectors =  count($oSniffer->getSelectorList(), COUNT_RECURSIVE) - $iBaseSelectors;

            $oMainSection  = $this->getFirstElementByTagName('section');
            $oFilesMenu    = $this->getFirstElementWithClassName('files');
            $oSelectorMenu = $this->getFirstElementWithClassName('selector');

            //------------------------------------------------------------------
            $oListNode = $oFilesMenu->appendChild($this->createElement('ul'));
			$aFilesNames = array();
			foreach($oSniffer->getFileList() as $t_oSplFile)
			{
				/** @var $t_oSplFile SplFileObject */
				$aFilesNames[] = substr($t_oSplFile->getPathname(), strlen($oSniffer->getCssDirectory())+1);
			}
			natcasesort($aFilesNames);
			foreach($aFilesNames as $t_sFileName)
			{
				$oListNode->appendChild($this->createElement('li', $t_sFileName));
			}
			//------------------------------------------------------------------


            //------------------------------------------------------------------
            $oTitle = $oFilesMenu->getElementsByTagName('h2')->item(0);
            $oTitle->appendChild($this->createElement('span', $oSniffer->getFilecount()));
            //------------------------------------------------------------------


            //------------------------------------------------------------------
            $oTitle = $oSelectorMenu->getElementsByTagName('h2')->item(0);
            $oTitle->appendChild($this->createElement('span', $iTotalSelectors));
            //------------------------------------------------------------------


            //------------------------------------------------------------------
            // @TODO: Place these somewhere
            $this->createElement('li', 'Average Selectors per File : ' . ceil($iTotalSelectors / $oSniffer->getFilecount()));
            $this->createElement('li', 'Base Selectors : ' . $iBaseSelectors);
            //------------------------------------------------------------------


            //------------------------------------------------------------------
            $aSelectorList = $oSniffer->getSelectorList();
            foreach($oSniffer->getCounters() as $t_sName => $t_aCounter)
            {
                $oTitle = $oMainSection->appendChild($this->createElement('h3', $t_sName));
                $oTitle->appendChild($this->createElement('span', count($t_aCounter)));

                $oSelectorMenu->appendChild($this->createElement('h3', $t_sName));
                $oListNode = $oSelectorMenu->appendChild($this->createElement('ul'));

                natcasesort($t_aCounter);
                foreach($t_aCounter as $t_sBaseSelector)
                {
                    $iBaseSelectorCount = 0;
                    $iFileCount = 0;

                    // Get info from base selector
                    $aSelectorInfo = $aSelectorList[$t_sBaseSelector];

                    // Add row to menu
                    $oListNode->appendChild($this->createElement('li', $t_sBaseSelector));

                    // Append Header to main section
                    $oTitle = $oMainSection->appendChild($this->createElement('h4', $t_sBaseSelector));

                    $oSelectorList = $oMainSection->appendChild($this->createElement('ul'));

                    foreach($aSelectorInfo as $t_sFileName => $t_aFullSelectors)
                    {
                        $iFileCount++;

                        foreach($t_aFullSelectors as $t_iLineNumber => $t_sFullSelector)
                        {
                            $iBaseSelectorCount++;

                            // Append info to main selection
                            $oListItem = $oSelectorList->appendChild($this->createElement('li', $t_sFullSelector));
                            $oListItem->appendChild($this->createElement('span', $t_iLineNumber . ' : ' . $t_sFileName));
                        }#foreach
                    }#foreach

                    $oTitle->appendChild($this->createElement('span', $iBaseSelectorCount . ' selector(s)'));
                    $oTitle->appendChild($this->createElement('span', $iFileCount . ' file(s)'));
                }#foreach
            }#foreach
            //------------------------------------------------------------------

            /** @var $DOMNodeList DOMNodeList  */
		}
		return parent::__toString();
	}
}

run();
