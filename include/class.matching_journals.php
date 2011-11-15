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
// | Authors: Lachlan Kuhn <l.kuhn@library.uq.edu.au>                     |
// +----------------------------------------------------------------------+
include_once(APP_INC_PATH . "class.auth.php");
define("TEST",   		 			false); // limit to 250 records only if TRUE
define("TEST_WHERE",				''); // Adds this condition to the where statement for eg testing single pids
define("SIMILARITY_THRESHOLD",		80);    // These similarity functions aren't currently invoked
define("WINDOW_START",				'2005-01-01 00:00:00');

class RJL
{
    var $dupeList = "";
    var $previousCount = 0;
    var $runType = "0";

	function matchAll()
	{
        $userDetails = User::getDetailsByID(APP_SYSTEM_USER_ID);
        Auth::createLoginSession($userDetails['usr_username'], $userDetails['usr_full_name'], $userDetails['usr_email'], '');
		echo "======================================\n";
		echo "RJL Matching Utility\n";
		echo date('d/m/Y H:i:s') . "\n";
		
		$matchesI = array(); // ISSN matches
		$matchesT = array(); // Journal title matches
		$matchesC = array(); // Conference title matches
		$matchesS = array(); // Similar title matches
		$matchesM = array(); // Manual matches
		$matches = array();  // All matches
		
		$candidateJournals = RJL::getCandidateJournals();
		$candidateISSNs = RJL::getCandidateISSNs();
		$candidateConferences = RJL::getCandidateConfs();		
		$rankedJournals = RJL::getRankedJournals();
		$rankedJournalISSNs = RJL::getISSNsRJL();
		$manualMatches = RJL::getManualMatches();
		
		/* Perform normalisation */
		$normalisedCandidateJournals = RJL::normaliseListOfTitles($candidateJournals);
		$normalisedCandidateISSNs = RJL::normaliseListOfISSNs($candidateISSNs);
		$normalisedCandidateConferences = RJL::normaliseListOfTitles($candidateConferences);
		$normalisedRankedJournals = RJL::normaliseListOfTitles($rankedJournals);
		$normalisedRankedJournalISSNs = $rankedJournalISSNs;

		/* See how many unique records we're really talking about here */
		$master = array_merge($candidateJournals, $candidateISSNs, $candidateConferences);
		$master = RJL::keyMasterList($master);

		/* Print some information about the number of items found */
		echo "Number of candidate journal titles: " . sizeof($candidateJournals) . "\n";
		echo "Number of candidate ISSNs: " . sizeof($normalisedCandidateISSNs) . "\n";
		echo "Number of candidate conferences: " . sizeof($normalisedCandidateConferences) . "\n";
		echo "Total number of candidate records: " . sizeof($master) . "\n";
		echo "Number of ranked journals: " . sizeof($rankedJournals) . "\n";
		echo "Number of ranked ISSNs: " . sizeof($normalisedRankedJournalISSNs) . "\n";
		ob_flush();

    /* Look for manual matches first because it should be authoritative over any dupe pid/year era id combos */
        RJL::lookForManualMatches($normalisedCandidateJournals, $manualMatches, $matches);
        echo "Number after manual matches: " . sizeof($matches) . "\n";
          ob_flush();


		/* Look for ISSN matches */
		RJL::lookForMatchesByISSN($normalisedCandidateISSNs, $normalisedRankedJournalISSNs, $matches);
		echo "Number after ISSN matches: " . sizeof($matches) . "\n";
        ob_flush();
		/* Look for title matches (string normalisation and comparison) */
/*        echo " ranks j s\n";
        print_r($normalisedRankedJournals);
        echo " candidate j s\n";
        print_r($normalisedCandidateJournals);
*/
		RJL::lookForMatchesByStringComparison($normalisedCandidateJournals, $normalisedRankedJournals, $matches, "T");
		echo "Number after normalised string matches (journal): " . sizeof($matches) . "\n";
        ob_flush();
		/* Look for conference matches (string normalisation and comparison) */
		RJL::lookForMatchesByStringComparison($normalisedCandidateConferences, $normalisedRankedJournals, $matches, "C");
		echo "Number after normalised string matches (conference): " . sizeof($matches) . "\n";
        ob_flush();
		/* Look for similar title matches (uses normalised strings for comparison) */
		/*RJL::lookForMatchesBySimilarStrings($normalisedCandidateJournals, $normalisedRankedJournals, $matchesS);
		echo "Number of similar string matches: " . sizeof($matchesS) . "\n";
		*/
		
		/* Assemble list of all matches */
//		$matches = array_merge($matchesT, $matchesI, $matchesM, $matchesC, $matchesS);
		echo "Total number of matches: " . sizeof($matches) . "\n";
        ob_flush();
		/* Subtract matches from list before printing unmatched */
		/*
		$unmatched = $normalisedCandidateJournals;
		RJL::subtractMatchesFromCandidates(&$unmatched, $matchesI);
		RJL::subtractMatchesFromCandidates(&$unmatched, $matchesS);
		RJL::subtractMatchesFromCandidates(&$unmatched, $matchesT);
		RJL::subtractMatchesFromCandidates(&$unmatched, $matchesM);
*/
		echo "Number of ISSN matches: " . sizeof($matchesI) . "\n";
        ob_flush();
		echo "Number of journal title matches: " . sizeof($matchesT) . "\n";
		echo "Number of conference title matches: " . sizeof($matchesC) . "\n";
		echo "Number of manual matches: " . sizeof($matchesM) . "\n";
		echo "Total number of matches: " . sizeof($matches) . "\n";
        ob_flush();
        echo "Dupe list:\n\n".$this->dupeList."\n";

/*
		// PRINT UNMATCHED JOURNALS (SPECIAL CASE)
		// Remove the title matches from the original candidate journal list
		$nonMatchingJournals = array_diff($coreJournals, RJL::keyMasterList($matchesT));
		echo "Number of non-matching journals: " . sizeof($nonMatchingJournals) . "\n";
		$nonMatchingJournals = array_diff($nonMatchingJournals, RJL::keyMasterList($matchesI)); // Also remove the ISSN matches
		echo "Number of non-matching journals after ISSN subtraction: " . sizeof($nonMatchingJournals) . "\n";
		$nonMatchingJournals = array_diff($nonMatchingJournals, RJL::keyMasterList($matchesM)); // Next remove the manual matches
		echo "Number of non-matching journals after manual match subtraction: " . sizeof($nonMatchingJournals) . "\n";
		
		// PRINT UNMATCHED CONFERENCES (SPECIAL CASE)
		// Remove the title matches from the original candidate conference list
		$nonMatchingConferences = array_diff($coreConferences, RJL::keyMasterList($matchesC));
		echo "Number of non-matching conferences: " . sizeof($nonMatchingConferences) . "\n";
		$nonMatchingConferences = array_diff($nonMatchingConferences, RJL::keyMasterList($matchesC)); // Also remove the ISSN matches
		echo "Number of non-matching conferences after ISSN subtraction: " . sizeof($nonMatchingConferences) . "\n";
		$nonMatchingConferences = array_diff($nonMatchingConferences, RJL::keyMasterList($matchesC)); // Next remove the manual matches
		echo "Number of non-matching conferences after manual match subtraction: " . sizeof($nonMatchingConferences) . "\n";
		*/

        // Email the dupes list to the espace admin email address
        $mail = new Mail_API;
       	$mail->setTextBody(stripslashes($this->dupeList));
       	$subject = '['.APP_NAME.'] - Duplicate Journal Matches found, please resolve manually using manual matching';
       	$from = APP_EMAIL_SYSTEM_FROM_ADDRESS;
       	$to = APP_ADMIN_EMAIL;
       	$mail->send($from, $to, $subject, false);


		/* Subtract from any match results those PIDs that are either black-listed, or manually mapped */
		$matches = array_diff_key($matches, matching::getMatchingExceptions("J"));
		echo " About to run inserts \n";
        ob_flush();
		/* Insert all the found matches */
		RJL::runInserts($matches);
		return;
	}
	
	
	
