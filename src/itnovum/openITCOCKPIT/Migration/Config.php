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

namespace itnovum\openITCOCKPIT\Migration;

use itnovum\openITCOCKPIT\Migration\Exceptions\FileNotFoundException;
use Symfony\Component\Yaml\Parser;

class Config {

    /**
     * @var string
     */
    private $configFile;

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var array
     */
    private $defaultConfig = [
        'interval_length' => 60
    ];

    /**
     * Config constructor.
     * @throws FileNotFoundException
     */
    public function __construct () {
        $this->configFile = __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . 'etc' . DS . 'config.yml';
        if (!file_exists($this->configFile)) {
            throw new FileNotFoundException(sprintf('Config file %s not found', $this->configFile));
        }

        $this->loadConfig();
    }

    private function loadConfig () {
        $parser = new Parser();
        $this->config = $parser->parse(file_get_contents($this->configFile));
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getKey ($key = '') {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        return $this->defaultConfig[$key];
    }


    /**
     * @return int
     */
    public function getIntervalLength () {
        return (int)$this->getKey('interval_length');
    }

}