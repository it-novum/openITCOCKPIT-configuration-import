#!/usr/bin/env php
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

use itnovum\openITCOCKPIT\Migration\Config;
use itnovum\openITCOCKPIT\Migration\Database;
use itnovum\openITCOCKPIT\Migration\Mapping;
use itnovum\openITCOCKPIT\Migration\Cli;
use itnovum\openITCOCKPIT\Migration\Objects\Command;
use itnovum\openITCOCKPIT\Migration\Objects\TimePeriod;
use itnovum\openITCOCKPIT\Migration\Objects\Contact;
use itnovum\openITCOCKPIT\Migration\Objects\Contactgroup;
use itnovum\openITCOCKPIT\Migration\Objects\Host;
use itnovum\openITCOCKPIT\Migration\Objects\Parenthost;
use itnovum\openITCOCKPIT\Migration\Objects\Service;
use itnovum\openITCOCKPIT\Migration\Objects\Hostgroup;
use itnovum\openITCOCKPIT\Migration\Objects\Servicegroup;
use itnovum\openITCOCKPIT\Migration\Objects\Macro;


require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$Config = new Config();

$Database = new Database($Config);
$Database->connect();

$Mapping = new Mapping();

$client = new \GuzzleHttp\Client([
    'timeout'         => 2.0,
    'base_uri'        => 'https://' . $Config->getKey('oitc_host'),
    'headers'         => [
        'Content-Type' => 'application/json'
    ],
    'verify'          => false,
    'cookies'         => true,
    'connect_timeout' => 5,
    'timeout'         => 10
]);

$loginresponse = $client->post("/login/login.json",
    [
        'body' => json_encode(
            [
                'LoginUser' => [
                    'auth_method' => 'session',
                    'email'       => $Config->getKey('oitc_user'),
                    'password'    => $Config->getKey('oitc_password')
                ]
            ]
        )
    ]
);

if (json_decode($loginresponse->getBody()->getContents(), true)['message'] === 'Login successful') {
    echo 'Login successful';
    echo PHP_EOL . PHP_EOL;
} else {
    echo 'Login failed!';
    echo PHP_EOL;
    exit(1);
}


if (!empty(file($Config->getKey('resource_cfg')))) {
    echo 'Start importing macros...' . PHP_EOL;
    $Progressbar = new ProgressBar\Manager(0, sizeof(file($Config->getKey('resource_cfg'))));
    $Macro = new Macro($Config->getKey('resource_cfg'), $client, $Progressbar);
}


echo PHP_EOL . 'Start importing commands...' . PHP_EOL;
$commands = $Database->getCommands();
$Progressbar = new ProgressBar\Manager(0, sizeof($commands));
$commandOutput = [];

foreach ($commands as $i => $command) {
    $Command = new Command($command, $client);
    $return = $Command->save();
    $commandOutput[] = $return['message'];
    $Mapping->addCommand($Command->getObjectId(), $return['id']);
    $Progressbar->update($i + 1);
}
Cli::printMessages($commandOutput);


echo PHP_EOL . 'Start importing time periods...' . PHP_EOL;
$timeperiods = $Database->getTimePeriods();
$Progressbar = new ProgressBar\Manager(0, sizeof($timeperiods));
$timePeriodOutput = [];

foreach ($timeperiods as $i => $timeperiod) {
    $TimePeriod = new TimePeriod($timeperiod, $Database->getTimeRangesById($timeperiod['timeperiod_id']), $client);
    $return = $TimePeriod->save();
    $timePeriodOutput[] = $return['message'];
    $Mapping->addTimePeriod($TimePeriod->getObjectId(), $return['id']);
    $Progressbar->update($i + 1);
}
Cli::printMessages($timePeriodOutput);


echo PHP_EOL . 'Start importing contacts...' . PHP_EOL;
$contacts = $Database->getContacts();
$Progressbar = new ProgressBar\Manager(0, sizeof($contacts));
$contactOutput = [];

foreach ($contacts as $i => $contact) {
    $Contact = new Contact($contact, $Database->getContactNotificationCommands($contact['contact_id']), $client, $Mapping);
    $return = $Contact->save();
    $contactOutput[] = $return['message'];
    $Mapping->addContact($Contact->getObjectId(), $return['id']);
    $Progressbar->update($i + 1);
}
Cli::printMessages($contactOutput);


echo PHP_EOL . 'Start importing contact groups...' . PHP_EOL;
$contactgroups = $Database->getContactgroups();
$Progressbar = new ProgressBar\Manager(0, sizeof($contactgroups));

foreach ($contactgroups as $i => $contactgroup) {
    $Contactgroup = new Contactgroup($contactgroup, $Database->getContactgroupMembers($contactgroup['contactgroup_id']), $client, $Mapping);
    $Mapping->addContactgroup($Contactgroup->getObjectId(), $Contactgroup->save());
    $Progressbar->update($i + 1);
}