	function matchOne($pid)
	{
		echo "Match a single PID here. Rar!";
		
		// LKDB - TODO!
		
		return;
	}
	
	
	
	function getCandidateJournals()
	{
		$log = FezLog::get();
		$db = DB_API::get();
		
		echo "Running query to build candidate journals list ... ";
		$candidateJournals = array();
	
		$stmt = "	
			SELECT
				rek_pid AS record_pid,
				rek_journal_name AS journal_title
			FROM ".TEST_WHERE."
				" . APP_TABLE_PREFIX . "record_search_key, " . APP_TABLE_PREFIX . "record_search_key_journal_name, " . APP_TABLE_PREFIX . "xsd_display
			WHERE ".TEST_WHERE."
				" . APP_TABLE_PREFIX . "record_search_key_journal_name.rek_journal_name_pid = " . APP_TABLE_PREFIX . "record_search_key.rek_pid
				AND rek_display_type = xdis_id
				AND " . APP_TABLE_PREFIX . "record_search_key.rek_date >= '" . WINDOW_START . "'
				AND xdis_title IN ('Conference Paper', 'Conference Item', 'Journal Article', 'RQF 2006 Journal Article', 'RQF 2006 Conference Paper', 'RQF 2007 Journal Article', 'RQF 2007 Conference Paper', 'Online Journal Article')
			ORDER BY
				journal_title ASC
		";
		
		if (TEST) {
			$stmt .= " LIMIT 250;";
		}
		
		try {
			$result = $db->fetchAll($stmt, array(), Zend_Db::FETCH_ASSOC);
		}
		catch(Exception $ex) {
			$log->err($ex);
			return '';
		}
		
		if (count($result) > 0) {
			foreach ($result as $key => $row) {
				$candidateJournals[$row['record_pid']] = $row['journal_title'];
			}
		}
		
		echo "done.\n";
		
		return $candidateJournals;
	}
	
	
	
