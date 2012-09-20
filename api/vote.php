<?php
$postid =  $_POST['postid'];
$answer =  $_POST['answer'];
require_once(__DIR__ . '/../../../../wp-config.php');
$rc = new ReptiloVote($postid);
$rc->update($answer);
?>