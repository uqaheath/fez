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
// |          Lachlan Kuhn <l.kuhn@library.uq.edu.au>,                    |
// |          Rhys Palmer <r.rpalmer@library.uq.edu.au>                   |
// +----------------------------------------------------------------------+

include_once(APP_INC_PATH.'class.background_process.php');
include_once(APP_INC_PATH.'class.history.php');
include_once(APP_INC_PATH.'class.xsd_display.php');
include_once(APP_INC_PATH . 'class.bookreaderpdfconverter.php');

class BackgroundProcess_Bulk_Generate_Bookreader_Images extends BackgroundProcess
{
	function __construct()
	{
		parent::__construct();
		$this->include = 'class.bgp_bulk_generate_bookreader_images.php';
		$this->name = 'Bulk Generate Bookreader Images';
        $this->pdfConverter = new bookReaderPDFConverter();
	}

	function run()
	{
		$this->setState(BGP_RUNNING);
		extract(unserialize($this->inputs));

		if (!empty($options)) {
			$this->setStatus("Bulk generating bookreader images");
			$pids = $this->getPidsFromSearchBGP($options);
			$this->setStatus("Generated images for ".count($pids). " resources");
		}

		/*
		 * Copy pid(s) to collection
		 */

		if (!empty($pids) && is_array($pids)) {

			$this->setStatus("Bulk generating bookreader images.");

			foreach ($pids as $pid) {
				$this->setHeartbeat();

                $this->pdfConverter->setPIDQueue($pid, 'pdfToJpg');
                $this->pdfConverter->runQueue();
				 
				$this->setProgress($this->pid_count);
				$this->markPidAsFinished($pid);
			}

			$this->setStatus("Finished ".$this->name);

		}
		$this->setState(BGP_FINISHED);
	}

	function getPidsFromSearchBGP($options)
	{
		$list = Record::getListing($options, array(9,10), 0, 'ALL', 'searchKey0');
		$pids = array_keys(Misc::keyArray($list['list'],'rek_pid'));
		return $pids;
	}
}