	function getCandidateISSNs()
	{
		$log = FezLog::get();
		$db = DB_API::get();
	
		echo "Running query to build candidate ISSNs list ... ";
		$candidateISSNs = array();
	
		$stmt = "
			SELECT
				rek_pid AS record_pid,
				rek_issn AS issn
			FROM
				" . APP_TABLE_PREFIX . "record_search_key, " . APP_TABLE_PREFIX . "record_search_key_issn, " . APP_TABLE_PREFIX . "xsd_display
			WHERE ".TEST_WHERE."
				" . APP_TABLE_PREFIX . "record_search_key_issn.rek_issn_pid = " . APP_TABLE_PREFIX . "record_search_key.rek_pid
				AND rek_display_type = xdis_id
				AND " . APP_TABLE_PREFIX . "record_search_key.rek_date >= '" . WINDOW_START . "'
				AND xdis_title IN ('Conference Paper', 'Conference Item', 'Journal Article', 'RQF 2006 Journal Article', 'RQF 2006 Conference Paper', 'RQF 2007 Journal Article', 'RQF 2007 Conference Paper', 'Online Journal Article')
			ORDER BY
				issn ASC
		";
		
		if (TEST) {
			$stmt .= " LIMIT 250;";
		}
		
		try {
			$result = $db->fetchAll($stmt, array(), Zend_Db::FETCH_ASSOC);
		}
		catch(Exception $ex) {
			$log->err($ex);
			return '';
		}
		
		if (count($result) > 0) {
			foreach ($result as $key => $row) {
				$candidateISSNs[$row['record_pid']] = $row['issn'];
			}
		}
		
		echo "done.\n";
		
		return $candidateISSNs;
	}
	
	
	
