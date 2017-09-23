<?php
include("fj_common.php");
ignore_user_abort(true);

if (!isset($_POST['fj_hash'])||!is_hash($_POST['fj_hash'], 40)||!isset($_POST['fj_jar'])||!is_hash($_POST['fj_jar'], 32)||!isset($_POST['fj_sig_pre'])||!is_hash($_POST['fj_sig_pre'], 40)||!isset($_POST['fj_file'])||!is_hash($_POST['fj_file'], 4)||!isset($_POST['fj_block'])||!isset($_POST['fj_max'])||!isset($_POST['fj_content'])||$_POST['fj_block']>$_POST['fj_max']) exit;
