<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Fez - Digital Repository System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2005, 2006 The University of Queensland,               |
// | Australian Partnership for Sustainable Repositories,                 |
// | eScholarship Project                                                 |
// |                                                                      |
// | Some of the Fez code was derived from Eventum (Copyright 2003, 2004  |
// | MySQL AB - http://dev.mysql.com/downloads/other/eventum/ - GPL)      |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Rhys Palmer <r.palmer@library.uq.edu.au>                    |
// +----------------------------------------------------------------------+

include_once(APP_INC_PATH . 'class.background_process.php');
include_once(APP_INC_PATH . 'class.bgp_migrate_fedora.php');

class BackgroundProcess_Db_Load extends BackgroundProcess
{
  function __construct()
  {
    parent::__construct();
    $this->include = 'class.bgp_db_load.php';
    $this->name = 'DB load';
  }

  function run() {
    $this->setState(BGP_RUNNING);
    extract(unserialize($this->inputs));
    $this->loadDb();
    $this->setState(BGP_FINISHED);
  }

  function loadDb() {
    $log = FezLog::get();

    $environment = $_SERVER['APP_ENVIRONMENT'];
    if ($environment !== 'staging') {
      $log->err('DB load failed: Unknown environment - ' . $environment);
      return;
    }
    set_time_limit(0);

    $db = DB_API::get();
    $path = '/tmp/' . $environment . '/export';
    system("rm -Rf ${path}");
    mkdir($path);
    chdir($path);

    if (! system("AWS_ACCESS_KEY_ID=" .
      AWS_KEY. " AWS_SECRET_ACCESS_KEY=" .
      AWS_SECRET .
      " bash -c \"aws s3 cp s3://uql-fez-${environment}-cache/prod.config.inc.php ${path}/prod.config.inc.php\"")
    ) {
      $log->err('DB config failed: Unable to copy Fez prod config from S3');
      return;
    }
    include_once($path . "/prod.config.inc.php");

    if (! system(
      "MYSQL_DUMP_DIR=" . "${path}/../" .
      " MYSQL_HOST=" . DB_LOAD_PROD_SQL_DBHOST .
      " MYSQL_NAME=" . DB_LOAD_PROD_SQL_DBNAME .
      " MYSQL_USER=" . DB_LOAD_PROD_SQL_DBUSER .
      " MYSQL_PASS=" . DB_LOAD_PROD_SQL_DBPASS .
      " bash -c \"" . APP_INC_PATH . "../../util/mysql_dump_aws.sh\"")
    ) {
      $log->err('DB load failed: Unable to export Fez DB');
      return;
    }

    if (! system(
      "AWS_ACCESS_KEY_ID=" . AWS_KEY .
      " AWS_SECRET_ACCESS_KEY=" . AWS_SECRET .
      " bash -c \"aws s3 cp s3://uql-fez-${environment}-cache/fez_config_extras.sql ${path}/fez_config_extras.sql\"")
    ) {
      $log->err('DB load failed: Unable to copy Fez DB from S3');
      return;
    }

    $files = glob($path . "/*.sql");
    foreach ($files as $sql) {
      $tbl = basename($sql, '.sql');
      if (strpos($tbl, 'scd_') !== 0) {
        $db->query('DROP TABLE IF EXISTS ' . $tbl);
      }
      $sql = file_get_contents($sql);
      $db->query($sql);
    }

    $files = glob($path . "/*.txt");

    $dsn = "mysql:host=".APP_SQL_DBHOST.";dbname=".APP_SQL_DBNAME;
    $con = new PDO($dsn, APP_SQL_DBUSER, APP_SQL_DBPASS,
      array(
        PDO::MYSQL_ATTR_LOCAL_INFILE => 1,
      ));

    foreach ($files as $txt) {
      $tbl = basename($txt, '.txt');

      $sql = "LOAD DATA LOCAL INFILE '${path}/" . basename($txt) . "' INTO TABLE ${tbl}" .
        " FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\n'";
      $stmt = $con->prepare($sql);

      $stmt->execute();
    }

    $sql = file_get_contents($path . '/fez_config_extras.sql');
    $db->query($sql);

    system("rm -Rf ${path}");
  }

  /**
   * Check that an existing DB load process isn't scheduled or running
   * @return bool
   */
  function registerCheck() {
    // Check hasn't been scheduled in the past 10 minutes and isn't already running
    return !($this->isScheduledOrRunning(time() - (10 * 60)));
  }
}