	function getCandidateConfs()
	{
		$log = FezLog::get();
		$db = DB_API::get();
		
		echo "Running query to build candidate conference list ... ";
		$candidateConferences = array();

		$stmt = "	
			SELECT
				rek_pid AS record_pid,
				rek_proceedings_title AS conference_name
			FROM ".TEST_WHERE."
				" . APP_TABLE_PREFIX . "record_search_key, " . APP_TABLE_PREFIX . "record_search_key_proceedings_title, " . APP_TABLE_PREFIX . "xsd_display
			WHERE
				" . APP_TABLE_PREFIX . "record_search_key_proceedings_title.rek_proceedings_title_pid = " . APP_TABLE_PREFIX . "record_search_key.rek_pid
				AND rek_display_type = xdis_id
				AND " . APP_TABLE_PREFIX . "record_search_key.rek_date >= '" . WINDOW_START . "'
				AND xdis_title IN ('Conference Paper', 'Conference Item', 'Journal Article', 'RQF 2006 Journal Article', 'RQF 2006 Conference Paper', 'RQF 2007 Journal Article', 'RQF 2007 Conference Paper')
			ORDER BY
				conference_name ASC
		";
		
		if (TEST) {
			$stmt .= " LIMIT 250;";
		}
		
		try {
			$result = $db->fetchAll($stmt, array(), Zend_Db::FETCH_ASSOC);
		}
		catch(Exception $ex) {
			$log->err($ex);
			return '';
		}
		
		if (count($result) > 0) {
			foreach ($result as $key => $row) {
				$candidateConferences[$row['record_pid']] = $row['conference_name'];
			}
		}
		
		echo "done.\n";
		
		return $candidateConferences;
	}
	
	
	
	function getRankedJournals()
	{
		$log = FezLog::get();
		$db = DB_API::get();
				
		echo "Running query to build ranked journals list ... ";
		$rankedJournals = array();

		$stmt = "
			SELECT
				jnl_id AS jnl_id,
				jnl_journal_name AS title,
				jnl_era_year
			FROM
				" . APP_TABLE_PREFIX . "journal
			ORDER BY
				jnl_journal_name ASC;
		";
		
		try {
			$result = $db->fetchAll($stmt, array(), Zend_Db::FETCH_ASSOC);
		}
		catch(Exception $ex) {
			$log->err($ex);
			return '';
		}
		
//		if (count($result) > 0) {
//			foreach ($result as $row) {
//		    	$rankedJournals[$row['jnl_id']] = $row['title'];
//		    }
//		}
		$rankedJournals = $result;
		echo "done.\n";
		
		return $rankedJournals;
	}
	
	
	
