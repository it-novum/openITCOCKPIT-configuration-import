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

class Mapping {

    /**
     * @var array
     */
    private $map = [];

    /**
     * Command constructor.
     */
    public function __construct () {

    }

    /**
     * @param $objectConstant
     * @param $key
     * @param $value
     * @return bool
     */
    public function add ($objectConstant, $key, $value) {
        if ($this->map[$objectConstant][$key] = $value) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getMap () {
        return $this->map;
    }

    /**
     * @param $objectConstant
     * @return bool|mixed
     */
    public function getObjectMap ($objectConstant) {
        if ($this->map[$objectConstant]) {
            return $this->map[$objectConstant];
        }
        return false;
    }

    /**
     * @param $objectConstant
     * @param $key
     * @return bool
     */
    public function getValue ($objectConstant, $key) {
        if ($this->map[$objectConstant][$key]) {
            return $this->map[$objectConstant][$key];
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function addCommand ($key, $value) {
        if ($this->map[OBJECT_COMMAND][$key] = $value) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function addTimePeriod ($key, $value) {
        if ($this->map[OBJECT_TIMEPERIOD][$key] = $value) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function addContact ($key, $value) {
        if ($this->map[OBJECT_CONTACT][$key] = $value) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function addContactgroup ($key, $value) {
        if ($this->map[OBJECT_CONTACTGROUP][$key] = $value) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function addHost ($key, $value) {
        if ($this->map[OBJECT_HOST][$key] = $value) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function addServicetemplate ($key, $value) {
        if ($this->map[OBJECT_SERVICETEMPLATE][$key] = $value) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function addService ($key, $value) {
        if ($this->map[OBJECT_SERVICE][$key] = $value) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function addHostgroup ($key, $value) {
        if ($this->map[OBJECT_HOSTGROUP][$key] = $value) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function addServicegroup ($key, $value) {
        if ($this->map[OBJECT_SERVICEGROUP][$key] = $value) {
            return true;
        }
        return false;
    }

}