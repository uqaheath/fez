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
// | Authors: |
// +----------------------------------------------------------------------+
//
//


/**
 * Class RhdStudentInsertion
 *
 * Adds RHD students to fez
 */
class FezAuthorInsertion
{

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    private $db;

    /**
     * RhdStudentInsertion constructor.
     *
     * @param Zend_Db_Adapter_Abstract $db
     */
    function __construct(Zend_Db_Adapter_Abstract $db)
    {
        $this->db = $db;
    }

    /**
     * Checks if the passed in user's usernames already exist in
     * fez, returns a list of usernames for those already existing
     *
     * @param array $users
     *
     * @return array
     */
    public function getExistingUsers($users)
    {
        // OK so there is no IN limit, it's up to max allowed packet size
        // but lets just set one and paginate the query so we don't break stuff
        $inLimit = 200;

        // isolate the usernames of the users
        $userNames = [];
        
        foreach ($users as $user) {
            // comparison case insensitive, but just for good measure
            // use exact format of fez author
            $userNames[] = $user['aut_org_username'];
        }
        
        // well push the ones which exist onto here
        $existingUserNames = [];

        while (count($userNames) > 0) {
            $splice = array_splice($userNames, 0, $inLimit, []);

            $results = $this->listExistingAuthors($splice);

            if ($results) {
                // use the three dots of sorcery
                array_push($existingUserNames, ...$results);
            }
        }

        return $existingUserNames;
    }

    /**
     * Pulls out a complete list of RHD students, with the option
     * to paginate
     *
     * @param array $users
     *
     * @return mixed
     */
    private function listExistingAuthors($users)
    {
        $select = $this->db->select();

        $select->from('fez_author', ['aut_org_username'])
            ->where('aut_org_username IN (?)', $users);

        return $this->db->fetchCol($select);
    }


    /**
     * Insert non existing users
     *
     * @param array $users
     *
     * @return int  - number inserted
     */
    public function insertNew($users)
    {
        $existingUsers = $this->getExistingUsers($users);
        return $this->insert($users, $existingUsers);
    }

    /**
     * Insert rhd students
     *
     * @param array $users
     * @param array $existingUsernames
     *
     * @return int  - number inserted
     */
    public function insert($users, $existingUsernames)
    {
        $successful = 0;

        foreach ($users as $user) {
            if (!in_array($user['aut_org_username'], $existingUsernames)) {
                if ($this->db->insert('fez_author', $user)) {
                    ++$successful;
                }
            }
        }

        return $successful;
    }
}
