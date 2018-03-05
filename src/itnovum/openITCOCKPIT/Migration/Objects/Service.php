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

namespace itnovum\openITCOCKPIT\Migration\Objects;

use GuzzleHttp\Client;
use itnovum\openITCOCKPIT\Migration\Mapping;

class Service {

    /**
     * @var string
     */
    private $service;

    /**
     * @var int
     */
    private $objecttype_id;

    /**
     * @var int
     */
    private $object_id;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * @var int
     */
    private $intervallength;

    /**
     * Service constructor.
     * @param $record
     * @param Client $Client
     * @param Mapping $Mapping
     * @param $intervalLength
     * @param $serviceContacts
     * @param $serviceContactgroups
     * @param $servicecustomvariables
     * @param $v3Command
     */
    public function __construct ($record, Client $Client, Mapping $Mapping, $intervalLength, $serviceContacts, $serviceContactgroups, $servicecustomvariables, $v3Command) {
        $this->service = $record;
        $this->client = $Client;
        $this->mapping = $Mapping;
        $this->intervallength = $intervalLength;
        $this->servicecontacts = $serviceContacts;
        $this->servicecontactgroups = $serviceContactgroups;
        $this->servicecustomvariables = $servicecustomvariables;
        $this->objecttype_id = $record['objecttype_id'];
        $this->object_id = $record['object_id'];
        $this->v3Command = $v3Command;
    }

    /**
     * @return int
     */
    public function getObjecttypeId () {
        return $this->objecttype_id;
    }

    /**
     * @return int
     */
    public function getObjectId () {
        return $this->object_id;
    }

    /**
     * @param $str
     * @return string
     */
    public function fixUtf8 ($str) {
        if (mb_detect_encoding($str) !== 'UTF-8') {
            return utf8_encode($str);
        }
        return $str;
    }

    /**
     * @param int $recursive
     * @return mixed
     */
    public function save ($recursive = 0) {

        $contactIds = [];
        foreach ($this->servicecontacts AS $servicecontact) {
            $contactIds[] = $this->mapping->getValue(OBJECT_CONTACT, $servicecontact['contact_object_id']);
        }

        $contactGroupIds = [];
        foreach ($this->servicecontactgroups AS $servicecontactgroup) {
            $contactGroupIds[] = $this->mapping->getValue(OBJECT_CONTACTGROUP, $servicecontactgroup['contactgroup_object_id']);
        }

        $newCustomVariables = [];
        foreach ($this->servicecustomvariables as $servicecustomvariable) {
            if ($servicecustomvariable['varvalue'] == false) {
                continue;
            }
            $newCustomVariables[] = [
                'objecttype_id' => 2048,
                'name'          => $servicecustomvariable['varname'],
                'value'         => $servicecustomvariable['varvalue'],
            ];
        }

        $checkCmdArgValues = [];
        foreach ($this->v3Command->getCommandarguments() as $commandargument) {
            $checkCmdArgValues[] = [
                'value'              => '',
                'commandargument_id' => $commandargument['id']
            ];
        }

        $checkCommandArgs = [];
        foreach (explode('!', $this->service['check_command_args']) AS $i => $arg) {
            $name = '$ARG' . ($i + 1) . '$';

            foreach ($this->v3Command->getCommandarguments() as $commandargument) {
                if ($commandargument['name'] == $name) {
                    $checkCommandArgs[] = [
                        'commandargument_id' => $commandargument['id'],
                        'value'              => $arg,
                    ];
                }
            }

        }

        $data = [
            'Serviceeventcommandargumentvalue' => [],
            'Servicecommandargumentvalue'      => $checkCommandArgs,
            'Service'                          => [
                'Contactgroup'               => $contactGroupIds,
                'Contact'                    => $contactIds,
                'servicetemplate_id'         => (int)$this->mapping->getValue(OBJECT_SERVICETEMPLATE, $this->service['check_command_object_id']),
                'host_id'                    => (int)$this->mapping->getValue(OBJECT_HOST, $this->service['host_object_id']),
                'name'                       => $this->fixUtf8($this->service['name2']),
                'description'                => $this->fixUtf8($this->service['display_name']),
                'command_id'                 => $this->mapping->getValue(OBJECT_COMMAND, $this->service['check_command_object_id']),
                'check_command_args'         => '',
                'eventhandler_command_id'    => 0,
                'notify_period_id'           => $this->mapping->getValue(OBJECT_TIMEPERIOD, $this->service['notification_timeperiod_object_id']),
                'check_period_id'            => $this->mapping->getValue(OBJECT_TIMEPERIOD, $this->service['check_timeperiod_object_id']),
                'check_interval'             => ($this->service['check_interval'] * $this->intervallength),
                'retry_interval'             => ($this->service['retry_interval'] * $this->intervallength),
                'max_check_attempts'         => $this->service['max_check_attempts'],
                'notification_interval'      => ($this->service['notification_interval'] * $this->intervallength),
                'notify_on_warning'          => $this->service['notify_on_warning'],
                'notify_on_unknown'          => $this->service['notify_on_unknown'],
                'notify_on_critical'         => $this->service['notify_on_critical'],
                'notify_on_recovery'         => $this->service['notify_on_recovery'],
                'notify_on_flapping'         => $this->service['notify_on_flapping'],
                'notify_on_downtime'         => $this->service['notify_on_downtime'],
                'is_volatile'                => $this->service['is_volatile'],
                'flap_detection_enabled'     => $this->service['flap_detection_enabled'],
                'flap_detection_on_ok'       => $this->service['flap_detection_on_ok'],
                'flap_detection_on_warning'  => $this->service['flap_detection_on_warning'],
                'flap_detection_on_unknown'  => $this->service['flap_detection_on_unknown'],
                'flap_detection_on_critical' => $this->service['flap_detection_on_critical'],
                'process_performance_data'   => $this->service['process_performance_data'],
                'freshness_checks_enabled'   => $this->service['freshness_checks_enabled'],
                'freshness_threshold'        => $this->service['freshness_threshold'],
                'passive_checks_enabled'     => $this->service['passive_checks_enabled'],
                'event_handler_enabled'      => $this->service['event_handler_enabled'],
                'active_checks_enabled'      => $this->service['active_checks_enabled'],
                'notifications_enabled'      => $this->service['notifications_enabled'],
                'notes'                      => $this->service['notes'],
                'priority'                   => 1,
                'tags'                       => '',
                'own_contacts'               => (int)!empty($contactIds),
                'own_contactgroups'          => (int)!empty($contactGroupIds),
                'own_customvariables'        => (int)!empty($newCustomVariables),
                'service_url'                => $this->service['notes_url'],
                'service_type'               => 1,
                'disabled'                   => 0,
            ],
            'Customvariable'                   => $newCustomVariables
        ];
        try {
            $response = $this->client->post('/services/add.json',
                [
                    'body' => json_encode($data)
                ]
            );
            $enc_response = json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
            if ($recursive != 1) {
                return $this->save(1);
            }
        }

        return $enc_response['id'];
    }
}