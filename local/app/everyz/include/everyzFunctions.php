<?php

/*
 * * Purpose: Miscelaneous functions
 * * Adail Horst - http://spinola.net.br/blog
 * *
 * * This program is free software; you can redistribute it and/or modify
 * * it under the terms of the GNU General Public License as published by
 * * the Free Software Foundation; either version 2 of the License, or
 * * (at your option) any later version.
 * *
 * * This program is distributed in the hope that it will be useful,
 * * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * * GNU General Public License for more details.
 * *
 * * You should have received a copy of the GNU General Public License
 * * along with this program; if not, write to the Free Software
 * * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * */

// Define and global variables
define("ZE_VER", "3.0");
define("EZ_TITLE", 'EveryZ - ');
define("ZE_COPY", ", ZE " . ZE_VER);
global $VG_DEBUG;
global $zeMessages, $zeLocale, $baseName, $requiredMissing;

$requiredMissing = false;
$VG_DEBUG = (isset($_REQUEST['p_debug']) && $_REQUEST['p_debug'] == 'S' ? TRUE : FALSE );

// End of define and global variables
// Functions required ==========================================================
# Zabbix-Extras - Global Variables Start
function zbxeFieldValue($p_query, $p_field) {
    $res = "";
    $result = prepareQuery($p_query);
    while ($row = DBfetch($result)) {
        $res = $row[$p_field];
    }
    return $res;
}

function descItem($itemName, $itemKey) {
    if (strpos($itemName, "$") !== false) {
        $tmp = explode("[", $itemKey);
        $tmp = explode(",", str_replace("]", "", $tmp[1]));
        for ($i = 0; $i < count($tmp); $i++) {
            $itemName = str_replace("$" . ($i + 1), $tmp[$i], $itemName);
        }
    }
    return $itemName;
}

// Functions using my project tables ===========================================
/**
 * _zeT
 *
 * Translate strings using zbxe_translation. Need to be used on all modules.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $msg        text to translate
 * @param string  $moduleid   module identifier
 */
function _zeT($msg, $moduleid = "") {
    if ($moduleid == "") {
        global $moduleName;
        $moduleid = $moduleName;
    }
    $lang = quotestr(CWebUser::$data['lang']);
    $p_msg2 = quotestr($msg);
    $return = zbxeFieldValue('select tx_new from zbxe_translation where tx_original = '
            . $p_msg2 . ' and lang = ' . $lang, 'tx_new');
    if ($return == "") {
        $sql = "insert into zbxe_translation values (" . $lang . "," . $p_msg2 . "," . $p_msg2 . ", " . quotestr($moduleid) . ")";
        prepareQuery($sql);
        $return = $msg;
    }
    return $return;
}

/**
 * zbxeConfigValue
 *
 * Get configuration value from zbxe_preferences.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $param   param to get current value
 * @param integer $id      get the value for specific user (default 0 = all users)
 * @param string  $default default value (used if dont exists configuration paramiter with $param name)
 */
function zbxeConfigValue($param, $id = 0, $default = "") {
    $query = 'select tx_value from zbxe_preferences where userid = '
            . $id . " and tx_option = " . quotestr($param);
    $retorno = zbxeFieldValue($query, 'tx_value');
    return (strlen($retorno) < 1 ? $default : $retorno);
}

/**
 * zbxeUpdateConfigValue
 *
 * Update configuration values on zbxe_preferences.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string   $param   param to update value
 * @param string   $value   new value
 * @param integer  $id      userid (default 0 = all users)
 */
