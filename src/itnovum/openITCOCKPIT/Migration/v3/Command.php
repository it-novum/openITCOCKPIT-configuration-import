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

class Command {

    /**
     * @var Client
     */
    private $client;

    private $name;

    private $command_line;

    private $command_type;

    private $uuid;

    private $description;

    private $commandarguments = [];

    /**
     * @var int
     */
    private $id;

    public function __construct ($id, Client $Client) {
        $this->client = $Client;
        $this->id = $id;
        $this->load();
    }


    private function load () {
        $response = $this->client->get(sprintf('/commands/%s.json', $this->id));
        $response = json_decode($response->getBody()->getContents(), true);

        $command = $response['command'];
        $this->name = $command['Command']['name'];
        $this->command_line = $command['Command']['command_line'];
        $this->command_type = $command['Command']['command_type'];
        $this->uuid = $command['Command']['uuid'];
        $this->description = $command['Command']['description'];
        $this->commandarguments = $command['Commandargument'];
    }

    public function isCheckCommand () {
        return $this->command_type == 1;
    }

    /**
     * @return mixed
     */
    public function getName () {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getCommandLine () {
        return $this->command_line;
    }

    /**
     * @return mixed
     */
    public function getCommandType () {
        return $this->command_type;
    }

    /**
     * @return mixed
     */
    public function getUuid () {
        return $this->uuid;
    }

    /**
     * @return mixed
     */
    public function getDescription () {
        return $this->description;
    }

    /**
     * @return array
     */
    public function getCommandarguments () {
        return $this->commandarguments;
    }

    /**
     * @return int
     */
    public function getId () {
        return $this->id;
    }


}