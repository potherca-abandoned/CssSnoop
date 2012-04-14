<?php

/**
 * @method static CssSnifferTemplate fromFile()
 */
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

                        foreach($t_aFullSelectors as $t_sFullSelector)
                        {
                            //@TODO: add $t_iLineNumber
                            $iBaseSelectorCount++;

                            // Append info to main selection
                            $oListItem = $oSelectorList->appendChild($this->createElement('li', $t_sFullSelector));
                            $oListItem->appendChild($this->createElement('span', $t_sFileName));
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

#EOF