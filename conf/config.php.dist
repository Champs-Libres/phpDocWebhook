<?php 

/* 
This script generate php doc using phpDocumentor on github webhook

Usage
------

adapt config/config.php to your needs :

set path to : 

- phpDocumentor in $phpdoc
- git (get absolute path) in $git ;

Set the variables : 

- gitTempDir : where the git repo is cloned before running phpdoc ;
- targetDir : where the doc should be generated ;
- logFile : to log this script ;

Set the repository config file, as a json file with an array of repositories : 
- full_name : the name of the repo ;
- secret : a secret key for generating

Example : 

[
   {
      "full_name" : "Chill-project/Main",
      "secret" : "12345"
   }
]

IF you use secret, the repository config file should not be viewable by web users

*/

$phpdoc = '/usr/local/bin/phpdoc';
$git = '/usr/bin/git';
$repositoriesConfigFile = './../conf/repository.json';
$gitTempDir = './../temp-clone';
$targetDir = '.';
$logFile = './../logs/file.log';
