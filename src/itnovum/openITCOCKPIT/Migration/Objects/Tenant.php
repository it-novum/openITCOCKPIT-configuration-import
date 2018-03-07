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

class Tenant {

    /**
     * @var string
     */
    private $name;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * Tenant constructor.
     * @param $name
     * @param Client $Client
     */
    public function __construct ($name, Client $Client) {
        $this->name = $name;
        $this->client = $Client;
    }

    /**
     * @return string
     */
    public function getName () {
        return $this->name;
    }

    /**
     * @param bool $data
     * @return array
     */
    public function save ($data = false, $message = false) {
        $exception = false;
        if ($data) {
            try {
                $response = $this->client->post('/tenants/add.json', [
                        'body' => json_encode($data)
                    ]
                );
            } catch (Exception $e) {
                echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
            }
        } else {
            $data = [
                'Container' => [
                    'name' => $this->name
                ],
                'Tenant'    => [
                    'max_users'    => 0,
                    'max_hosts'    => 0,
                    'max_services' => 0,
                    'is_active'    => 1
                ]
            ];
            try {
                $response = $this->client->post('/tenants/add.json',
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
        if ($exception || (isset($enc_response['error']['Container']['name']) && in_array('This name already exists.', $enc_response['error']['Container']['name']))
        ) {
            $message .= 'Container name "' . $data['Container']['name'] . '" was already taken, I\'ll prefix it with "_" and try again.' . PHP_EOL;
            $data['Container']['name'] = '_' . $data['Container']['name'];
            return $this->save($data, $message);
        }
        $return = [
            'container_id' => $enc_response['container_id'],
            'message'      => $message
        ];
        return $return;
    }

}