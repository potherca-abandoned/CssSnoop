<?php
require 'errorHandling.php';
require 'class.Template.php';
require 'class.CssSniffer.php';
require 'class.CssSnifferTemplate.php';

$oTemplate = CssSnifferTemplate::fromFile('main.html');

$oSniffer = new CssSniffer();
$oSniffer->setCssDirectory(__DIR__);
$oSniffer->parse();

$oTemplate->setSniffer($oSniffer);

echo $oTemplate;

#EOF