	function getManualMatches()
	{
		echo "Retrieving list of manual matches ... ";
		$manualMatches = array(
		array("jnl_id" => "108", "title"  => "physical review a", "jnl_era_year" => 2010),
		array("jnl_id" => "21332", "title"  => "physical review a", "jnl_era_year" => 2012),
		array("jnl_id" => "757", "title"  => "physical review b", "jnl_era_year" => 2010),
		array("jnl_id" => "21432", "title"  => "physical review b", "jnl_era_year" => 2012),
		array("jnl_id" => "13933", "title"  => "british medical journal", "jnl_era_year" => 2010),
		array("jnl_id" => "39113", "title"  => "british medical journal", "jnl_era_year" => 2012),
		array("jnl_id" => "20157", "title"  => "media international australia", "jnl_era_year" => 2010),
		array("jnl_id" => "27279", "title"  => "media international australia", "jnl_era_year" => 2012),
		array("jnl_id" => "5615", "title"  => "arena magazine", "jnl_era_year" => 2010),
		array("jnl_id" => "32629", "title"  => "arena magazine", "jnl_era_year" => 2012),
		array("jnl_id" => "3083", "title"  => "cochrane database of systematic reviews", "jnl_era_year" => 2010),
		array("jnl_id" => "30396", "title"  => "cochrane database of systematic reviews", "jnl_era_year" => 2012),
		array("jnl_id" => "7153", "title"  => "proceedings of the national academy of sciences of the united states of america", "jnl_era_year" => 2010),
		array("jnl_id" => "22152", "title"  => "proceedings of the national academy of sciences of the united states of america", "jnl_era_year" => 2012),
		array("jnl_id" => "3100", "title"  => "lancet", "jnl_era_year" => 2010),
		array("jnl_id" => "30411", "title"  => "lancet", "jnl_era_year" => 2012),
		array("jnl_id" => "16667", "title"  => "environmental science and technology", "jnl_era_year" => 2010),
		array("jnl_id" => "24080", "title"  => "environmental science and technology", "jnl_era_year" => 2012),
		array("jnl_id" => "2724", "title"  => "langmuir", "jnl_era_year" => 2010),
		array("jnl_id" => "21691", "title"  => "langmuir", "jnl_era_year" => 2012),
		array("jnl_id" => "7059", "title"  => "journal of experimental biology", "jnl_era_year" => 2010),
		array("jnl_id" => "22137", "title"  => "journal of experimental biology", "jnl_era_year" => 2012),
		array("jnl_id" => "2616", "title"  => "journal of physical chemistry b", "jnl_era_year" => 2010),
		array("jnl_id" => "21680", "title"  => "journal of physical chemistry b", "jnl_era_year" => 2012)
		);
		echo "done.\n";
		
		return $manualMatches;
	}
	
	
	
	function getISSNsRJL()
	{
		$log = FezLog::get();
		$db = DB_API::get();
		
		echo "Running query to build ranked journal ISSN list ... ";
		$rankedJournalISSNs = array();

		$stmt = "
			SELECT
				jni_issn,
				jni_id,
				jnl_era_year,
				jnl_id
			FROM
				" . APP_TABLE_PREFIX . "journal,
				" . APP_TABLE_PREFIX . "journal_issns
			WHERE
				" . APP_TABLE_PREFIX . "journal.jnl_id = " . APP_TABLE_PREFIX . "journal_issns.jni_jnl_id
			ORDER BY
				jni_issn ASC,
				jni_issn_order ASC;
		";
		
		try {
			$result = $db->fetchAll($stmt, array(), Zend_Db::FETCH_ASSOC);
		}
		catch(Exception $ex) {
			$log->err($ex);
			return '';
		}
		
		if (count($result) > 0) {
			foreach ($result as $key => $row) {
                $issn = RJL::normaliseISSN($row['jni_issn']);
                $rankedJournalISSNs[$row['jni_id']]['jni_issn'] = $issn;
                $rankedJournalISSNs[$row['jni_id']]['jnl_id'] = $row['jnl_id'];
                $rankedJournalISSNs[$row['jni_id']]['jnl_era_year'] = $row['jnl_era_year'];
		    }
		}
		
		echo "done.\n";
		
		return $rankedJournalISSNs;
	}
	

	
	function normaliseListOfTitles($titles)
	{
		foreach ($titles as &$title) {
			$title['title'] = RJL::normaliseTitle($title['title']);
		}
		
		return $titles;
	}
	
	
	
	function normaliseListOfISSNs($issns)
	{
		foreach ($issns as &$issn) {
			$issn = RJL::normaliseISSN($issn);
		}
		
		return $issns;
	}
	
	
	
	function normaliseTitle($title)
	{
		$title = strtolower($title);
		$title = RJL::strip_punctuation($title);
		$title = trim($title);

		return $title;
	}
	
	
	
