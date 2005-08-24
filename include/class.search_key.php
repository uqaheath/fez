<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | eSpace - Digital Repository                                          |
// +----------------------------------------------------------------------+
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
// |																	  |
// | Some code and structure is derived from Eventum (GNU GPL - MySQL AB) |
// | http://dev.mysql.com/downloads/other/eventum/index.html			  |
// | Eventum is primarily authored by Jo�o Prado Maia <jpm@mysql.com>     |
// +----------------------------------------------------------------------+
// | Authors: Christiaan Kortekaas <c.kortekaas@library.uq.edu.au>        |
// |          Matthew Smith <m.smith@library.uq.edu.au>                   |
// +----------------------------------------------------------------------+
//
//

/**
 * Class to handle search keys.
 *
 * @version 1.0
 * @author Christiaan Kortekaas <c.kortekaas@library.uq.edu.au>
 * @author Matthew Smith <m.smith@library.uq.edu.au>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.record.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.auth.php");


class Search_Key
{

    /**
     * Method used to remove a given list of search keys.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["items"]);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_key
                 WHERE
                    sek_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
		  return true;
        }
    }


    /**
     * Method used to add a new search key to the system.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

		if (@$HTTP_POST_VARS["sek_simple_used"]) {
			$sek_simple_used = 1;
		} else {
			$sek_simple_used = 0;
		}
		if (@$HTTP_POST_VARS["sek_adv_visible"]) {
			$sek_adv_visible = 1;
		} else {
			$sek_adv_visible = 0;
		}
		
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_key
                 (
                    sek_title,
					sek_simple_used,
					sek_adv_visible
                 ) VALUES (
                    '" . Misc::escapeString($HTTP_POST_VARS["sek_title"]) . "',
					" . $sek_simple_used .",
					" . $sek_adv_visible ."					
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
			return 1;
        }
    }

    /**
     * Method used to update details of a search key.
     *
     * @access  public
     * @param   integer $sek_id The search key ID
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function update($sek_id)
    {
        global $HTTP_POST_VARS;

		if (@$HTTP_POST_VARS["sek_simple_used"]) {
			$sek_simple_used = 1;
		} else {
			$sek_simple_used = 0;
		}
		if (@$HTTP_POST_VARS["sek_adv_visible"]) {
			$sek_adv_visible = 1;
		} else {
			$sek_adv_visible = 0;
		}

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_key
                 SET 
                    sek_title = '" . Misc::escapeString($HTTP_POST_VARS["sek_title"]) . "',
					sek_simple_used = ".$sek_simple_used.",
					sek_adv_visible = ".$sek_adv_visible."
                 WHERE sek_id = $sek_id";

        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
			return 1;
        }
    }


    /**
     * Method used to get the title of a specific search key.
     *
     * @access  public
     * @param   integer $sek_id The search key ID
     * @return  string The title of the search key
     */
    function getTitle($sek_id)
    {
        $stmt = "SELECT
                    sek_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_key
                 WHERE
                    sek_id=$sek_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);

        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of search keys available in the 
     * system returned in an associative array for drop down lists.
     *
     * @access  public
     * @return  array The list of search keys in an associative array (for drop down lists).
     */
    function getAssocList()
    {
        $stmt = "SELECT
                    sek_id,
					sek_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_key
                 ORDER BY
                    sek_title ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }

    /**
     * Method used to get the list of search keys available in the 
     * system.
     *
     * @access  public
     * @return  array The list of search keys 
     */
    function getList()
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_key
                 ORDER BY
                    sek_title ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (empty($res)) {
                return array();
            } else {
                return $res;
            }
        }
    }

    /**
     * Method used to get the details of a specific search key.
     *
     * @access  public
     * @param   integer $sek_id The search key ID
     * @return  array The search key details
     */
    function getDetails($sek_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_key
                 WHERE
                    sek_id=$sek_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }

}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Search_Key Class');
}
?>
