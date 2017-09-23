<?php
include("fj_common.php");
ignore_user_abort(true);

if (!isset($_POST['fj_hash'])||!is_hash($_POST['fj_hash'], 40)||!isset($_POST['fj_jar'])||!is_hash($_POST['fj_jar'], 32)||!isset($_POST['fj_sig_pre'])||!is_hash($_POST['fj_sig_pre'], 40)||!isset($_POST['fj_file'])||!is_hash($_POST['fj_file'], 4)||!isset($_POST['fj_block'])||!isset($_POST['fj_max'])||!isset($_POST['fj_content'])||$_POST['fj_block']>$_POST['fj_max']) exit;

function fj_load_get() {
	global $fj_load_range;

	$fj_load_select = array_keys($fj_load_range);
	unset($fj_load_select[4000+intval(str_ireplace("FJ", "", gethostname()))]);
	$fj_load_select = array_values($fj_load_select);
	$fj_attempt_limit = count($fj_load_select)*10;
	$fj_attempt = 0;
	$fj_load = 0;
	$fj_pass = false;
	while ($fj_pass===false&&$fj_attempt>$fj_attempt_limit) {
		if ($fj_attempt>$fj_attempt_limit) ;
		$fj_load = $fj_load_select[mt_rand(0, count($fj_load_range)-1)];
		$fj_result_check = fj_db("SELECT `fj_value` FROM `fj_load`.`fj_dynamic` WHERE `fj_index` = 'fj_ping'", array($fj_load, "fj_load"));
		if ($fj_result_check->num_rows==1&&intval($fj_result_check->fetch_assoc()['fj_value'])==1) $fj_pass = true;
		$fj_attempt++;
	}
	if ($fj_pass===false) fj_return(2);
	return array($fj_load, "fj_load");
}

if ($_POST['fj_hash']!=sha1($_POST['fj_file'].$_POST['fj_block'].$_POST['fj_max'].$_POST['fj_content'])) fj_return(2);

$fj_content_length = strlen($_POST['fj_content']);
if ($_POST['fj_block']!=$_POST['fj_max']&&$fj_content_length!=100000) exit;

$fj_sig = substr(sha1($_POST['fj_sig_pre']), 0, 10);
$fj_result = fj_db("SELECT `fj_jar` FROM `fj_front`.`fj_jar` WHERE `fj_jar` = '".$_POST['fj_jar']."' AND `fj_sig` = '".$fj_sig."'", array(4000, "fj_front"));
if ($fj_result->num_rows!=1) exit;
$fj_load_select = fj_load_select();
if ($_POST['fj_block']==1) $_POST['fj_file'] = fj_rand(4);
else {
	$fj_found = false;
	foreach($fj_load_select as $fj_server) {
		$fj_result = fj_db("SELECT `fj_scan` FROM `fj_load`.`fj_safe` WHERE `fj_jar` = '".$_POST['fj_jar']."' AND `fj_sig` = '".$fj_sig."' AND `fj_file` = '".$_POST['fj_file']."' AND `fj_block` = ".($_POST['fj_block']-1)." AND `fj_max` = ".$_POST['fj_max'], $fj_server);
		if ($fj_result->num_rows==1) {
			$fj_found = true;
			break;
		}
	}
	if ($fj_found===false) fj_return(2);
}
$fj_scan = sha1($_POST['fj_jar'].$_POST['fj_file'].$_POST['fj_block'].$_POST['fj_max'].$fj_sig.$_POST['fj_content']);
$fj_transfer_array = array(false, fj_load_get());
foreach ($fj_transfer_array as $fj_transfer_value) fj_db("INSERT IGNORE INTO `fj_load`.`fj_safe` (`fj_scan`, `fj_jar`, `fj_sig`, `fj_file`, `fj_block`, `fj_max`, `fj_exp`, `fj_rate`, `fj_start`, `fj_last`, `fj_streak`, `fj_stage`, `fj_content`) VALUES('".$fj_scan."', '".$_POST['fj_jar']."', '".$fj_sig."', '".$_POST['fj_file']."', ".$_POST['fj_block'].", ".$_POST['fj_max'].", 0, 0, ".$fj_time.", 0, 0, 1, '".$_POST['fj_content']."')", $fj_transfer_value);
unset($_POST['fj_content']);
if ($_POST['fj_block']==$_POST['fj_max']) {
	sleep(1);
	$fj_block_counter = 1;
	while ($fj_block_counter<=$_POST['fj_max']) {
		$fj_block_exist = 0;
		foreach ($fj_load_select as $fj_server) {
			$fj_result = fj_db("SELECT `fj_scan`, `fj_max`, `fj_content` FROM `fj_load`.`fj_safe` WHERE `fj_jar` = '".$_POST['fj_jar']."' AND `fj_sig` = '".$fj_sig."' AND `fj_file` = '".$_POST['fj_file']."' AND `fj_block` = '".$fj_block_counter."' AND `fj_stage` = 1", $fj_server);
			if ($fj_result->num_rows==1) {
				$fj_row = $fj_result->fetch_assoc();
				if ($fj_row['fj_max']==$_POST['fj_max']&&$fj_row['fj_scan']==sha1($_POST['fj_jar'].$_POST['fj_file'].$fj_block_counter.$fj_row['fj_max'].$fj_sig.$fj_row['fj_content'])) $fj_block_exist++;
			}
		}
		if ($fj_block_exist<2) {
			foreach ($fj_load_select as $fj_server) {
				fj_db("DELETE FROM `fj_load`.`fj_safe` WHERE `fj_jar` = '".$_POST['fj_jar']."' AND `fj_sig` = '".$fj_sig."' AND `fj_file` = '".$_POST['fj_file']."'", $fj_server);
			}
			fj_return(3);
		}
		$fj_block_counter++;
	}
	foreach ($fj_load_select as $fj_server) {
		fj_db("UPDATE `fj_load`.`fj_safe` SET `fj_stage` = 2 WHERE `fj_jar` = '".$_POST['fj_jar']."' AND `fj_sig` = '".$fj_sig."' AND `fj_file` = '".$_POST['fj_file']."' AND `fj_stage` = 1", $fj_server);
	}
	fj_db("UPDATE `fj_front`.`fj_jar` SET `fj_max` = `fj_max` + ".$_POST['fj_max']." WHERE `fj_jar` = '".$_POST['fj_jar']."'", array(4000, "fj_front"));
}
fj_return(1);