	function strip_punctuation($text)
	{
	    $text = preg_replace("/[:]/", " ", $text); // Replace colons with spaces
	    $text = preg_replace("/[&]/", "and", $text); // Swap ampersands with the word "and"
	    $text = preg_replace("/[^a-z0-9\s]/", " ", $text); // force a-z, 0-9
	    if (strpos($text, "the ") !== false && strpos($text, "the ") == '0') {
	    	$text = substr_replace($text, "", 0, 4); // remove any leading "the ", if one is encountered
	    }
	    $text = preg_replace("/\s{2,}/", " ", $text); // strip any double / induced whitespace
	    
	    return $text;
	}
	
	
	
	function normaliseISSN($issn)
	{
		$issn = preg_replace("/[^0-9\-X]/", "", $issn);
		
		return $issn;		
	}
	
	
	
	function lookForMatchesByISSN($check, $against, &$matches)
	{
		echo "Running ISSN match ... \n";
        $bgp = new BackgroundProcess();
        $bgp->register(array(), Auth::getUserID());
        $eta_cfg = $bgp->getETAConfig();
		/* Step through each source item */
        $counter = 0;
        $this->previousCount = 0;
        $record_count = count($check);
		foreach ($check as $sourceKey => $sourceVal) {
			$counter++;
			/* Reset match position and value */
			$earliestMatch = '';
			$earliestMatchPosition = 999999;
            // Get the ETA calculations
            if (($this->runType == "1" || $counter == 10) && $counter % 10 == 0 || $counter == $record_count) {
                $eta = $bgp->getETA($counter, $record_count, $eta_cfg);

                $msg = "(" . $counter . "/" . $record_count . ") ".
                     "(Avg " . $eta['time_per_object'] . "s per Object. " .
                     "Expected Finish " . $eta['expected_finish'] . ")";
                if ($this->previousCount != 0) {
                    for($x = 0; $x<$this->previousCount; $x++) {
                        echo "\x08"; //echo a backspace
                    }
                }
                echo $msg;
                $this->previousCount = strlen($msg);

                ob_flush();
            }
			/* Attempt to match it against each target item */
			foreach ($against as $targetVal) {
				/* Look for the target strng inside the source */
				if (substr_count($sourceVal, $targetVal['jni_issn']) > 0) { //haystack, needle
                    $existsAlready = false;
                    foreach ($matches as $match) {
                        if ($match['year'] == $targetVal['jnl_era_year'] && $match['pid'] == $sourceKey) {
                            $existsAlready = true;
                            $this->dupeList .= "Found ".$sourceKey." matched more than once on journal ISSN.\n ".
                                "PID Journal name: ".$sourceVal."\n".
                                "Existing Match jnl_id: ".$match['matching_id']." - Year: ".$match['year']."\n".
                                "New Candidate Match: ".$targetVal['jnl_id']." - Year: ".$targetVal['jnl_era_year']."\n\n";
                        }
                    }
                    if ($existsAlready !== true) {
                        $matches[] = array('pid' => $sourceKey, 'matching_id' => $targetVal['jnl_id'], 'year' => $targetVal['jnl_era_year']);
                    }
					//echo "I";
				}
			}
		}
		
		echo " done.\n";
		
		return;
	}
	
	
	