function zbxeUpdateConfigValue($param, $value, $id = 0) {
    $currentValue = zbxeConfigValue($param, $id);
    if ($currentValue == "") {
        if (zbxeFieldValue("select count(*) as total from zbxe_preferences where tx_option = "
                        . quotestr($param), "total") == 0) {
            $query = zbxeInsert("zbxe_preferences", ['userid', 'tx_option', 'tx_value', 'st_ativo'], [$id, $param, $value, '1']);
            //echo $query;
            //echo "insert [$currentValue] <br> [$query]";
        }
    }
    if ((!isset($query)) && ($currentValue != $value)) {
        $query = zbxeUpdate("zbxe_preferences", ['userid', 'tx_option', 'tx_value', 'st_ativo'], [$id, $param, $value, '1']
                , ['tx_option'], [$param]);
        //echo "update [$currentValue] <br> [$query]";
    }
    //if ($param == "map_title_show") {
    if (isset($query)) {
        //echo "<br>ois [" . strlen($query) . "]";
        return prepareQuery($query);
    }
    //}
    //echo "[<br> $param - [$currentValue/$value]";
}

/**
 * zbxeInsert
 *
 * Return SQL query to insert records in a table.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string   $table       table name
 * @param array    $fields      fields to insert values
 * @param array    $values      new values
 */
function zbxeInsert($table, $fields, $values) {
    global $conn;
    $field_names = $field_values = "";
    for ($i = 0; $i < count($fields); $i++) {
        $field_names .= ($field_names == "" ? "" : ", " ) . "`" . $fields[$i] . "`";
        /* when I found the support for paramiters query on Zabbix..
          $field_values .= ($field_values == "" ? "" : ", " ) . "?";
         */
        $field_values .= ($field_values == "" ? "" : ", " ) . quotestr($values[$i]);
    }
    $filter = "";
    $query = " insert into " . $table . " (" . $field_names . ") VALUES (" . $field_values . ") " . $filter;
    return $query;
}

/**
 * zbxeUpdate
 *
 * Return SQL query to update records in a table.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string   $table       table name
 * @param array    $fields      fields to update
 * @param array    $values      new values
 * @param array    $filternames fields filter definition to update SQL
 * @param array    $filternames values of fields to search
 */
function zbxeUpdate($table, $fields, $values, $filterNames, $filterValues) {
    global $conn;
    $updateFields = "";
    for ($i = 0; $i < count($fields); $i++) {
        $updateFields .= ($updateFields == "" ? "" : ", " ) . "`" . $fields[$i] . "` = " . quotestr($values[$i]);
    }
    $filter = "";

    for ($i = 0; $i < count($filterNames); $i++) {
        /* when I found the support for paramiters query on Zabbix..
          $filter .= ($filter == "" ? "" : ", " ) . "" . $filterNames[$i] . " = ? ";
          $values[count($values)] = $filterValues[$i];
         */
        $filter .= ($filter == "" ? "" : " AND " ) . "" . $filterNames[$i] . " = " . quotestr($filterValues[$i]);
    }
    $query = " update " . $table . " set " . $updateFields . ($filter != "" ? " where " . $filter : "");
    return $query;
}

/**
 * newComboFilter
 *
 * Return a combo with options from SQL values
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $query   SQL statement
 * @param string    $name    name of HTML element
 * @param string    $value   current value
 * @param boolean   $reload  true - submit form when value are changed
 */
function newComboFilter($query, $name, $value, $reload = true) {
    $cmbRange = new CComboBox($name, $value, ($reload ? 'javascript: submit();' : ''));
    $result = DBselect($query);
    $cmbRange->additem("0", "");
    while ($row_extra = DBfetch($result)) {
        $cmbRange->additem($row_extra['id'], $row_extra['description']);
    }
    return $cmbRange;
}

/**
 * newComboFilterArray
 *
 * Return a combo with options from a array of values
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $array   array of options (key and value)
 * @param string    $name    name of HTML element
 * @param string    $value   current value
 * @param boolean   $reload  true - submit form when value are changed
 */
function newComboFilterArray($array, $name, $value, $reload = true) {
    $cmbRange = new CComboBox($name, $value, ($reload ? 'javascript: submit();' : ''));
    $cmbRange->additem('', 'Selecione...');
    foreach ($array as $k => $v) {
        $cmbRange->additem($k, $v);
    }
    return $cmbRange;
}

/**
 * prepareQuery
 *
 * Execute a SQL code using native Zabbix Frontend Functions
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_query    SQL code to execute
 */
