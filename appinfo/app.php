<?php

/**
 * ownCloud - user_cas
 *
 * @author Sixto Martin <sixto.martin.garcia@gmail.com>
 * @copyright Sixto Martin Garcia. 2012
 * @copyright Leonis. 2014 <devteam@leonis.at>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */




if (OCP\App::isEnabled('user_cas')) {

	require_once 'user_cas/user_cas.php';

	OCP\App::registerAdmin('user_cas', 'settings');

	// register user backend
	OC_User::useBackend( 'CAS' );

	OC::$CLASSPATH['OC_USER_CAS_Hooks'] = 'user_cas/lib/hooks.php';
	OCP\Util::connectHook('OC_User', 'post_createUser', 'OC_USER_CAS_Hooks', 'post_createUser');
	OCP\Util::connectHook('OC_User', 'post_login', 'OC_USER_CAS_Hooks', 'post_login');
	OCP\Util::connectHook('OC_User', 'logout', 'OC_USER_CAS_Hooks', 'logout');

	$force_login = shouldEnforceAuthentication();

	if( (isset($_GET['app']) && $_GET['app'] == 'user_cas') || $force_login ) {

		if (OC_USER_CAS :: initialized_php_cas()) {

			phpCAS::forceAuthentication();

			if (!OC_User::login('', '')) {
				$error = true;
				\OCP\Util::writeLog('cas','Error trying to authenticate the user', \OCP\Util::DEBUG);
			}
		
			if (isset($_SERVER["QUERY_STRING"]) && !empty($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"] != 'app=user_cas') {
				header( 'Location: ' . OC::$WEBROOT . '/?' . $_SERVER["QUERY_STRING"]);
				exit();
			}
		}

		OC::$REQUESTEDAPP = '';
		OC_Util::redirectToDefaultPage();
	}


	if (!phpCAS::isAuthenticated() && !OCP\User::isLoggedIn()) {

		// Load js code in order to render the CAS link and to hide parts of the normal login form
		OCP\Util::addScript('user_cas', 'login');
	}

}

/**
 * Check if login should be enforced using user_cas
 */
function shouldEnforceAuthentication()
{
	if (OC::$CLI) {
		return false;
	}

	if (OCP\Config::getAppValue('user_cas', 'cas_force_login', false) === false) {
		return false;
	}

	if (OCP\User::isLoggedIn() || isset($_GET['admin_login'])) {
		return false;
	}

	$script = basename($_SERVER['SCRIPT_FILENAME']);
	return !in_array(
		$script,
		array(
			'cron.php',
			'public.php',
			'remote.php',
			'status.php',
		)
	);
}

