<?php

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

#EOF