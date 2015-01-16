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
- secret : a secret key for generating (TODO)

IF you use secret, the repository config file should not be viewable by web users

*/

require_once('./../conf/config.php');

$output = array();
$output[] = "\n";

//create the log dir if not exists
///// create dir
$logDir = explode('/', $logFile);
if (count($logDir) > 1) { //last entry should be filename, remove it to create dir
   unset($logDir[ count($logDir) - 1 ]);
}
$logDir = implode('/', $logDir); #recreate the log dir
if (!file_exists($logDir)) {
   $result = createDirRecursive($logDir);
   //check the dir is created...
   if ($result === FALSE) {
      header('HTTP/1.0 500 Creating log dir path fail');
      $output[] = "Creating log dir fail - check user rights\n";
      echo "Creating log dir fail - check user rights\n";
      return;
   }
   touch($logDir);
}

//Register shutdown function 
function shutdown($logFile) {
   global $output;
   file_put_contents($logFile, implode("\n", $output), FILE_APPEND | LOCK_EX);
}
register_shutdown_function('shutdown', $logFile);

//decode repositories
$configRepositories = json_decode(file_get_contents($repositoriesConfigFile), TRUE);
if ($configRepositories === NULL) {
   header('HTTP/1.0 500 error parsing repository file');
   echo "error parsing repository file\n";
   $output[] = "error parsing repository file";
   exit();
}

//decode headers and check if this is a push event
$headers = apache_request_headers();
if (!isset($headers['X-Github-Event'])) {
   header('HTTP/1.0 400 github headers should be present');
   echo "github header should be present";
   $output[] = "github header missing";
   exit();
}
if ($headers['X-Github-Event'] !== 'push') {
   header('HTTP/1.0 400 only push event allowed');
   echo 'only push event allowed';
   $output[] = "only push event allowed";
   exit();
}

//decode the post
$body = file_get_contents('php://input');
// for debug $payload = json_decode(file_get_contents('test_payload.json'), TRUE);
/* todo if (empty($_POST)) {
   header('HTTP/1.0 400 Method not allowed'); //todo check code
   echo "method not allowed";
   $output[] = "method not allowed";
   exit();
}*/
$payload = json_decode($body, TRUE);

if ($payload === false) {
   header('HTTP/1.0 400 could not parse body');
   $output[] = "I could not understand your body";
   echo "I could not understand your body";
   exit();
}

//check the repo is allowed
$repo = $payload['repository'];

$configuredRepo = NULL;
foreach ($configRepositories as $allowedRepo) {
   if (mb_strtolower($allowedRepo['full_name']) === mb_strtolower($repo['full_name'])) {
      $configuredRepo = $allowedRepo;
      break;
    }
}

//stop script if repo is not allowed
if ($configuredRepo === NULL) {
   header('HTTP/1.0 400 Bad Request - repo is not allowed');
   $output[] = 'repo is not allowed';
   echo "repo is not allowed";
   exit();
}

//check the HMAC, stop the script if incorrect
if (isset($allowedRepo['secret'])) {
   if (!isset($headers['X-Hub-Signature'])) {
      $error = 'HTTP/1.0 403 hmac signature requested';
      header($error);
      $output[] = $error;
      echo $error;
      exit();
   }   

   if (
      'sha1='.hash_hmac('sha1', $body, $allowedRepo['secret']) !==
      $headers['X-Hub-Signature']
   )  {
      $error = 'HTTP/1.0 403 hmac signature incorrect';
      header($error);
      $output[] = $error;
      echo $error;
      exit();
   }
}  

//clone repo
$clonePath = $gitTempDir.'/'.$repo['full_name'];

if (!file_exists($clonePath)) {
   $result = createDirRecursive($clonePath);
   //check the dir is created...
   if ($result === FALSE) {
      header('HTTP/1.0 500 Creating repo path fail');
      $output[] = "Creating repo path fail - check user rights\n";
      echo "Creating repo path fail - check user rights\n";
      exit();
   }
   //initial clone
   exec($git.' clone '.escapeshellarg($repo['clone_url']).' '.escapeshellarg($clonePath), $output);
}

//extract ref and switch to ref
$refs = explode('/', $payload['ref']);

if ($refs[1] === 'heads') { //we are on a branch
   //pull remote branch and switch to it
   exec($git.' -C '.escapeshellarg($clonePath).' pull origin '.escapeshellarg($refs[2]).':'.escapeshellarg($refs[2]), $output);
   exec($git.' -C '.escapeshellarg($clonePath).' checkout '.escapeshellarg($refs[2]), $output);
} elseif ($refs[1] === 'tags') {
   exec($git.' -C '.escapeshellarg($clonePath).' checkout '.escapeshellargs($refs[2]), $output);
} else {
   header('HTTP/1.0 200 OK');
   echo "We do not know your ref, but this is not your fault\n";
   $output[] = "We do not know your ref, but this is not your fault\n";
   $output[] = "ref is ".$payload['ref'];
   exit();
}

//guess target path
$targetPath = $targetDir.'/'.$repo['full_name'].'/'.end(explode('/', $payload['ref']));

//create the target doc dir if not exists
if (!file_exists($targetPath)) {
   $result = createDirRecursive($targetPath);
   if ($result === FALSE) {
      header('HTTP/1.0 500 Creating target path fail');
      echo "Creating target path fail - check user rights\n";
      exit();
   }
}

//run doc
exec($phpdoc.' -d '.escapeshellarg($clonePath).' -t '.escapeshellarg($targetPath), $output);

//everything is ok, yahouu !
header('HTTP/1.0 200 OK');
echo "Everything is ok, thank you !";
$output[] = "Everything is ok, thank you !";
$output[] = "\n";
exit();

function createDirRecursive($fullPath, $before = array()) 
{
   $paths = explode('/', $fullPath);
   $beforePath = implode('/', $before);

   if (count($paths) === 0) {
      return true;
   }

   if (!file_exists($beforePath.'/'.$paths[0])) {
      //echo "attempt to create ".$beforePath.'/'.$paths[0]."\n";
      $result = mkdir($beforePath.'/'.$paths[0]);
      if ($result === false) {

         return $result;
      }
   } else {
      //echo "path ".$beforePath.'/'.$paths[0]." exists, skipping \n";
   }

   if (count($paths) > 1) {
      $before[] = $paths[0];
      unset($paths[0]);
      $result = createDirRecursive(implode('/', $paths), $before);
   }

   return isset($result) ? $result : true;
}
