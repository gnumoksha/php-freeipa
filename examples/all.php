<?php
/**
FreeIPA library for PHP
Copyright (C) 2015  Tobias Sette <contato@tobias.ws>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * This file contains examples of utilization of this library
 */

ini_set( 'display_errors', 1);
ini_set( 'track_errors',   1);
ini_set( 'html_errors',    1);
error_reporting( E_ALL );

require_once( 'functions_utils.php' );

$host = 'ipa.demo1.freeipa.org';
// The certificate can be obtained in https://$host/ipa/config/ca.crt
$certificate = __DIR__ . "/../certs/ipa.demo1.freeipa.org_ca.crt";
$user = 'admin';
$password = 'Secret123';
$search = 'test';
$random = rand(1, 9999);

require_once('../bootstrap.php');
try {
    $ipa = new \FreeIPA\APIAccess\Main($host, $certificate);
    //$ipa->connection($host, $certificate);
} catch (Exception $e) {
    _print("[instance] Exception. Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}

// If you wish to force the use of one specific version of API (for example:
// after make tests in the code and define that he does not work with different
// versions).
//$ipa->connection()->setAPIVersion('2.112');

// Make authentication
try {
    $ret_aut = $ipa->connection()->authenticate($user, $password);
    if (TRUE === $ret_aut) { // user is authenticate
        _print('Authentication successful');
    } else {
        $auth_info = $ipa->connection()->getAuthenticationInfo();
        var_dump($auth_info);
        // For more details:
        //$ret_curl = $ipa->connection()->getCurlError();
        //print "User is not authenticated. Return is: <br/>" . PHP_EOL;
        //print "cURL: " . $ret_curl[0] . " (" . $ret_curl[1] . ")<br/>" . PHP_EOL;
        //print "cURL string: " . $ipa->connection()->getCurlReturn() . "<br/>\n";
        die();
    }
} catch (Exception $e) {
    _print("[login] Exception. Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}


// Make a connection test with the server
_print('Ping');
try {
    $ret_ping = $ipa->connection()->ping();
    if ($ret_ping) {
        _print('Done!');
    } else {
        _print('Error in ping!');
    }
} catch (Exception $e) {
    _print("[ping] Exception. Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}


// Get User information
_print("Showing o user \"$user\"");
try {
    $ret_user = $ipa->user()->get($user);
    if (TRUE == $ret_user) {
        _print('User found');
        var_dump($ret_user);
    } else {
        _print("User $user not found");
    }
} catch (Exception $e) {
    _print("Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}


// Searching a user
_print("Searching users with login/name contains contenham \"$search\"");
try {
    $ret_search_users = $ipa->user()->find(array($search));
    if ($ret_search_users) {
        $t = count($ret_search_users);
        print "Found $t usuários. Names: ";
        for ($i = 0; $i < $t; $i++) {
            print $ret_search_users[$i]->uid[0] . " | ";
        }
        _print();
    } else {
        _print('No users found');
    }
} catch (Exception $e) {
    _print("[searching user] Exception. Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}


// Procura um user atraves de um campo identificador
// See documentarion of method \FreeIPA\APIAccess\User->findBy() !
_print("Searching for users with login \"$search\"");
try {
    $search_user_by = $ipa->user()->findBy('uid', 'admin'); // login
    //$search_user_by = $ipa->user()->findBy('mail', 'teste@ipatest.com'); // email
    //$search_user_by = $ipa->user()->findBy('givenname', $search); // first name
    //$search_user_by = $ipa->user()->findBy('cn', 'Administrator'); // full name
    //$search_user_by = $ipa->user()->findBy('in_group', 'admins'); // user in group
    if ($search_user_by) {
        _print('Users found');
        var_dump($search_user_by);
    } else {
        _print('No users found');
    }
} catch (\Exception $e) {
    _print("[search user by] Exception. Message: {$e->getMessage()} Code: {$e->getCode()}");
    _print("Json request is {$ipa->connection()->getJsonRequest()}");
    _print("Json response is {$ipa->connection()->getJsonResponse()}");
    die();
}


// Insert a new user
$new_user_data = array(
    'givenname' => 'Richardi',
    'sn' => 'Stallman',
    'uid' => "stallman$random",
    'mail' => "rms$random@fsf.org",
    'userpassword' => $password,
);
_print("Adding new user {$new_user_data['uid']} whith password \"$password\"");
try {
    $add_user = $ipa->user()->add($new_user_data);
    if ($add_user) {
        _print('User added');
    } else {
        _print('Error while adding user');
    }
} catch (\Exception $e) {
    _print("[insert new user] Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}


// Changes the previously created user
$modify_user_data = array(
    'givenname' => 'Richard',
);
_print("Modifying the user {$new_user_data['uid']}");
try {
    $modify_user = $ipa->user()->modify($new_user_data['uid'], $modify_user_data);
    if ($modify_user) {
        _print('User modified');
    } else {
        _print('Error while modifying user');
    }
} catch (\Exception $e) {
    _print("[modifying user] Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}


// Change the password for the previously created user
$data_change_pass = array(
    'userpassword' => 'linus123',
);
_print("Changing the password for user {$new_user_data['uid']} to {$data_change_pass['userpassword']}");
try {
    $change_pass = $ipa->user()->modify($new_user_data['uid'], $data_change_pass);
    if ($change_pass) {
        _print('Password changed');
    } else {
        _print('Error while changing the password');
    }
} catch (\Exception $e) {
    _print("[change password] Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}


// Add group
_print("Add group \"group$random\"");
try {
    $add_group = $ipa->group()->add("group$random", "group$random description");
    if ($add_group) {
        _print('Group added');
    } else {
        _print('Error while adding a group');
    }
} catch (\Exception $e) {
    _print("[add group] Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}


// Add user to group
_print("Add \"$user\" to group \"group$random\"");
try {
    $add_user_group = $ipa->group()->addMember("group$random", $user);
    if ($add_group) {
        _print('User added');
        var_dump($add_user_group);
    } else {
        _print('Error while adding user to group');
    }
} catch (\Exception $e) {
    _print("[add user to group] Message: {$e->getMessage()} Code: {$e->getCode()}");
    die();
}


_print('DONE');
