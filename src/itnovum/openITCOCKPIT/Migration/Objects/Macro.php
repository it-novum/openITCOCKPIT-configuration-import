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

class Macro {

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var String
     */
    private $macro_filename;

    /**
     * Macro constructor.
     * @param $filename
     * @param Client $Client
     */
    public function __construct ($filename, Client $Client, $Progressbar) {
        $this->macro_filename = $filename;
        $this->client = $Client;
        $this->progressbar = $Progressbar;
        $this->migrateMacros();
    }

    /**
     * @param bool $data
     * @return array
     */
    public function migrateMacros () {

        $data = [
            [
                'Macro' => [
                    'password'    => 0,
                    'id'          => '1',
                    'name'        => '$USER1$',
                    'value'       => '/opt/openitc/nagios/libexec',
                    'description' => 'Path to monitoring plugins'
                ]
            ]
        ];
        $resource_cfg = fopen($this->macro_filename, 'r');
        $i = 0;
        while (($line = fgets($resource_cfg)) !== false) {
            //echo strpos($line,'#');
            if ((strpos($line, '#') === false || strpos($line, '#') > 2) &&
                str_replace(' ', '', $line) != '' &&
                str_replace(' ', '', $line) != "\n" &&
                strpos($line, '$USER1$') === false
            ) {
                $macro = explode('=', $line, 2);
                $data[] = [
                    'Macro' => [
                        'value'       => $macro[1],
                        'name'        => $macro[0],
                        'password'    => 0,
                        'description' => ''
                    ]
                ];

            }
            $this->progressbar->update($i + 1);
            $i++;
        }

        try {
            $response = $this->client->post('/macros/index.json',
                [
                    'body' => json_encode($data)
                ]
            );
        } catch (Exception $e) {
            echo 'Exception catched: ' . $e->getMessage() . PHP_EOL;
        }

        echo sizeof($data) . ' macros imported' . PHP_EOL;
    }
}