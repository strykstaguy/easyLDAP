<?php
namespace stryksta;

spl_autoload_register(function($className) {
    $lastSlash = strpos($className, '\\') + 1;
    $className = substr($className, $lastSlash);
    $directory = str_replace('\\', '/', $className);
    $filename = __DIR__ . '/' . $directory . '.php';
    require_once $filename;
});

$ldap = new EasyLdap('ldap://stryksta.test.local', 389, 3);

// The default DN (Distinguished Name)
$ldap->dn = 'OU=Users,DC=stryksta,DC=local';

// The admin username
$ldap->adminUser = 'admin';

// The admin password
$ldap->adminPassword = 'admin';

// The user domain
$ldap->userDomain = '@stryksta.local';

//Set filter for users
$ldap->userFilter = '(&(objectCategory=person)(objectClass=user))';
$ldap->userAttributes = array('samaccountname', 'title', 'mail', 'department', 'name');

// // Authentication
if ($ldap->authenticate('user', 'password')) {
    echo "Success!";
    //$userDetails = $ldap->getUserDetails();
    //echo("<pre>" . print_r($userDetails, true) . "</pre>");
}

//Get all Users
$getUsers = $ldap->getUsers();

//echo("<pre>" . print_r($getUsers, true) . "</pre>");


$table = '<table>';
$table .="<tr>
            <td>Name</td>
            <td>Title</td>    
            <td>Department</td>    
            <td>Employee</td>    
            <td>Mail</td>      
        </tr>";

foreach ($getUsers as $user) {

    $table .="<tr>
            <td>{$user['name']}</td>
            <td>{$user['title']}</td>
            <td>{$user['department']}</td>
            <td>{$user['samaccountname']}</td>
            <td>{$user['mail']}</td>
        </tr>";
}

$table .= '</table>';

echo $table;

//Close the Connection
$ldap->close();
