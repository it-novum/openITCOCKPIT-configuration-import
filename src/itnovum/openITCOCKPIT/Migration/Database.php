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


use PDO;

class Database {

    /**
     * @var Config
     */
    private $Config;

    /**
     * @var \PDO
     */
    private $dbh;

    /**
     * Database constructor.
     * @param Config $Config
     */
    public function __construct (Config $Config) {
        $this->Config = $Config;
        $this->config_type = $this->Config->getKey('config_type');
        $this->prefix = $this->Config->getKey('mysql_table_prefix');
    }

    /**
     * Database connector
     */
    public function connect () {
        $this->dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $this->Config->getKey('mysql_host'), $this->Config->getKey('mysql_database')), $this->Config->getKey('mysql_user'), $this->Config->getKey('mysql_password'));
    }

    /**
     * @param $queryString
     * @return mixed
     */
    public function setPrefix ($queryString) {
        return str_replace('{{prefix}}', $this->prefix, $queryString);
    }

    /**
     * @return array
     */
    public function getCommands () {
        $query = 'SELECT * from {{prefix}}_commands
                  INNER JOIN {{prefix}}_objects ON {{prefix}}_commands.object_id = {{prefix}}_objects.object_id
                  WHERE {{prefix}}_objects.is_active=1
                  AND {{prefix}}_commands.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function getTimePeriods () {
        $query = 'SELECT * from {{prefix}}_timeperiods
                  INNER JOIN {{prefix}}_objects ON {{prefix}}_timeperiods.timeperiod_object_id = {{prefix}}_objects.object_id
                  WHERE {{prefix}}_objects.is_active=1
                  AND {{prefix}}_timeperiods.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getTimeRangesById ($id) {
        $query = 'SELECT * from {{prefix}}_timeperiod_timeranges WHERE timeperiod_id = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function getContacts () {
        $query = 'SELECT * from {{prefix}}_contacts
                  INNER JOIN {{prefix}}_objects ON {{prefix}}_contacts.contact_object_id = {{prefix}}_objects.object_id
                  WHERE {{prefix}}_objects.is_active = 1 
                  AND {{prefix}}_contacts.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getContactNotificationCommands ($id) {
        $query = 'SELECT * from {{prefix}}_contact_notificationcommands 
                  INNER JOIN {{prefix}}_commands ON {{prefix}}_contact_notificationcommands.command_object_id = {{prefix}}_commands.object_id
                  WHERE {{prefix}}_contact_notificationcommands.contact_id = ?
                  AND {{prefix}}_commands.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->bindParam(2, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function getContactgroups () {
        $query = 'SELECT * from {{prefix}}_contactgroups
                  INNER JOIN {{prefix}}_objects ON {{prefix}}_contactgroups.contactgroup_object_id = {{prefix}}_objects.object_id
                  WHERE {{prefix}}_objects.is_active=1
                  AND {{prefix}}_contactgroups.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function getHosts () {
        $query = 'SELECT * from {{prefix}}_hosts
                  INNER JOIN {{prefix}}_objects ON {{prefix}}_hosts.host_object_id = {{prefix}}_objects.object_id
                  WHERE {{prefix}}_objects.is_active=1
                  AND {{prefix}}_hosts.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getHostContactgroups ($id) {
        $query = 'SELECT * from {{prefix}}_host_contactgroups WHERE host_id = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getHostContacts ($id) {
        $query = 'SELECT * from {{prefix}}_host_contacts WHERE host_id = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getParenthosts ($id) {
        $query = 'SELECT * from {{prefix}}_host_parenthosts 
                  INNER JOIN {{prefix}}_objects ON {{prefix}}_host_parenthosts.parent_host_object_id = {{prefix}}_objects.object_id
                  WHERE {{prefix}}_objects.is_active=1 AND host_id = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getCustomVariables ($id) {
        $query = 'SELECT * from {{prefix}}_customvariables WHERE object_id = ? AND config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->bindParam(2, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getContactgroupMembers ($id) {
        $query = 'SELECT * from {{prefix}}_contactgroup_members WHERE contactgroup_id = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function getServices () {
        $query = 'SELECT * from {{prefix}}_services
                  INNER JOIN {{prefix}}_objects ON {{prefix}}_services.service_object_id = {{prefix}}_objects.object_id
                  WHERE {{prefix}}_objects.is_active=1
                  AND {{prefix}}_services.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getServiceContactgroups ($id) {
        $query = 'SELECT * from {{prefix}}_service_contactgroups WHERE service_id = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getServiceContacts ($id) {
        $query = 'SELECT * from {{prefix}}_service_contacts WHERE service_id = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return int
     */
    public function getMostUsedCheckIntervalByCheckCommandObjectId ($id) {
        $query = 'SELECT count( * ) AS `counter` , `check_command_object_id` , `check_interval` , `retry_interval`
                FROM `{{prefix}}_services`
                WHERE `check_command_object_id` = ?
                AND {{prefix}}_services.config_type = ?
                GROUP BY `check_interval`
                ORDER BY `counter` DESC
                LIMIT 1';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->bindParam(2, $this->config_type);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if (isset($result[0]['check_interval'])) {
            return (int)$result[0]['check_interval'];
        }
        return 5;
    }

    /**
     * @param $id
     * @return int
     */
    public function getMostUsedRetryIntervalByCheckCommandObjectId ($id) {
        $query = 'SELECT count( * ) AS `counter` , `check_command_object_id` , `check_interval` , `retry_interval`
                FROM `{{prefix}}_services`
                WHERE `check_command_object_id` = ?
                AND {{prefix}}_services.config_type = ?
                GROUP BY `retry_interval`
                ORDER BY `counter` DESC
                LIMIT 1';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->bindParam(2, $this->config_type);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if (isset($result[0]['retry_interval'])) {
            return (int)$result[0]['retry_interval'];
        }
        return 1;
    }

    /**
     * @param $id
     * @return int
     */
    public function getMostUsedNotificationIntervalByCheckCommandObjectId ($id) {
        $query = 'SELECT count( * ) AS `counter` , `check_command_object_id` , `notification_interval`
                FROM `{{prefix}}_services`
                WHERE `check_command_object_id` = ?
                AND {{prefix}}_services.config_type = ?
                GROUP BY `notification_interval`
                ORDER BY `counter` DESC
                LIMIT 1';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->bindParam(2, $this->config_type);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if (isset($result[0]['notification_interval'])) {
            return (int)$result[0]['notification_interval'];
        }
        return 120;
    }

    /**
     * @param $id
     * @return int
     */
    public function getMostUsedProcessPerformanceDataByCheckCommandObjectId ($id) {
        $query = 'SELECT count( * ) AS `counter` , `check_command_object_id` , `process_performance_data`
                FROM `{{prefix}}_services`
                WHERE `check_command_object_id` = ?
                AND {{prefix}}_services.config_type = ?
                GROUP BY `process_performance_data`
                ORDER BY `counter` DESC
                LIMIT 1';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->bindParam(2, $this->config_type);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if (isset($result[0]['process_performance_data'])) {
            return (int)$result[0]['process_performance_data'];
        }
        return 1;
    }

    /**
     * @param $id
     * @return int
     */
    public function getMostUsedActiveChecksEnabledByCheckCommandObjectId ($id) {
        $query = 'SELECT count( * ) AS `counter` , `check_command_object_id` , `active_checks_enabled`
                FROM `{{prefix}}_services`
                WHERE `check_command_object_id` = ?
                AND {{prefix}}_services.config_type = ?
                GROUP BY `active_checks_enabled`
                ORDER BY `counter` DESC
                LIMIT 1';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->bindParam(2, $this->config_type);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if (isset($result[0]['active_checks_enabled'])) {
            return (int)$result[0]['active_checks_enabled'];
        }
        return 1;
    }

    /**
     * @return array
     */
    public function getHostgroups () {
        $query = 'SELECT * FROM `{{prefix}}_hostgroups`
                  INNER JOIN `{{prefix}}_objects` ON `{{prefix}}_objects`.`object_id` = `{{prefix}}_hostgroups`.`hostgroup_object_id`
                  WHERE {{prefix}}_objects.is_active=1
                  AND {{prefix}}_hostgroups.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getHostgroupMembers ($id) {
        $query = 'SELECT *
                FROM `{{prefix}}_hostgroups`
                LEFT JOIN `{{prefix}}_hostgroup_members` ON `{{prefix}}_hostgroup_members`.`hostgroup_id` = `{{prefix}}_hostgroups`.`hostgroup_id`
                INNER JOIN `{{prefix}}_objects` ON `{{prefix}}_objects`.`object_id` = `{{prefix}}_hostgroup_members`.`host_object_id`
                WHERE `{{prefix}}_hostgroups`.`hostgroup_object_id` = ?
                AND {{prefix}}_hostgroups.config_type = ?
                AND {{prefix}}_objects.is_active=1';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->bindParam(2, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * @return array
     */
    public function getServicegroups () {
        $query = 'SELECT * FROM `{{prefix}}_servicegroups`
                  INNER JOIN `{{prefix}}_objects` ON `{{prefix}}_objects`.`object_id` = `{{prefix}}_servicegroups`.`servicegroup_object_id`
                  WHERE {{prefix}}_objects.is_active=1
                  AND {{prefix}}_servicegroups.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array
     */
    public function getServicegroupMembers ($id) {
        $query = 'SELECT *
                FROM `{{prefix}}_servicegroups`
                LEFT JOIN `{{prefix}}_servicegroup_members` ON `{{prefix}}_servicegroup_members`.`servicegroup_id` = `{{prefix}}_servicegroups`.`servicegroup_id`
                INNER JOIN `{{prefix}}_objects` ON `{{prefix}}_objects`.`object_id` = `{{prefix}}_servicegroup_members`.`service_object_id`
                WHERE `{{prefix}}_servicegroups`.`servicegroup_object_id` = ?
                AND {{prefix}}_objects.is_active=1
                AND {{prefix}}_servicegroups.config_type = ?';
        $query = $this->setPrefix($query);
        $query = $this->dbh->prepare($query);
        $query->bindParam(1, $id);
        $query->bindParam(2, $this->config_type);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}