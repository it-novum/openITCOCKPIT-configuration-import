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

class Contactgroup {

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
    private $contactgroupdata;

    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * @var array
     */
    private $contactgroupMembers;

    /**
     * Contact constructor.
     * @param $record
     * @param $contactNotificationCommands
     * @param Client $Client
     * @param Mapping $Mapping
     */
    public function __construct ($record, $contactgroupMembers, Client $Client, Mapping $Mapping) {
        $this->contactgroupdata = $record;
        $this->object_id = $record['object_id'];
        $this->contactgroup_id = $record['contactgroup_id'];
        $this->client = $Client;
        $this->mapping = $Mapping;
        $this->contactgroupMembers = $contactgroupMembers;
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
    public function save ($data = false, $recursive = 0) {
        if ($data) {
            try {
                $response = $this->client->post('/contactgroups/add.json', [
                        'body' => json_encode($data)
                    ]
                );
            } catch (Exception $e) {
                echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
            }
        } else {
            $contactIds = [];
            foreach ($this->contactgroupMembers as $contactgroupMember) {
                $contactIds[] = $this->mapping->getValue(OBJECT_CONTACT, $contactgroupMember['contact_object_id']);
            }
            $data = [
                'Container'    => [
                    'parent_id'        => ROOT_CONTAINER,
                    'name'             => $this->contactgroupdata['name1'],
                    'containertype_id' => 6, // Fixed value for CT_CONTACTGROUP.
                ],
                'Contactgroup' => [
                    'description' => $this->contactgroupdata['alias'],
                    'Contact'     => $contactIds,
                ]
            ];
            try {
                $response = $this->client->post('/contactgroups/add.json',
                    [
                        'body' => json_encode($data)
                    ]
                );
            } catch (Exception $e) {
                echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
                if ($recursive != 1) {
                    return $this->save($data, 1);
                }
            }
        }
        $enc_response = json_decode($response->getBody()->getContents(), true);
        return $enc_response['id'];
    }

}