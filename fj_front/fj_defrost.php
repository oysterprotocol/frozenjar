<?php
include("fj_common.php");

if (!isset($_POST['fj_jar'])||!is_hash($_POST['fj_jar'], 32)||!isset($_POST['fj_sig_pre'])||!is_hash($_POST['fj_sig_pre'], 40)) exit;

$fj_time = time();
$fj_sig = substr(sha1($_POST['fj_sig_pre']), 0, 10);
$fj_result = fj_db("SELECT `fj_state` FROM `fj_front`.`fj_jar` WHERE `fj_jar` = '".$_POST['fj_jar']."' AND `fj_sig` = '".$fj_sig."'");
if ($fj_result->num_rows!=1) exit;
$fj_row = $fj_result->fetch_assoc();
if ($fj_row['fj_state']!=0) fj_return(2);
$fj_load_select = fj_load_select();
foreach ($fj_load_select as $fj_server) {
	fj_db("UPDATE `fj_load`.`fj_safe` SET `fj_stage` = 2 WHERE `fj_jar` = '".$fj_row['fj_jar']."' AND `fj_stage` = 3", $fj_server);
	fj_db("UPDATE `fj_load`.`fj_report` SET `fj_call` = 1 WHERE `fj_jar` = '".$fj_row['fj_jar']."'", $fj_server);
}
fj_db("UPDATE `fj_front`.`fj_jar` SET `fj_defrost` = `fj_defrost` + 1, `fj_state` = 1 WHERE `fj_jar` = '".$_POST['fj_jar']."'");