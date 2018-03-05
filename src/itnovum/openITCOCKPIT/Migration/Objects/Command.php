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

class Command {

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
     * @var array
     */
    private $args = [];

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * Command constructor.
     * @param array $record
     */
    public function __construct ($record, Client $Client) {
        $this->name = $record['name1'];
        $this->command_line = $record['command_line'];
        $this->objecttype_id = $record['objecttype_id'];
        $this->object_id = $record['object_id'];
        $this->client = $Client;
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
     * @param bool $data
     * @return array
     */
    public function save ($data = false, $message = false) {
        $exception = false;
        if ($data) {
            try {
                $response = $this->client->post('/commands/add.json', [
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
                'Command'         => [
                    'name'         => $this->name,
                    'command_line' => $this->command_line,
                    'command_type' => 1
                ],
                'Commandargument' => $commandArguments
            ];
            try {
                $response = $this->client->post('/commands/add.json',
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
        if ($exception || (isset($enc_response['error']['name']) && in_array('This command name has already been taken.', $enc_response['error']['name']))
        ) {
            $message = 'Command name "' . $data['Command']['name'] . '" was already taken, I\'ll prefix it with "migration_" and try again.' . PHP_EOL;
            $data['Command']['name'] = 'migration_' . $data['Command']['name'];
            return $this->save($data, $message);
        }
        $return = [
            'id'      => $enc_response['id'],
            'message' => $message
        ];
        return $return;
    }

}