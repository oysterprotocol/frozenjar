<?php
include("fj_common.php");

if (!isset($_POST['fj_jar'])||!is_hash($_POST['fj_jar'], 32)||!isset($_POST['fj_sig_pre'])||!is_hash($_POST['fj_sig_pre'], 40)) exit;

$fj_time = time();
$fj_sig = substr(sha1($_POST['fj_sig_pre']), 0, 10);
$fj_result = fj_db("SELECT `fj_freeze` FROM `fj_front`.`fj_jar` WHERE `fj_jar` = '".$_POST['fj_jar']."' AND `fj_sig` = '".$fj_sig."'");
if ($fj_result->num_rows!=1) exit;
if ($fj_result->fetch_assoc()['fj_freeze']>$fj_time) fj_db("UPDATE `fj_front`.`fj_jar` SET `fj_state` = ".$fj_time." WHERE `fj_jar` = '".$_POST['fj_jar']."' AND `fj_sig` = '".$fj_sig."'");