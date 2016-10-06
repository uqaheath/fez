<?php

include_once(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."config.inc.php");
if (php_sapi_name()==="cli") {
  getFezACML();
}

function getFezACML() {
  $db = DB_API::get();

  $stmt = "SELECT rek_pid
                 FROM " . APP_TABLE_PREFIX . "record_search_key
                 WHERE rek_security_inherited IS NULL";
  $pids = [];
  try {
    $pids = $db->fetchCol($stmt);
  } catch (Exception $e) {
  }
  $count = count($pids);
  $i = 0;
  foreach ($pids as $pid) {
    $i++;
    echo "Updating $i/$count\n";
    Record::getACML($pid);
  }
}