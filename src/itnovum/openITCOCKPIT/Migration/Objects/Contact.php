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

class Contact {

    /**
     * @var int
     */
    private $object_id;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var array
     */
    private $contactdata;

    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * @var array
     */
    private $contactNotificationCommands;

    /**
     * @var int
     */
    private $migrationContainerId;

    /**
     * Contact constructor.
     * @param $record
     * @param $contactNotificationCommands
     * @param $migrationContainerId
     * @param Client $Client
     * @param Mapping $Mapping
     */
    public function __construct ($record, $contactNotificationCommands, $migrationContainerId, Client $Client, Mapping $Mapping) {
        $this->contactdata = $record;
        $this->object_id = $record['object_id'];
        $this->client = $Client;
        $this->mapping = $Mapping;
        $this->migrationContainerId = $migrationContainerId;
        $this->contactNotificationCommands = $contactNotificationCommands;
    }


    /**
     * @return int
     */
    public function getObjectId () {
        return $this->object_id;
    }

    /**
     * @param bool $data
     * @return array
     */
    public function save ($data = false, $message = false, $recursive = 0) {
        $exception = false;
        if ($data) {
            try {
                $response = $this->client->post('/contacts/add.json', [
                        'body' => json_encode($data)
                    ]
                );
            } catch (Exception $e) {
                echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
            }
        } else {
            $hostCommands = [];
            $serviceCommands = [];
            foreach ($this->contactNotificationCommands AS $contactNotificationCommand) {
                $command_id = $this->mapping->getValue(OBJECT_COMMAND, $contactNotificationCommand['command_object_id']);

                $getCommandResponse = $this->client->get(sprintf('/commands/%s.json', $command_id));
                $commandData = json_decode($getCommandResponse->getBody()->getContents(), true);
                $commandData = $commandData['command'];
                $commandData['Command']['command_type'] = 3;

                $editCommandResponse = $this->client->post(sprintf('/commands/edit/%s.json', $command_id),
                    [
                        'body' => json_encode($commandData)
                    ]
                );

                if ($contactNotificationCommand['notification_type'] == 1) {
                    $serviceCommands[] = $command_id;
                }
                if ($contactNotificationCommand['notification_type'] == 0) {
                    $hostCommands[] = $command_id;
                }
            }
            $data = [
                'Contact'   => [
                    'name'        => $this->contactdata['name1'],
                    'description' => $this->contactdata['alias'],
                    'email'       => ($this->contactdata['email_address'] === null) ? 'info@example.org' : $this->contactdata['email_address'],
                    'phone'       => ($this->contactdata['pager_address'] === null) ? '' : $this->contactdata['pager_address'],

                    'host_timeperiod_id'    => $this->mapping->getValue(OBJECT_TIMEPERIOD, $this->contactdata['host_timeperiod_object_id']),
                    'service_timeperiod_id' => $this->mapping->getValue(OBJECT_TIMEPERIOD, $this->contactdata['service_timeperiod_object_id']),

                    'HostCommands'    => $hostCommands,
                    'ServiceCommands' => $serviceCommands,

                    'host_notifications_enabled'    => 1, // Enabled by default. New in v3.
                    'service_notifications_enabled' => 1, // Enabled by default. New in v3.
                    'notify_service_recovery'       => $this->contactdata['notify_service_recovery'],
                    'notify_service_warning'        => $this->contactdata['notify_service_warning'],
                    'notify_service_unknown'        => $this->contactdata['notify_service_unknown'],
                    'notify_service_critical'       => $this->contactdata['notify_service_critical'],
                    'notify_service_flapping'       => $this->contactdata['notify_service_flapping'],
                    'notify_service_downtime'       => 0, // Disabled by default. New in v3.
                    'notify_host_recovery'          => $this->contactdata['notify_host_recovery'],
                    'notify_host_down'              => $this->contactdata['notify_host_down'],
                    'notify_host_unreachable'       => $this->contactdata['notify_host_unreachable'],
                    'notify_host_flapping'          => $this->contactdata['notify_host_flapping'],
                    'notify_host_downtime'          => 0, // Disabled by default. New in v3.
                ],
                'Container' => [
                    'Container' => [
                        $this->migrationContainerId
                    ],
                ],
            ];
            try {
                $response = $this->client->post('/contacts/add.json',
                    [
                        'body' => json_encode($data)
                    ]
                );
            } catch (Exception $e) {
                echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
                $exception = true;
            }
        }
        $enc_response = json_decode($response->getBody()->getContents(), true);
        if ($exception || (isset($enc_response['error']['name']) && in_array('This contact name has already been taken.', $enc_response['error']['name']))) {
            $message .= 'Contact name "' . $data['Contact']['name'] . '" was already taken, I\'ll prefix it with "migration_" and try again.' . PHP_EOL;
            $data['Contact']['name'] = 'migration_' . $data['Contact']['name'];
            return $this->save($data, $message);
        }
        if (isset($enc_response['error']['notify_host_recovery']) && in_array('You have to choose at least one option.', $enc_response['error']['notify_host_recovery'])) {
            $message .= 'Set contact options "notify_host_recovery" and "notify_service_recovery" for "'.$this->contactdata['name1'].'" to 1' . PHP_EOL;
            $data['Contact']['notify_host_recovery'] = 1;
            $data['Contact']['notify_service_recovery'] = 1;
            return $this->save($data, $message);
        }
        if (isset($enc_response['error']['email']) && in_array('Invalid email address', $enc_response['error']['email']) && $recursive < 2) {
            if ($recursive == 0) {
                $message .= 'Contact email "' . $data['Contact']['email'] . '" is invalid, I\'ll postfix it with ".com" and try again.' . PHP_EOL;
                $data['Contact']['email'] = $data['Contact']['email'] . '.com';
            } else {
                $message .= 'Contact email "' . $data['Contact']['email'] . '" is invalid, I\'ll replace it with default "info@example.org" and try again.' . PHP_EOL;
                $data['Contact']['email'] = 'info@example.org';
            }

            return $this->save($data, $message, $recursive + 1);
        }
        $return = [
            'id'      => $enc_response['id'],
            'message' => $message
        ];
        return $return;
    }

}