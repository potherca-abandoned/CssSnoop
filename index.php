<?php
require 'errorHandling.php';
require 'class.Template.php';
require 'class.CssSniffer.php';
require 'class.CssSnifferTemplate.php';

$oTemplate = CssSnifferTemplate::fromFile('main.html');

$oSniffer = new CssSniffer();
$oSniffer->setCssDirectory('/home/ben/Desktop/dev/DCPF/new/www/rsrc/css');
$oSniffer->parse();

$oTemplate->setSniffer($oSniffer);

$oTemplate->render();
echo $oTemplate->toString();

#EOF