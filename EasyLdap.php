<?php

namespace stryksta;

/**
 * EasyLdap
 *
 * An abstraction layer for LDAP server communication using PHP
 *
 * @author Cody Benner <strykstaguy@gmail.com>
 * @package easyldap
 * @license https://opensource.org/licenses/MIT MIT License
 * @version 1.0
 */
class EasyLdap
{
    private $ldap;
    public $dn;
    public $user;
    public $userFilter;
    public $userAttributes;
    public $attributes;
    public $userDomain;
    public $userData;
    public $adminUser;
    public $adminPassword;

    /**
     * EasyLdap constructor.
     *
     * @param string $host LDAP server address
     * @param string $port LDAP server port
     * @param null $protocol optional
     */
    public function __construct($host, $port, $protocol = null)
    {
        $this->ldap = ldap_connect($host, $port);

        if ($protocol != null) {
            ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, $protocol);
        }
    }

    /**
     * Bind as admin
     *
     * @return bool Returns if bind was successful
     */
    private function bindAdmin()
    {

        $bind = ldap_bind($this->ldap, $this->adminUser, $this->adminPassword);

        return $bind;
    }

    /**
     * Check if username and password authenticates
     *
     * @param string $user
     * @param string $password
     *
     * @return bool returns if user authentication was successful or not
     */
    public function authenticate($user, $password)
    {

        $this->user = $user;

        $bind = ldap_bind($this->ldap, $user . $this->userDomain, $password);

        if ($bind) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return logged in user's information
     *
     * @return mixed
     * @throws Exception
     */
    public function getUserDetails()
    {
        if ($this->bindAdmin()) {

            $filter = "(&(objectCategory=person)(objectClass=user)(sAMAccountName=$this->user))";
            $attributes = array('sAMAccountName', 'givenname', 'title', 'mail', 'department', 'name');


            $search = ldap_search($this->ldap, $this->dn, $filter, $attributes);

            if (!$search) {
                $error = ldap_errno($this->ldap) . ": " . ldap_error($this->ldap);
                throw new Exception($error);
            }

            $ldapData = ldap_get_entries($this->ldap, $search);

            if (!$ldapData) {
                $error = ldap_errno($this->ldap) . ": " . ldap_error($this->ldap);
                throw new Exception($error);
            }

            //Oragnize the Data
            $ldapUser = $ldapData[0];

            $this->userData['name'] = $ldapUser['name'][0];
            $this->userData['title'] = $ldapUser['title'][0];
            $this->userData['department'] = $ldapUser['department'][0];
            $this->userData['samaccountname'] = $ldapUser['samaccountname'][0];
            $this->userData['mail'] = $ldapUser['mail'][0];

            return $this->userData;
        } else {
            return false;
        }
    }

    /**
     * @return array|bool
     */
    public function getUsers()
    {
        if ($this->bindAdmin()) {
            if ($this->userAttributes !== null) {
                $search = ldap_search($this->ldap, $this->dn, $this->userFilter, $this->userAttributes);
                if (!$search) {
                    return false;
                }
                $data = ldap_get_entries($this->ldap, $search);
                return $this->ldapArray($data);
            } else {
                $search = ldap_search($this->ldap, $this->dn, $this->userFilter);
                if (!$search) {
                    return false;
                }
                $data = ldap_get_entries($this->ldap, $search);
                return $this->ldapArray($data);
            }
        } else {
            return false;
        }
    }

    /**
     * @param $array
     *
     * @return array
     */
    private function ldapArray($array)
    {
        $newArray = array();
        $finalArray = array();

        foreach ($array as $item) {

            if (is_array($item)) {

                $tempArray = array();
                $test = array();

                foreach ($item as $key => $value) {

                    if ((count($item[$key]) - 1) === 1)  //if the item is not an array
                    {

                        $tempArray[$key] = $item[$key][0];
                        $test[] = $key;

                    } elseif ((count($item[$key]) - 1) > 1)  //if the item is an array
                    {

                        foreach ($item[$key] as $arrayPosition) {
                            array_push($newArray, $arrayPosition);

                        }

                        //Send it to the new object
                        $tempArray[$key] = $newArray;

                    }
                }


                $missing = array_diff($this->userAttributes, $test);

                if (count($missing) === 1) {

                    foreach ($missing as $key) {
                        $tempArray[$key] = "";
                    }

                }

                array_push($finalArray, $tempArray);
            }

        }

        return $finalArray;
    }

    /**
     * Close the LDAP Connection
     *
     */
    public function close()
    {
        ldap_close($this->ldap);
    }

}