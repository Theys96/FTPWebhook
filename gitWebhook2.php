<?php
class ftp {
    public $conn;
    public function __construct($url){
        $this->conn = ftp_connect($url);
    }
    public function __call($func,$a){
        if(strstr($func,'ftp_') !== false && function_exists($func)){
            array_unshift($a,$this->conn);
            return call_user_func_array($func,$a);
        }else{
            die("$func is not a valid FTP function");
        }
    }
}

function isfile($repo, $before, $file){
   $headers = get_headers("https://raw.githubusercontent.com/" . $repo . "/" . $before . "/" . $file);
   return stripos($headers[0],"200 OK")?true:false;
}

$configs = array();

$payload = json_decode($_POST['payload'], true);
$before = $payload['before'];
$longid = $payload['head_commit']['id'];
$id = substr($longid, 0, 7);

if (
	isset($configs[$payload['repository']['full_name']]) && 
	isset($configs[$payload['repository']['full_name']][$payload['ref']])
) {
	$repConfig = $configs[$payload['repository']['full_name']][$payload['ref']];
} else {
	die("No configuration for " . $payload['repository']['full_name'] . "/" . $payload['ref']);
}


$conn = new ftp($repConfig[0]);
$conn->ftp_login($repConfig[1], $repConfig[2]);
$conn->ftp_pasv(true);
$conn->ftp_chdir($repConfig[3]);

for ($i = 0; $i < count($payload['commits']); $i++) {
	$commit = $payload['commits'][$i];
	echo "Commit: \"" . $commit['message'] . "\".\n";
	echo "ID: " . $commit['id'] . "\n";
	echo "Base URL: " . "https://raw.githubusercontent.com/" . $repConfig[4] . "/" . $commit['id'] . "/\n";
	foreach ($commit['added'] as $add) {
		$conn->ftp_put($add, "https://raw.githubusercontent.com/" . $repConfig[4] . "/" . $commit['id'] . "/" . $add, FTP_BINARY);
		echo "Added " . $add . "\n";
	}
	foreach ($commit['deleted'] as $del) {
		$conn->ftp_delete($del);
		echo "Deleted " . $del . "\n";
	}
	foreach ($commit['modified'] as $mod) {
		$conn->ftp_put($mod, "https://raw.githubusercontent.com/" . $repConfig[4] . "/" . $commit['id'] . "/" . $mod, FTP_BINARY);
		echo "Modified " . $mod . "\n";
	}
}

$conn->ftp_close();

/* Backup
foreach(array('added', 'removed', 'modified') as $type) {
	foreach ($filechanges[$type] as $file) {
		$conn->ftp_rename($file, "git_archives/" . $id . "/" . str_replace('/','_',$file));
	}
}

/* Actual execution
sort($dirchanges['added']);
foreach ($dirchanges['added'] as $dir) {
	$conn->ftp_mkdir($file);
}

foreach ($filechanges['removed'] as $file) {
	$conn->ftp_delete($file, $file);
}

foreach(array('added', 'modified') as $type) {
	foreach ($filechanges[$type] as $file) {
		$conn->ftp_put($file, "https://raw.githubusercontent.com/" . $repConfig[4] . "/" . $longid . "/" . $file, FTP_BINARY);
		echo "Fetching https://raw.githubusercontent.com/" . $repConfig[4] . "/" . $longid . "/" . $file . "\n";
	}
}

rsort($dirchanges['removed']);
foreach ($dirchanges['removed'] as $dir) {
	$conn->ftp_rmdir($dir);
}


$conn->ftp_close();
*/
?>
