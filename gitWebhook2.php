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

function verifyDir($conn, $add) {
	$dir = explode("/", $add);
	array_pop($dir);
	$makedir = ".";
	foreach ($dir as $d) {
		$makedir .= "/" . $d;
		$conn->ftp_mkdir($makedir);
	}
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
	echo "\nCommit: \"" . $commit['message'] . "\".\n";
	echo "ID: " . $commit['id'] . "\n";
	echo "Base URL: " . "https://raw.githubusercontent.com/" . $repConfig[4] . "/" . $commit['id'] . "/\n";
	foreach ($commit['added'] as $add) {
		verifyDir($conn, $add);
		$conn->ftp_put($add, "https://raw.githubusercontent.com/" . $repConfig[4] . "/" . $commit['id'] . "/" . $add, FTP_BINARY);
		echo "Added " . $add . "\n";
	}
	foreach ($commit['removed'] as $del) {
		$conn->ftp_delete($del);
		echo "Deleted " . $del . "\n";
	}
	foreach ($commit['modified'] as $mod) {
		verifyDir($conn, $add);
		$conn->ftp_put($mod, "https://raw.githubusercontent.com/" . $repConfig[4] . "/" . $commit['id'] . "/" . $mod, FTP_BINARY);
		echo "Modified " . $mod . "\n";
	}
}

/* TODO HERE: Remove any empty directory */

$conn->ftp_close();
?>
