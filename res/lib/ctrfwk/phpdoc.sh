#!/bin/sh
phpdoc_dest=/hosting/http/tryer.cl/dom/devel/www/cf/doc/
rm -rf $phpdoc_dest/*
phpdoc -d ./class,./include -t $phpdoc_dest -ti 'Counter Framework' -o 'HTML:frames:DOM/earthli'

a=<<EOF
	"HTML:frames:default"			=>	'HTML:frames:default',
	"HTML:frames:earthli"			=>	'HTML:frames:earthli',
	"HTML:frames:l0l33t"			=>	'HTML:frames:l0l33t',
	"HTML:frames:phpdoc.de"			=>	'HTML:frames:phpdoc.de',
	"HTML:frames:phphtmllib"		=>	'HTML:frames:phphtmllib',
	"HTML:frames:phpedit"			=>	'HTML:frames:phpedit',
	"HTML:frames:DOM/default"		=>	'HTML:frames:DOM/default',
	"HTML:frames:DOM/earthli"	    =>	'HTML:frames:DOM/earthli',
	"HTML:frames:DOM/l0l33t"		=>	'HTML:frames:DOM/l0l33t',
	"HTML:frames:DOM/phpdoc.de"		=>	'HTML:frames:DOM/phpdoc.de',
	"HTML:frames:DOM/phphtmllib"	=>	'HTML:frames:DOM/phphtmllib',
	"HTML:Smarty:default"			=>	'HTML:Smarty:default',
	"HTML:Smarty:HandS"				=>	'HTML:Smarty:HandS',
	"HTML:Smarty:PHP"   			=>	'HTML:Smarty:PHP',
	"PDF:default:default"			=>	'PDF:default:default',
	"CHM:default:default"			=>	'CHM:default:default',
	"XML:DocBook/peardoc2:default"	=>	'XML:DocBook/peardoc2:default'
EOF