echo PHP_EOL . 'Start importing hosts...' . PHP_EOL;
$hosts = $Database->getHosts();
$Progressbar = new ProgressBar\Manager(0, sizeof($hosts));

foreach ($hosts as $i => $host) {
    $Host = new Host(
        $host,
        $Database->getHostContacts($host['host_id']),
        $Database->getHostContactgroups($host['host_id']),
        $Database->getCustomVariables($host['host_id']),
        $client,
        $Mapping,
        $Config->getKey('interval_length')
    );
    $Mapping->addHost($Host->getObjectId(), $Host->save());
    $Progressbar->update($i + 1);
}


echo PHP_EOL . 'Assign parent hosts...' . PHP_EOL;
$hosts = $Database->getHosts();
$Progressbar = new ProgressBar\Manager(0, sizeof($hosts));

foreach ($hosts as $i => $host) {
    $Parenthost = new Parenthost(
        $host,
        $Database->getParenthosts($host['host_id']),
        $client,
        $Mapping
    );
    $Parenthost->save();
    $Progressbar->update($i + 1);
}


echo PHP_EOL . 'Start importing service templates...' . PHP_EOL;
$commands = $Database->getCommands();
$Progressbar = new ProgressBar\Manager(0, sizeof($commands));

foreach ($commands as $i => $command) {
    $Command = new Command($command, $client);
    $checkInterval = $Database->getMostUsedCheckIntervalByCheckCommandObjectId($Command->getObjectId());
    $retryInterval = $Database->getMostUsedRetryIntervalByCheckCommandObjectId($Command->getObjectId());
    $notificationInterval = $Database->getMostUsedNotificationIntervalByCheckCommandObjectId($Command->getObjectId());
    $activeChecksEnabled = $Database->getMostUsedActiveChecksEnabledByCheckCommandObjectId($Command->getObjectId());
    $processPerformanceData = $Database->getMostUsedProcessPerformanceDataByCheckCommandObjectId($Command->getObjectId());

    $v3Command = new \itnovum\openITCOCKPIT\Migration\v3\Command(
        $Mapping->getValue(
            OBJECT_COMMAND,
            $Command->getObjectId()
        ),
        $client
    );

    if (!$v3Command->isCheckCommand()) {
        continue;
    }

    $Servicetemplate = new \itnovum\openITCOCKPIT\Migration\v3\Servicetemplate(
        $client,
        $checkInterval,
        $retryInterval,
        $notificationInterval,
        $activeChecksEnabled,
        $processPerformanceData,
        $v3Command,
        $Config->getKey('interval_length')
    );

    $Mapping->addServicetemplate($Command->getObjectId(), $Servicetemplate->createTemplate());
    $Progressbar->update($i + 1);
}


echo PHP_EOL . 'Start importing services...' . PHP_EOL;
$services = $Database->getServices();
$Progressbar = new ProgressBar\Manager(0, sizeof($services));

foreach ($services as $i => $service) {

    $v3Command = new \itnovum\openITCOCKPIT\Migration\v3\Command(
        $Mapping->getValue(
            OBJECT_COMMAND,
            $service['check_command_object_id']
        ),
        $client
    );

    $Service = new Service(
        $service,
        $client,
        $Mapping,
        $Config->getKey('interval_length'),
        $Database->getServiceContacts($service['service_id']),
        $Database->getServiceContactgroups($service['service_id']),
        $Database->getCustomVariables($service['service_id']),
        $v3Command
    );
    $Mapping->addService($Service->getObjectId(), $Service->save());
    $Progressbar->update($i + 1);
}


echo PHP_EOL . 'Start importing host groups...' . PHP_EOL;
$hostgroups = $Database->getHostgroups();
$Progressbar = new ProgressBar\Manager(0, sizeof($hostgroups));

foreach ($hostgroups as $i => $hostgroup) {
    $Hostgroup = new Hostgroup($hostgroup, $client, $Database->getHostgroupMembers($hostgroup['hostgroup_object_id']), $Mapping);
    $Mapping->addHostgroup($Hostgroup->getObjectId(), $Hostgroup->save());
    $Progressbar->update($i + 1);
}


echo PHP_EOL . 'Start importing service groups...' . PHP_EOL;
$servicegroups = $Database->getServicegroups();
$Progressbar = new ProgressBar\Manager(0, sizeof($servicegroups));

foreach ($servicegroups as $i => $servicegroup) {
    $Servicegroup = new Servicegroup($servicegroup, $client, $Database->getServicegroupMembers($servicegroup['servicegroup_object_id']), $Mapping);
    $Mapping->addServicegroup($Servicegroup->getObjectId(), $Servicegroup->save());
    $Progressbar->update($i + 1);
}

echo PHP_EOL . 'Finished!' . PHP_EOL;

//print_r($Mapping->getMap());
