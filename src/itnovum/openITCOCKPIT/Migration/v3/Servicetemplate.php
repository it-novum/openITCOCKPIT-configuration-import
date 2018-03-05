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

namespace itnovum\openITCOCKPIT\Migration\v3;


use GuzzleHttp\Client;

class Servicetemplate {

    private $client;

    public function __construct (Client $Client,
                                 $checkInterval,
                                 $retryInterval,
                                 $notificationInterval,
                                 $activeChecksEnabled,
                                 $processPerformanceData,
                                 Command $v3Command,
                                 $intervalLength) {
        $this->client = $Client;
        $this->checkInterval = $checkInterval;
        $this->retryInterval = $retryInterval;
        $this->notificationInterval = $notificationInterval;
        $this->activeChecksEnabled = $activeChecksEnabled;
        $this->processPerformanceData = $processPerformanceData;
        $this->v3Command = $v3Command;
        $this->intervallength = $intervalLength;
    }

    public function createTemplate ($recursive = 0) {

        $checkCmdArgValues = [];
        foreach ($this->v3Command->getCommandarguments() as $commandargument) {
            $checkCmdArgValues[] = [
                'value'              => '',
                'commandargument_id' => $commandargument['id']
            ];
        }

        $data = [
            'Contactgroup'                             => [],
            'Contact'                                  => [],
            'Servicetemplateeventcommandargumentvalue' => [],
            'Servicetemplatecommandargumentvalue'      => $checkCmdArgValues,
            'Servicetemplate'                          => [
                'template_name'              => strtoupper($this->v3Command->getName()),
                'name'                       => $this->v3Command->getName(),
                'container_id'               => ROOT_CONTAINER,
                'servicetemplatetype_id'     => 1,
                'check_period_id'            => 1,
                'notify_period_id'           => 1,
                'description'                => '', //$serviceTemplate->getServiceDescription(),
                'command_id'                 => $this->v3Command->getId(),
                'check_command_args'         => '', // We'll import the data anyway.
                'checkcommand_info'          => '',
                'eventhandler_command_id'    => 0,
                'timeperiod_id'              => 1,
                'check_interval'             => ($this->checkInterval * $this->intervallength),
                'retry_interval'             => ($this->retryInterval * $this->intervallength),
                'max_check_attempts'         => 3,
                'first_notification_delay'   => 0,
                'notification_interval'      => ($this->notificationInterval * $this->intervallength),
                'notify_on_warning'          => 1,
                'notify_on_unknown'          => 1,
                'notify_on_critical'         => 1,
                'notify_on_recovery'         => 1,
                'notify_on_flapping'         => 0,
                'notify_on_downtime'         => 0, // TODO This should probably be zero, but review that with colleagues.
                'flap_detection_enabled'     => 0,
                'flap_detection_on_ok'       => 0,
                'flap_detection_on_warning'  => 0,
                'flap_detection_on_unknown'  => 0,
                'flap_detection_on_critical' => 0,
                'process_performance_data'   => $this->processPerformanceData,
                'freshness_checks_enabled'   => 0,
                'freshness_threshold'        => 0,
                'passive_checks_enabled'     => 1,
                'event_handler_enabled'      => 1,
                'active_checks_enabled'      => $this->activeChecksEnabled,
                'notifications_enabled'      => 1,
                'notes'                      => '',
                'priority'                   => 1,
                'tags'                       => '',
                'is_volatile'                => 0,
                'check_freshness'            => 0,
            ],
        ];

        try {
            $response = $this->client->post('/servicetemplates/add.json',
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
            $enc_response['id'] = null;
        }

        return $enc_response['id'];
    }
}