function prepareQuery($p_query) {
    $result = DBselect($p_query);
    if (!$result) {
        global $DB;
        die("Invalid query [$p_query]." . ( $DB['TYPE'] == ZBX_DB_POSTGRESQL ? "" : mysql_error()));
        return 0;
    } else {
        return $result;
    }
}

/**
 * getBetweenStrings
 *
 * Get text between two strings
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $start  Start identifier
 * @param string    $end    End identifier
 * @param string    $str    Full text
 */
function getBetweenStrings($start, $end, $str) {
    $matches = array();
    $regex = "/$start([a-zA-Z0-9_]*)$end/";
    preg_match_all($regex, $str, $matches);
    return $matches[1];
}

/**
 * debugInfo
 *
 * Generic function to show debug messages
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_text   Debug text message
 * @param boolean   $p_debug  True - Show message, False - Dont show
 * @param string    $p_color  Background color of message
 */
function debugInfo($p_text, $p_debug = false, $p_color = "gray") {
    global $VG_DEBUG;
    if ($p_debug == true || $VG_DEBUG == true) {
        echo '<div style="background-color:' . $p_color . ';">' . $p_text . "</div>";
    }
}

function array_sort($array, $on, $order = SORT_ASC) {
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
                break;
            case SORT_DESC:
                arsort($sortable_array);
                break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

/**
 * quotestr
 *
 * Generic function to quote strings
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_text   Text to quote
 */
function quotestr($p_text) {
    global $DB;
    return "'" . ($DB['TYPE'] == ZBX_DB_POSTGRESQL ?
                    pg_escape_string($p_text) :
                    addslashes($p_text)
            ) . "'";
}

/**
 * ezZabbixVersion
 *
 * Return Current Zabbix Version.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 */
function ezZabbixVersion() {
    return str_replace(".", "", substr(ZABBIX_VERSION, 0, 5));
}

/**
 * checkAccessGroup
 *
 * Check if current user can access (read) hostgroup data.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_groupid   ID of hostgroup
 */
function checkAccessGroup($p_groupid) {
    if (getRequest($p_groupid) && !API::HostGroup()->isReadable(array($_REQUEST[$p_groupid]))) {
        access_deny();
    } else {
        $groupids = array($_REQUEST[$p_groupid]);
    }
    return $groupids;
}

/**
 * checkAccessHost
 *
 * Check if current user can access (read) host data.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_hostid   ID of host
 */
function checkAccessHost($p_hostid) {
    if (getRequest($p_hostid) && !API::Host()->isReadable(array($_REQUEST[$p_hostid]))) {
        access_deny();
    } else {
        $hostids = array($_REQUEST[$p_hostid]);
        if ($hostids[0] == 0) {
            $hostids = array();
        }
    }
    return $hostids;
}

/**
 * getRequest2
 *
 * Get a parameter value with support to default value. Function created because Zabbix INC change this funcion name many times last years.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_name     Name of parameter
 * @param boolean   $p_message  Default value
 */
function getRequest2($p_name, $p_default = "") {
    if (isset($_REQUEST[$p_name])) {
        return $_REQUEST[$p_name];
    } else {
        return $p_default;
    }
}

/**
 * checkRequiredField
 *
 * Check if a mandatory field is empty.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_name     Name of parameter
 * @param boolean   $p_message  Custom error message
 */
function checkRequiredField($p_name, $p_message = "") {
    global $requiredMissing;
    $value = getRequest2($p_name);
    $requiredMissing = ($requiredMissing == true ? true : false);
    if (is_array($value) && $value == array(0)) {
        $requiredMissing = true;
    } else if ($value == "") {
        $requiredMissing = true;
    }
    if ($requiredMissing) {
        error(_zeT($p_message));
    }
}

/**
 * addFilterActions
 *
 * Add the standard fields on $fields variable (common variable used for filter on standard zabbix pages)
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 */
function addFilterActions() {
    global $filter, $fields, $moduleName;
    $fields["action"] = array(T_ZBX_STR, O_OPT, P_UNSET_EMPTY, NULL, null);
    $filter["action"] = $moduleName;
    $fields["filter_rst"] = array(T_ZBX_STR, O_OPT, P_UNSET_EMPTY, NULL, null);
    $filter["filter_rst"] = getRequest2("filter_rst", "");
    $fields["filter_set"] = array(T_ZBX_STR, O_OPT, P_UNSET_EMPTY, NULL, null);
    $filter["filter_set"] = getRequest2("filter_set", "");
    $fields['fullscreen'] = array(T_ZBX_INT, O_OPT, P_SYS, IN('0,1'), null);
    $filter["fullscreen"] = getRequest2("fullscreen", "1");
}

/**
 * fullScreenIcon
 *
 * Return a object for control fullscreen mode
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 */
function fullScreenIcon() {
    global $filter;
    return (new CList())->addItem(get_icon('fullscreen', ['fullscreen' => $filter['fullscreen']]));
}

/**
 * addFilterParameter
 *
 * Add filter fields with support to default values and profiles.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string  $p_name         Paramiter name
 * @param string  $p_type         Type of data
 * @param string  $p_default      Default value
 * @param boolean $p_array        The data will be stored as array values on Zabbix Database
 * @param boolean $p_unset_empty  Clear data on profile when empty
 * @param boolean $p_use_profile  Use Zabbix Profile system
 */
function addFilterParameter($p_name, $p_type, $p_default = "", $p_array = false
, $p_unset_empty = false, $p_use_profile = true) {
    global $baseProfile, $fields, $filter;
    $typeProfile = ($p_type == T_ZBX_INT ? PROFILE_TYPE_INT : PROFILE_TYPE_STR);
    // Algum problema com o tipo negativo... validar
    $fields[$p_name] = array($p_type, O_OPT, ($p_unset_empty ? P_UNSET_EMPTY : P_SYS), null, null);
//    $fields[$p_name] = array($p_type, O_OPT, ($p_unset_empty ? P_UNSET_EMPTY : P_SYS), DB_ID, null);
    if ($p_array) {
        $p_default = (is_array($p_default) ? $p_default : array($p_default));
        $filter[$p_name] = getRequest2($p_name, CProfile::getArray($baseProfile . "." . $p_name, $p_default));
        if ($p_use_profile) {
            CProfile::updateArray($baseProfile . "." . $p_name, $filter[$p_name], $typeProfile);
        }
    } else {
        $filter[$p_name] = getRequest2($p_name, CProfile::get($baseProfile . "." . $p_name, $p_default));
        if ($p_use_profile) {
            CProfile::update($baseProfile . "." . $p_name, $filter[$p_name], $typeProfile);
        }
    }
}

/**
 * resetProfile
 *
 * Clear profile data on Zabbix Database.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param string    $p_name   Last part of profile name on Zabbix Database ($baseProfile$p_name)
 * @param boolean   $p_array  True - Filter variable will receive "blank value" | False - Filter variable will receive "null" value.
 */
function resetProfile($p_name, $p_array = false) {
    global $baseProfile, $filter;
    CProfile::delete($baseProfile . "." . $p_name);
    if ($p_array) {
        $filter[$p_name] = null;
    } else {
        $filter[$p_name] = "";
    }
}

/**
 * baseURL
 *
 * Return current URL of frontend. Used for create dynamic links (for example)
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 */
function baseURL() {
    global $s, $_SERVER;
    return "http" . ((!empty($s['HTTPS']) && $s['HTTPS'] == 'on' ) ? "s" : "") . "://{$_SERVER['HTTP_HOST']}";
}

/**
 * getItem
 *
 * Return a standard object with item data.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $itemid   Primary Key (unique identifier) of item on Zabbix Database
 */
function getItem($itemid) {
    $item = API::Item()->get([
        'itemids' => $itemid,
        'output' => ['itemid', 'name', 'status', 'key_', 'units', 'valuemapid', 'value_type', 'state']
    ]);
    $item = CMacrosResolverHelper::resolveItemKeys($item);
    $item = CMacrosResolverHelper::resolveItemNames($item);
    foreach ($item as $row) {
        return $row;
    }
}

/**
 * multiSelectHosts
 *
 * Return a standard object for select hosts.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $multiSelectHostData   Array with hosts from Zabbix
 */
function multiSelectHosts($multiSelectHostData) {
    return (new CMultiSelect([
        'name' => 'hostids[]',
        'objectName' => 'hosts',
        'data' => $multiSelectHostData,
        'popup' => [
            'parameters' => 'srctbl=hosts&dstfrm=zbx_filter&dstfld1=hostids_&srcfld1=hostid' .
            '&real_hosts=1&multiselect=1'
        ]]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH);
}

/**
 * multiSelectHostGroups
 *
 * Return a standard object for select host groups.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $multiSelectHostGroupData   Array with hostgroups from Zabbix
 */
function multiSelectHostGroups($multiSelectHostGroupData) {
    return (new CMultiSelect(
            [
        'name' => 'groupids[]',
        'objectName' => 'hostGroup',
        'data' => $multiSelectHostGroupData,
        'popup' => [
            'parameters' => 'srctbl=host_groups&dstfrm=zbx_filter&dstfld1=groupids_' .
            '&srcfld1=groupid&multiselect=1'
        ]
            ]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
    ;
}

/**
 * selectedHostGroups
 *
 * Return data from grouphosts.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $groupids   Array with groupid to get grouphost data
 */
function selectedHostGroups($groupids) {
    $multiSelectHostGroupData = NULL;
    if ($groupids !== [] && $groupids !== NULL && $groupids[0] !== NULL) {
        $filterGroups = API::HostGroup()->get([
            'output' => ['groupid', 'name'],
            'groupids' => $groupids
        ]);

        foreach ($filterGroups as $group) {
            $multiSelectHostGroupData[] = [
                'id' => $group['groupid'],
                'name' => $group['name']
            ];
        }
    }
//    var_dump($multiSelectHostGroupData);
    return $multiSelectHostGroupData;
}

/**
 * selectHostsByGroup
 *
 * Return Hosts from a list of groupids with inventory data.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $groupids   Array with groupid to get hosts
 * @param integer   $inventoryFields   Array with name of inventory fields to return with host data
 */
function selectHostsByGroup($groupids, $inventoryFields = NULL) {
    $multiSelectHostData = [];
    if ($groupids !== [] && $groupids !== NULL && $groupids[0] !== NULL) {
        // Get hosts only with inventory enabled
        $filterHosts = API::Host()->get([
            'output' => ['hostid', 'name'],
            'selectInventory' => $inventoryFields,
            'withInventory' => true,
            'groupids' => $groupids
        ]);
        foreach ($filterHosts as $host) {
            $tmp = [
                'id' => $host['hostid'],
                'name' => $host['name']
            ];
            foreach ($inventoryFields as $Inv) {
                $tmp[$Inv] = $host['inventory'][$Inv];
            }
            $multiSelectHostData[] = $tmp;
        }
    }
    return $multiSelectHostData;
}

/**
 * selectEventsByGroup
 *
 * Return active events from a list of groupids.
 * @author Adail Horst <the.spaww@gmail.com>
 * 
 * @param array     $groupids   Array with groupid to search triggers and events
 * @param integer   $status     Status of trigger (1 - Problem, 0 - OK)
 * @param integer   $severity   Minimum severity of trigger
 */
function selectEventsByGroup($groupids, $status = 1, $severity = 0) {
    $events = [];
    if ($groupids !== [] && $groupids !== NULL && $groupids[0] !== NULL) {
        // Find active triggers from selected host groups
        $events = API::Trigger()->get([
            'output' => ['triggerid', 'description', 'priority'
            //, 'expression', 'flags', 'url'
            ],
            'selectHosts' => ['hostid'],
            'active' => true,
            'only_true' => true,
            'expandComment' => true,
            'expandDescription' => true,
            'groupids' => $groupids,
            'preservekeys' => true,
            'selectLastEvent' => true,
            'filter' => ["value" => $status],
            'min_severity' => $severity
        ]);
    }
    return $events;
}

/* Return Hosts form a list of hostids with inventory data */

function selectedHosts($hostids, $inventoryFields = NULL) {
    $multiSelectHostData = [];
    if ($hostids !== [] && $hostids !== NULL && $hostids[0] !== NULL) {
        $filterHosts = API::Host()->get([
            'output' => ['hostid', 'name'],
            'selectInventory' => [$inventoryFields],
            'hostids' => $hostids
        ]);

        foreach ($filterHosts as $host) {
            $multiSelectHostData[] = [
                'id' => $host['hostid'],
                'name' => $host['name']
            ];
        }
    }
    return $multiSelectHostData;
}

function hostMacroValue($hostid, $macroName, $default = 0) {
    // Recupera o valor das macros no host
    $array_host = API::Host()->get([
        'output' => ['name', 'hostid'],
        'hostids' => $hostid,
        'selectMacros' => ['hostmacroid', 'macro', 'value']
    ]);
    foreach ($array_host[0]["macros"] as $row) {
        if ($row["macro"] == $macroName) {
            $macroValue = $row["value"];
            break;
        }
    }
    if (!isset($macroValue) || $macroValue == NULL) {
        $macroValue = globalMacroValue($macroName);
    }
    // Recupera o valor global da macro caso ela não exista no host
    return $macroValue;
}

function globalMacroValue($macroName, $default = 0) {
    global $globalMacros;
    if (!isset($globalMacros)) {
        $globalMacros = API::UserMacro()->get([
            'output' => ['globalmacroid', 'macro', 'value'],
            'globalmacro' => true,
            'preservekeys' => true
        ]);
    }
    foreach ($globalMacros as $row) {
        if ($row["macro"] == $macroName) {
            $macroValue = $row["value"];
            break;
        }
    }
    return ( isset($macroValue) ? $macroValue : $default);
}

function hostName($hostid, $array_host = []) {
    if ($array_host == []) {
        $array_host = API::Host()->get([
            'output' => ['name', 'hostid'],
            'hostids' => $hostid
//            , 'selectGraphs' => API_OUTPUT_COUNT
//            , 'selectScreens' => API_OUTPUT_COUNT
//            , 'preservekeys' => true
        ]);
    }
//var_dump($hostid);
//var_dump($array_host);
    $retorno = "";
    foreach ($array_host as $rowData) {
        foreach ($hostid as $rowID) {
            if ($rowData['hostid'] == $rowID) {
                $retorno = ($retorno == "" ? "" : ", ") . $rowData['name'];
            }
        }
    }
    return $retorno;
}

function zbxeDBConditionInt($p_field, $p_array) {
    if ($p_array !== [] && $p_array !== NULL && $p_array[0] !== NULL) {
        return dbConditionInt($p_field, $p_array);
    } else {
        return "";
    }
}

// End Functions
// Enviroment configuration
try {
    $result = DBselect('select 1 from zbxe_translation where 0 = 1');
    if (!$result) {
        $url = baseURL() . "/local/app/everyz/include/initDBEverys.php?p_versao_zbx=" . ezZabbixVersion() . "&p_modo_install=N";
//{$_SERVER['REQUEST_URI']}
        echo "bd nao iniciado!" . $url;
        $ch = curl_init();
// set url 
        curl_setopt($ch, CURLOPT_URL, $url);

//return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// $output contains the output string 
        $output = curl_exec($ch);

        echo "[" . $output . "]";
// close curl resource to free up system resources 
        curl_close($ch);
    }
} catch (Exception $e) {
// We got an exception == table not found
    return FALSE;
}

function zeDBConditionInt($fieldName, array $values, $notIn = false, $sort = true) {
    return (count($values) > 0 ? dbConditionInt($fieldName, $values) : "");
}

function buttonOptions($name, $value, $options, $values = []) {
    $radioOptions = (new CRadioButtonList($name, (int) $value));
    for ($i = 0; $i < count($options); $i++) {
        $radioOptions->addValue($options[$i], (count($values) > 0 ? $values[$i] : $i));
    }
    $radioOptions->setModern(true);
    return $radioOptions;
}

function buttonOutputFormat($name, $value) {
    return (new CRadioButtonList($name, (int) $value))->addValue('HTML', 0)->addValue('CSV', 1)->addValue('JSON', 2)->setModern(true);
}

// Adiciona link para javascript externo
function zeAddJsFile($scripts) {
    if (!is_array($scripts)) {
        $scripts = array($scripts);
    }
    foreach ($scripts as $script) {
        echo "\n" . '<script src="' . $script . '"></script>';
    }
}

function zeCancelButton($url) {
    return (new CRedirectButton(_('Cancel'), $url, null))->setId('cancel');
}

function zbxeMapTitleColor() {
    return zbxeFieldValue("select tx_value from zbxe_preferences where tx_option='map_title_color'", "tx_value");
}

function zbxeMapShowTitle() {
    return zbxeFieldValue("select tx_value from zbxe_preferences where tx_option='map_title_show'", "tx_value");
}

function zbxeCompanyName() {
    return zbxeFieldValue("select tx_value from zbxe_preferences where tx_option='company_name'", "tx_value") . " ";
}

function zbxeCompanyNameSize() {
    $empresa = zbxeCompanyName();
    $tamanho = (120 + (strlen($empresa) * 4));
    return $tamanho;
}

function zbxeImageId($name) {
    $query = "SELECT imageid FROM images WHERE name = '" . $name . "'";
    return zbxeFieldValue($query, 'imageid');
}

function zbxeImageName($id) {
    $query = "SELECT name FROM images WHERE imageid = '" . $id . "'";
    return zbxeFieldValue($query, 'name');
}

function zbxeJSONKey($name, $value) {
    $value = '"' . ltrim(rtrim($value, "'"), "'") . '"';
    return "\"" . $name . "\": " . $value;
}

function zbxeComboIconMap($p_name = 'iconmapid', $p_default = 0) {
    // icon maps
    $data = [];
    $data['iconMaps'] = API::IconMap()->get([
        'output' => ['iconmapid', 'name'],
        'preservekeys' => true
    ]);
    order_result($data['iconMaps'], 'name');
    // Append iconmapping to form list.
    $cmbIconMap = (new CComboBox($p_name, $p_default))->addItem(0, _('<manual>'));
    foreach ($data['iconMaps'] as $iconMap) {
        $cmbIconMap->addItem($iconMap['iconmapid'], $iconMap['name']);
    }
    return $cmbIconMap;
}

function zbxeInventoryField($inventoryId) {
    $inventoryFields = ["", "type", "type_full", "name", "alias", "os", "os_full"
        , "os_short", "serialno_a", "serialno_b", "tag", "asset_tag"
        , "macaddress_a", "macaddress_b", "hardware", "hardware_full"
        , "software", "software_full", "software_app_a"
        , "software_app_b", "software_app_c", "software_app_d"
        , "software_app_e", "contact", "location", "location_lat"
        , "location_lon", "notes", "chassis", "model", "hw_arch"
        , "vendor", "contract_number", "installer_name", "deployment_status"
        , "url_a", "url_b", "url_c", "host_networks", "host_netmask"
        , "host_router", "oob_ip", "oob_netmask", "oob_router"
        , "date_hw_purchase", "date_hw_install", "date_hw_expiry"
        , "date_hw_decomm", "site_address_a", "site_address_b"
        , "site_address_c", "site_city", "site_state"
        , "site_country", "site_zip", "site_rack"
        , "site_notes", "poc_1_name", "poc_1_email"
        , "poc_1_phone_a", "poc_1_phone_b", "poc_1_cell"
        , "poc_1_screen", "poc_1_notes", "poc_2_name"
        , "poc_2_email", "poc_2_phone_a", "poc_2_phone_b"
        , "poc_2_cell", "poc_2_screen", "poc_2_notes"];
    return $inventoryFields[$inventoryId];
}



?>