	function lookForMatchesByStringComparison($check, $against, &$matches, $type)
	{
		echo "Running normalised string match ... ";
        $bgp = new BackgroundProcess();
        $bgp->register(array(), Auth::getUserID());
        $eta_cfg = $bgp->getETAConfig();
		/* Step through each source item */
        $counter = 0;
        $record_count = count($check);
        $this->previousCount = 0;
        ob_flush();
		/* Step through each source item */
		foreach ($check as $sourceKey => $sourceVal) {
            $counter++;
            // Get the ETA calculations
            if (($this->runType == "1" || $counter == 10) && $counter % 10 == 0 || $counter == $record_count) {
                $eta = $bgp->getETA($counter, $record_count, $eta_cfg);

                $msg = "(" . $counter . "/" . $record_count . ") ".
                     "(Avg " . $eta['time_per_object'] . "s per Object. " .
                     "Expected Finish " . $eta['expected_finish'] . ")";
                if ($this->previousCount != 0) {
                    for($x = 0; $x<$this->previousCount; $x++) {
                        echo "\x08"; //echo a backspace
                    }
                }
                echo $msg;
                $this->previousCount = strlen($msg);

                ob_flush();
            }

			/* Attempt to match it against each target item */
			foreach ($against as $targetVal) {
				/* Test for exact string match */
				if ($sourceVal == $targetVal['title']) {
					//echo $type;
//					$matches[$sourceKey] = $targetKey;
                    $existsAlready = false;
                    foreach ($matches as $match) {
                        if ($match['year'] == $targetVal['jnl_era_year'] && $match['pid'] == $sourceKey) {
                            $existsAlready = true;
                            $this->dupeList .= "Found ".$sourceKey." matched more than once on a journal name.\n ".
                                "PID Journal name: ".$sourceVal."\n".
                                "Existing Match jnl_id: ".$match['matching_id']." - Year: ".$match['year']."\n".
                                "New Candidate Match: ".$targetVal['jnl_id']." - Year: ".$targetVal['jnl_era_year']."\n\n";
                        }
                    }
                    if ($existsAlready !== true) {
                        $matches[] = array('pid' => $sourceKey, 'matching_id' => $targetVal['jnl_id'], 'year' => $targetVal['jnl_era_year']);
                    }



//					$matches[] = array('pid' => $sourceKey, 'matching_id' => $targetKey);
				}
			}
		}
        $bgp->setState(2);
		echo " done.\n";
		
		return;
	}



	function lookForMatchesBySimilarStrings($check, $against, &$matches)
	{
		echo "Running similar strings match ... ";

        $bgp = new BackgroundProcess();
        $bgp->register(array(), Auth::getUserID());
        $eta_cfg = $bgp->getETAConfig();
		/* Step through each source item */
        $counter = 0;
        $record_count = count($check);
        $this->previousCount = 0;
		/* Step through each source item */
		foreach ($check as $sourceKey => $sourceVal) {
            $counter++;
            // Get the ETA calculations
            if (($this->runType == "1" || $counter == 10) && $counter % 10 == 0 || $counter == $record_count) {
                $eta = $bgp->getETA($counter, $record_count, $eta_cfg);

                $msg = "(" . $counter . "/" . $record_count . ") ".
                     "(Avg " . $eta['time_per_object'] . "s per Object. " .
                     "Expected Finish " . $eta['expected_finish'] . ")";
                if ($this->previousCount != 0) {
                    for($x = 0; $x<$this->previousCount; $x++) {
                        echo "\x08"; //echo a backspace
                    }
                }
                echo $msg;
                $this->previousCount = strlen($msg);

                ob_flush();
            }
			foreach ($against as $targetKey => $targetVal) {
				similar_text($sourceVal, $targetVal['title'], $similarity);
				if ($similarity > SIMILARITY_THRESHOLD && $similarity != 100) {


                    $existsAlready = false;
                    foreach ($matches as $match) {
                        if ($match['year'] == $targetVal['jnl_era_year'] && $match['pid'] == $sourceKey) {
                            $existsAlready = true;
                            $this->dupeList .= "Found ".$sourceKey." matched more than once on a similar journal name.\n ".
                                "PID Journal name: ".$sourceVal."\n".
                                "Existing Match jnl_id: ".$match['matching_id']." - Year: ".$match['year']."\n".
                                "New Candidate Match: ".$targetVal['jnl_id']." - Year: ".$targetVal['jnl_era_year']."\n\n";
                        }
                    }
                    if ($existsAlready !== true) {
                        $matches[] = array('pid' => $sourceKey, 'matching_id' => $targetVal['jnl_id'], 'year' => $targetVal['jnl_era_year']);
                    }


					//echo $sourceVal . " :: " . $targetVal . "\n"; // LKDB
					//echo "Similarity = " . $similarity . "%\n\n"; // LKDB
					
					//echo "S";
//					$matches[$sourceKey] = $targetKey;
//                    $matches[] = array('pid' => $sourceKey, 'matching_id' => $targetKey);
				}
			}
		}
        $bgp->setState(2);
		echo " done.\n";
		
		return;
	}
	
	
	
