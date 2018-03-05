<?php
// Copyright (C) <2018>  <it-novum GmbH>
//
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.


define('OBJECT_COMMAND', 12);
define('OBJECT_TIMEPERIOD', 9);
define('OBJECT_CONTACT', 10);
define('OBJECT_CONTACTGROUP', 11);
define('OBJECT_HOST', 1);
define('OBJECT_SERVICE', 2);
define('OBJECT_HOSTGROUP', 3);
define('OBJECT_SERVICEGROUP', 4);
define('OBJECT_HOSTESCALATION', 5);
define('OBJECT_SERVICEESCALATION', 6);
define('OBJECT_HOSTDEPENDENCY', 7);
define('OBJECT_SERVICEDEPENDENCY', 8);

define('OBJECT_SERVICETEMPLATE', 100);
define('ROOT_CONTAINER', 1);
define('DEFAULT_HOSTTEMPLATE', 1);

define('DS', DIRECTORY_SEPARATOR);

$autoload = __DIR__ . DS . 'vendor' . DS . 'autoload.php';
if (!file_exists($autoload)) {
    printf('File "%s" not found! Clear run "composer install" first!%s', $autoload, PHP_EOL);
    exit(1);
}
require_once $autoload;

