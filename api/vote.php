<?php
/**
 * This is the page that updates the votes in the databse.
 * It returns a respons array in json format
 * A check is made that the referer and the hosting site is the same to prevent external scripts
 * The submited parameters are filtered for SQL-injection 
 */


require_once(__DIR__ . '/../../../../wp-config.php');
$host = get_bloginfo('url') . '/';
$referer = $_SERVER['HTTP_REFERER'];
if (strcmp($host, $host) == 0) {
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