	function subtractMatchesFromCandidates(&$candidates, $matches)
	{
		echo "Removing matches from journal pool ... ";
		
		foreach ($matches as $matchKey => $matchVal){
			unset($candidates[$matchKey]);
		}
		
		echo " done.\n";
		
		return;
	}
	
	
	
	function lookForManualMatches($check, $manualMatches, &$matches)
	{
		echo "Checking un-matched journals for manual matches... \n";
        $bgp = new BackgroundProcess();
        $bgp->register(array(), Auth::getUserID());
        $eta_cfg = $bgp->getETAConfig();
		/* Step through each source item */
        $counter = 0;
        $record_count = count($check);
        $this->previousCount = 0;
		foreach ($check as $sourceKey => $sourceVal) {
            $counter++;
            if (($this->runType == "1" || $counter == 10) && $counter % 10 == 0 || $counter == $record_count) {
                $eta = $bgp->getETA($counter, $record_count, $eta_cfg);
                $msg = "(" . $counter . "/" . $record_count . ") ".
                     "(Avg " . $eta['time_per_object'] . "s per Object. " .
                     "Expected Finish " . $eta['expected_finish'] . ")";
                ob_flush();
                if ($this->previousCount != 0) {
                    for($x = 0; $x<$this->previousCount; $x++) {
                        echo "\x08"; //echo a backspace
                    }
                }
                echo $msg;
                $this->previousCount = strlen($msg);

                ob_flush();

            }
			/* Attempt to match it against each target item */
			foreach ($manualMatches as $targetVal) {
				/* Test for exact string match */
				if ($sourceVal == $targetVal['title']) {
					//echo "M";
//					$matches[$sourceKey] = $targetKey;
                    $existsAlready = false;
                    foreach ($matches as $match) {
                        if ($match['year'] == $targetVal['jnl_era_year'] && $match['pid'] == $sourceKey && $match['matching_id'] == $targetVal['jnl_id']) {
                            $existsAlready = true;
                        }
                    }
                    if ($existsAlready !== true) {
                        $matches[] = array('pid' => $sourceKey, 'matching_id' => $targetVal['jnl_id'], 'year' => $targetVal['jnl_era_year']);
                    }
//                    $matches[] = array('pid' => $sourceKey, 'matching_id' => $sourceKey);
				}				
			}
		}
		$bgp->setState(2);
		echo " done!\n";
		
		return;
	}
	
	
	
	function keyMasterList($toClean)
	{
		$clean = array();		
		foreach ($toClean as $key => $val) {
			array_push($clean, $key);
		}
		
		return $clean;
	}
	
	
	
	function runInserts($matches)
	{
		$log = FezLog::get();
		$db = DB_API::get();
		
		echo "Running ".count($matches)." insertion queries on eSpace database ... ";
		
		foreach ($matches as $match) {
			$stmt = "INSERT INTO " . APP_TABLE_PREFIX . "matched_journals (mtj_pid, mtj_jnl_id, mtj_status) VALUES ('" . $match['pid'] . "', '" . $match['matching_id'] . "', 'A') ON DUPLICATE KEY UPDATE mtj_jnl_id = '" . $match['matching_id'] . "';";
            if (TEST_WHERE != '') {
    			echo $stmt."\n";
            }
            ob_flush();
			try {
				$db->exec($stmt);
			}
			catch(Exception $ex) {
				$log->err($ex);
				die('There was a problem with the query ' . $stmt);
			}
			
			//echo $stmt . "\n"; // This will tell us what's actually going to be run.
		}
		
		echo "done.\n";
		
		return;
	}

}
