<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Fez - Digital Repository System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2005, 2006, 2007 The University of Queensland,         |
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
// | Authors: Christiaan Kortekaas <c.kortekaas@library.uq.edu.au>,       |
// |          Lachlan Kuhn <l.kuhn@library.uq.edu.au>                     |
// |          Rhys Palmer <r.rpalmer@library.uq.edu.au>                   |
// +----------------------------------------------------------------------+

include_once(APP_INC_PATH . "class.bgp_bulk_assign_record_group.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.group.php");

class Bulk_Assign_Record_Group {

	var $bgp;


	function setBGP(&$bgp) {
		$this->bgp = &$bgp;
	}

	function assignGroupPids($pids, $assign_grp_id, $regen=false) {
		$bgp = new BackgroundProcess_Bulk_Assign_Record_Group;
		$bgp->register(serialize(compact('pids', 'assign_grp_id', 'regen')), Auth::getUserID());
	}

	function assignGroupBGP($pid, $assign_grp_id, $regen=false,$topcall=true, $eta_cfg=null, $record_counter=0, $record_count=0)
	{
		$this->regen = $regen;
		$this->bgp->setHeartbeat();
		$this->bgp->setProgress(++$this->pid_count);
		$dbtp =  APP_TABLE_PREFIX;


        // Get the ETA calculations
        $eta = $this->bgp->getETA($record_counter, $record_count, $eta_cfg);

        $this->bgp->setProgress($eta['progress']);
        $this->bgp->setStatus( "Assigning:  '" . $pid . "' " .
                                  "(" . $record_counter . "/" . $record_count . ") <br />".
                                  "(Avg " . $eta['time_per_object'] . "s per Object. " .
                                    "Expected Finish " . $eta['expected_finish'] . ")"
                                );

		$record = new RecordObject($pid);
		$record->updateAssignedGroup($assign_grp_id);  //Broken???

		History::addHistory($pid, null, "", "", true, "Assigned Record to Group ".Group::getName($assign_grp_id)." (".$assign_grp_id.")");
		$this->bgp->setStatus("Finished Bulk Assign Record to Group ".Group::getName($assign_grp_id)."(".$assign_grp_id.") for ".$record->getTitle());
	}
}
