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

class TimePeriod {

    const MIDNIGHT = 86400;
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $command_line;

    /**
     * @var int
     */
    private $objecttype_id;

    /**
     * @var int
     */
    private $object_id;

    /**
     * @var int
     */
    private $timeperiod_id;

    /**
     * @var int
     */
    private $instance_id;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array
     */
    private $args = [];

    /**
     * @var array
     */
    private $timeranges = [];

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * TimePeriod constructor.
     * @param $record
     * @param $timeranges
     * @param Client $Client
     */
    public function __construct ($record, $timeranges, Client $Client) {
        $this->name = $record['name1'];
        $this->description = $record['alias'];
        $this->objecttype_id = $record['objecttype_id'];
        $this->instance_id = $record['instance_id'];
        $this->object_id = $record['timeperiod_object_id'];
        $this->timeperiod_id = $record['timeperiod_id'];
        $this->client = $Client;
        $this->timeranges = $timeranges;
    }

    /**
     * @return mixed
     */
    public function getDescription () {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getEndSec () {
        return $this->end_sec;
    }

    /**
     * @return mixed
     */
    public function getInstanceId () {
        return $this->instance_id;
    }

    /**
     * @return mixed
     */
    public function getStartSec () {
        return $this->start_sec;
    }

    public function getCommandArgs () {
        preg_match_all('/\$ARG\d+\$/', $this->command_line, $matches);
        $this->args = [];
        if (sizeof($matches[0]) > 0) {
            foreach ($matches[0] as $match) {
                $this->args[$match] = $match;
            }
        }
        return $this->args;
    }

    /**
     * @return string
     */
    public function getName () {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCommandLine () {
        return $this->command_line;
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
     * @return int
     */
    public function getTimePeriodId () {
        return $this->timeperiod_id;
    }


    public function secToTime ($sec) {
        if ($sec == self::MIDNIGHT) {
            return "24:00";
        }
        return date("H:i", $sec + strtotime("1970/1/1"));
    }

    /**
     * @param bool $data
     * @param bool $message
     * @return array
     */
    public function save ($data = false, $message = false) {
        if ($data) {
            try {
                $response = $this->client->post('/timeperiods/add.json', [
                        'body' => json_encode($data)
                    ]
                );
            } catch (Exception $e) {
                echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
            }
        } else {
            preg_match_all('/\$ARG\d+\$/', $this->command_line, $matches);
            $commandArguments = [];
            if (sizeof($matches[0]) > 0) {
                foreach ($matches[0] as $arg) {
                    $commandArguments[] = [
                        'name'       => $arg,
                        'human_name' => $arg
                    ];
                }
            }

            $data = [
                'Timeperiod' => [
                    'container_id' => 1,
                    'name'         => $this->name,
                    'description'  => $this->description,
                ],
                'Timerange'  => []
            ];

            $days = [
                0 => 7,
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5,
                6 => 6
            ];
            foreach ($this->timeranges as $timerange) {
                $data['Timerange'][] = [
                    'day'   => $days[$timerange['day']],
                    'start' => $this->secToTime($timerange['start_sec']),
                    'end'   => $this->secToTime($timerange['end_sec']),
                ];
            }

            try {
                $response = $this->client->post('/timeperiods/add.json',
                    [
                        'body' => json_encode($data)
                    ]
                );
            } catch (Exception $e) {
                echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
                if ($message == false) {
                    return $this->save($data, 'Exception catched: ' . $e->getMessage() . PHP_EOL);
                }
            }
        }
        $enc_response = json_decode($response->getBody()->getContents(), true);
        if (isset($enc_response['error']['name']) && in_array('This timeperiod name has already been taken.', $enc_response['error']['name'])
        ) {
            $message .= 'Timeperiod name "' . $data['Timeperiod']['name'] . '" was already taken, I\'ll prefix it with "migration_" and try again.' . PHP_EOL;
            $data['Timeperiod']['name'] = 'migration_' . $data['Timeperiod']['name'];
            return $this->save($data, $message);
        }
        $return = [
            'id'      => $enc_response['id'],
            'message' => $message
        ];
        return $return;
    }

}