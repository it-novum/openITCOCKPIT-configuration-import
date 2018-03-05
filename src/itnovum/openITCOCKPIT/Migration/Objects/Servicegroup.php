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

class Servicegroup {

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
     * Servicegroup constructor.
     * @param $record
     * @param Client $Client
     * @param $hostgroupMembers
     * @param Mapping $mapping
     */
    public function __construct ($record, Client $Client, $servicegroupMembers, Mapping $mapping) {
        $this->servicegroup = $record;
        $this->name = $record['name1'];
        $this->objecttype_id = $record['objecttype_id'];
        $this->object_id = $record['object_id'];
        $this->client = $Client;
        $this->servicegroupMembers = $servicegroupMembers;
        $this->mapping = $mapping;
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
     * @param int $recursive
     * @return null
     */
    public function save ($recursive = 0) {
        $serviceroupMembers = [];
        foreach ($this->servicegroupMembers AS $servicegroupMember) {
            $serviceroupMembers[] = $this->mapping->getValue(OBJECT_SERVICE, $servicegroupMember['service_object_id']);
        }

        $data = [
            'Container'    => [
                'parent_id' => ROOT_CONTAINER,
                'name'      => $this->servicegroup['name1'],
            ],
            'Servicegroup' => [
                'description'      => $this->servicegroup['alias'],
                'servicegroup_url' => '',
                'Service'          => $serviceroupMembers
            ]
        ];

        try {
            $response = $this->client->post('/servicegroups/add.json',
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