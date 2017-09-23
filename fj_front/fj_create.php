<?php
include("fj_common.php");
ignore_user_abort(true);

if (!isset($_POST['fj_sig_pre'])||!is_hash($_POST['fj_sig_pre'], 40)) exit;

$fj_time = time();
$fj_jar = fj_rand(32);
$fj_sig = substr(sha1($_POST['fj_sig_pre']), 0, 10);
$fj_address = fj_address_get();
$fj_exp = $fj_time+12960000;//5 months
$fj_size = 5;//5 megabytes
fj_db("INSERT INTO `fj_front`.`fj_address` ('fj_address', `fj_jar`, `fj_amount`, `fj_worth`, `fj_start`, `fj_last`) VALUES('".$fj_address."', '".$fj_jar."', 0, 0, ".$fj_time.", 0)");
fj_db("INSERT INTO `fj_front`.`fj_jar` (`fj_jar`, `fj_sig`, `fj_exp`, `fj_size`, `fj_start`, `fj_last`, `fj_address`) VALUES('".$fj_jar."', '".$fj_sig."', ".$fj_exp.", ".$fj_size.", ".$fj_time.", 0, $fj_address)");
echo "FJ_PASS";