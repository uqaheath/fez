CREATE TABLE IF NOT EXISTS %TABLE_PREFIX%fez_tombstone (
  `tom_id` int(11) NOT NULL AUTO_INCREMENT,
  `tom_pid_main` varchar(45) DEFAULT NULL,
  `tom_pid_rel` varchar(45) DEFAULT NULL,
  `tom_delete_ts` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tom_id`)
);