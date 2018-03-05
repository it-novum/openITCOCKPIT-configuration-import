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

class Host {

    /**
     * @var string
     */
    private $host;

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
     * @var array
     */
    private $hostcontacts = [];

    /**
     * @var array
     */
    private $hostcontactgroups = [];

    /**
     * @var array
     */
    private $hostcustomvariables = [];

    /**
     * @var int
     */
    private $intervallength;

    /**
     * Host constructor.
     * @param $record
     * @param $hostContacts
     * @param $hostContactgroups
     * @param $hostCustomVariables
     * @param Client $Client
     * @param Mapping $Mapping
     * @param $intervalLength
     */
    public function __construct ($record, $hostContacts, $hostContactgroups, $hostCustomVariables, Client $Client, Mapping $Mapping, $intervalLength) {
        $this->host = $record;
        $this->hostcontacts = $hostContacts;
        $this->hostcustomvariables = $hostCustomVariables;
        $this->hostcontactgroups = $hostContactgroups;
        $this->objecttype_id = $record['objecttype_id'];
        $this->object_id = $record['object_id'];
        $this->client = $Client;
        $this->mapping = $Mapping;
        $this->intervallength = $intervalLength;
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
     * @param int $recursive
     * @return mixed
     */
    public function save ($recursive = 0) {

        $contactIds = [];
        foreach ($this->hostcontacts AS $hostcontact) {
            $contactIds[] = $this->mapping->getValue(OBJECT_CONTACT, $hostcontact['contact_object_id']);
        }

        $contactGroupIds = [];
        foreach ($this->hostcontactgroups AS $hostcontactgroup) {
            $contactGroupIds[] = $this->mapping->getValue(OBJECT_CONTACTGROUP, $hostcontactgroup['contactgroup_object_id']);
        }

        $newCustomVariables = [];
        foreach ($this->hostcustomvariables as $hostcustomvariable) {
            // Dont use custom variables with out a value.
            if ($hostcustomvariable['varvalue'] == false) {
                continue;
            }
            $newCustomVariables[] = [
                'objecttype_id' => 256,
                'name'          => $hostcustomvariable['varname'],
                'value'         => $hostcustomvariable['varvalue'],
            ];
        }

        $data = [
            'Host'           => [
                'container_id'                  => ROOT_CONTAINER,
                'hosttemplate_id'               => DEFAULT_HOSTTEMPLATE,
                'name'                          => $this->host['name1'],
                'description'                   => $this->host['alias'],
                'address'                       => $this->host['address'],
                'notes'                         => $this->host['notes'],
                'host_url'                      => ($this->host['notes_url'] == "0") ? '' : $this->host['notes_url'],
                'priority'                      => '1', // Not implemented in v2. Default is supposed be 1.
                'tags'                          => '',
                'satellite_id'                  => 0,
                'notify_period_id'              => $this->mapping->getValue(OBJECT_TIMEPERIOD, $this->host['notification_timeperiod_object_id']),
                'notification_interval'         => ($this->host['notification_interval'] * $this->intervallength),
                'notify_on_recovery'            => $this->host['notify_on_recovery'],
                'notify_on_down'                => $this->host['notify_on_down'],
                'notify_on_unreachable'         => $this->host['notify_on_unreachable'],
                'notify_on_flapping'            => $this->host['notify_on_flapping'],
                'notify_on_downtime'            => $this->host['notify_on_downtime'],  //davor: 0
                'active_checks_enabled'         => $this->host['active_checks_enabled'], // Is supposed to be always '1'.
                'Contact'                       => $contactIds,
                'Contactgroup'                  => $contactGroupIds,
                'command_id'                    => $this->mapping->getValue(OBJECT_COMMAND, $this->host['check_command_object_id']),
                'check_period_id'               => $this->mapping->getValue(OBJECT_TIMEPERIOD, $this->host['check_timeperiod_object_id']), // null => get the value from the template. (timeperiod with name 24x7).
                'max_check_attempts'            => $this->host['max_check_attempts'],
                'check_interval'                => ($this->host['check_interval'] * $this->intervallength),
                'retry_interval'                => ($this->host['retry_interval'] * $this->intervallength),
                'flap_detection_enabled'        => $this->host['flap_detection_enabled'],
                'flap_detection_on_up'          => $this->host['flap_detection_on_up'],
                'flap_detection_on_down'        => $this->host['flap_detection_on_down'],
                'flap_detection_on_unreachable' => $this->host['flap_detection_on_unreachable'],
                'host_type'                     => 1,
                'own_customvariables'           => (int)!empty($newCustomVariables),
            ],
            'Customvariable' => $newCustomVariables,
        ];
        try {
            $response = $this->client->post('/hosts/add.json',
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