<?php
require(dirname(__FILE__)."/config.php");
require(WWW_DIR.'/lib/powerprocess.php');
$dirroot = $argv[1];
$subdirs = array_filter(glob($dirroot."/*", GLOB_ONLYDIR|GLOB_NOSORT));
$subdir_count = 0;

foreach($subdirs AS $subdir){
	//$filecount = count(glob($subdir."/*.nzb"));
	//if($filecount > 0){
		//echo "Directory {$subdir} contains {$filecount} nzb's.\n";
		$subdir_count++;
	//}
}

if($subdir_count == 0){
	echo "Nothing to import, sub-directories are empty or there are no sub-directories to process.\n";
	die();
}

//get variables from config.sh and defaults.sh
$varnames = shell_exec("cat ../config.sh | grep ^export | cut -d \= -f1 | awk '{print $2;}'");
$varnames .= shell_exec("cat ../defaults.sh | grep ^export | cut -d \= -f1 | awk '{print $2;}'");
$vardata = shell_exec('cat ../config.sh | grep ^export | cut -d \" -f2 | awk "{print $1;}"');
$vardata .= shell_exec('cat ../defaults.sh | grep ^export | cut -d \" -f2 | awk "{print $1;}"');
$varnames = explode("\n", $varnames);
$vardata = explode("\n", $vardata);
$array = array_combine($varnames, $vardata);
unset($array['']);

$ps = new PowerProcess;
$ps->RegisterCallback('psUpdateComplete');
$ps->maxThreads = $array['NZB_FOLDER_COUNT'];
$ps->threadTimeLimit = 0;
$selected = $subdir_count - 1;

echo "Starting threaded import process, {$array['NZBCOUNT']} per thread\n";

while ($ps->RunControlCode()) 
{
	if ($selected >= 0)
	{
		if ($ps->SpawnReady()) 
		{
			$ps->threadData = $subdirs[$selected]."/";
			$ps->spawnThread();
				} 
	} 
	else 
	{
		$ps->Shutdown();
	}
$selected--;
}

if ($ps->RunThreadCode())
{
	$subdir = $ps->threadData;
	$thread = sprintf("%05d",$ps->GetPID());

	$dir = dirname(__FILE__);
	$file = 'nzb-importonenzb.php';
	echo "[Thread-{$thread}] Started importing from {$subdir}\n";
	$output = shell_exec("php {$dir}/{$file} {$subdir} {$array['IMPORT_TRUE']}");
	echo "[Thread-{$thread}] Completed importing from {$subdir}\n";
}
exit(0);


function psUpdateComplete()
{
	echo "[Thread-MASTER] The threaded import process has completed.\n";
}

?>


