<?php
/**
 * This is the page that updates the votes in the databse.
 * It returns a respons array in json format
 * 1. The wp_nonce is used as security
 * 2. Check that request comes from same host, to prevent external scripts
 * 3. The submited parameters are filtered for SQL-injection 
 */
require_once(__DIR__ . '/../../../../wp-config.php');
check_ajax_referer( 'reptilo-vote'.$_REQUEST['postid'], 'security' );
$host = get_bloginfo('url');
$referer = 'http://' . $_SERVER['HTTP_HOST'];
if (strcmp($host, $referer) == 0) {
  !empty($_REQUEST['postid']) ? $postid = addslashes($_REQUEST['postid']) : $postid = '';
  !empty($_REQUEST['answer']) ? $answer = addslashes($_REQUEST['answer']) : $answer = '';
  if ($postid != '' && $answer != '') {
    $rc = new ReptiloVote($postid);
    $response = $rc->update($answer);
  }
} else {
  $response = array(
      'status' => 'error'
  );
}
//return it in json
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
echo json_encode($response);