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

class Hostgroup {

    /**
     * @var string
     */
    private $name;

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
     * @var int
     */
    private $migrationContainerId;

    /**
     * Hostgroup constructor.
     * @param $record
     * @param Client $Client
     * @param $hostgroupMembers
     * @param Mapping $mapping
     * @param $migrationContainerId
     */
    public function __construct ($record, Client $Client, $hostgroupMembers, Mapping $mapping, $migrationContainerId) {
        $this->hostgroup = $record;
        $this->name = $record['name1'];
        $this->objecttype_id = $record['objecttype_id'];
        $this->object_id = $record['object_id'];
        $this->client = $Client;
        $this->hostgroupMembers = $hostgroupMembers;
        $this->mapping = $mapping;
        $this->migrationContainerId = $migrationContainerId;
    }

    /**
     * @return string
     */
    public function getName () {
        return $this->name;
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
    public function save ($data = false) {
        if ($data) {
            try {
                $response = $this->client->post('/hostgroups/add.json', [
                        'body' => json_encode($data)
                    ]
                );
            } catch (Exception $e) {
                echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
            }
        } else {


            $hostgroupMembers = [];
            foreach ($this->hostgroupMembers AS $hostgroupMember) {
                $hostgroupMembers[] = $this->mapping->getValue(OBJECT_HOST, $hostgroupMember['host_object_id']);
            }

            $data = [
                'Container' => [
                    'parent_id' => $this->migrationContainerId,
                    'name'      => $this->hostgroup['name1'],
                ],
                'Hostgroup' => [
                    'description'   => $this->hostgroup['alias'],
                    'hostgroup_url' => '',
                    'Host'          => $hostgroupMembers
                ]
            ];

            try {
                $response = $this->client->post('/hostgroups/add.json',
                    [
                        'body' => json_encode($data)
                    ]
                );
            } catch (Exception $e) {
                echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
                return $this->save($data);
            }
        }
        $enc_response = json_decode($response->getBody()->getContents(), true);
        if (isset($enc_response['error']['name']) && in_array('This host group name has already been taken.', $enc_response['error']['name'])
        ) {
            echo 'Hostgroup name "' . $data['Hostgroup']['name'] . '" was already taken, I\'ll prefix it with "migration_" and try again.' . PHP_EOL;
            $data['Hostgroup']['name'] = 'migration_' . $data['Hostgroup']['name'];
            return $this->save($data);
        }
        return $enc_response['id'];
    }

}