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

class Parenthost {

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
    private $parenthosts = [];


    /**
     * Parenthost constructor.
     * @param $record
     * @param $parenthosts
     * @param Client $Client
     * @param Mapping $Mapping
     */
    public function __construct ($record, $parenthosts, Client $Client, Mapping $Mapping) {
        $this->host = $record;
        $this->parenthosts = $parenthosts;
        $this->objecttype_id = $record['objecttype_id'];
        $this->object_id = $record['object_id'];
        $this->client = $Client;
        $this->mapping = $Mapping;
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

        if (count($this->parenthosts) > 0) {

            $parenthostIds = [];
            foreach ($this->parenthosts AS $parenthostid) {
                $parenthostIds[] = $this->mapping->getValue(OBJECT_HOST, $parenthostid['object_id']);
            }

            $newHostId = $this->mapping->getValue(OBJECT_HOST, $this->object_id);

            $data = [
                'Host' => [
                    'id'              => $newHostId,
                    'container_id'    => ROOT_CONTAINER,
                    'hosttemplate_id' => DEFAULT_HOSTTEMPLATE,
                    'name'            => $this->host['name1'],
                    'address'         => $this->host['address'],
                    'Parenthost'      => $parenthostIds
                ]
            ];

            try {
                $response = $this->client->post('/hosts/edit/' . $newHostId . '.json',
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
        }

    }
}