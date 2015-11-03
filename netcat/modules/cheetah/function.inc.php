<?php
/**
 * Created by PhpStorm.
 * User: pavelzotov
 * Date: 02.11.15
 * Time: 9:58
 */

/**
 * TODO:
 * повесить вызов cheetah_cache() на изменение настроек сайта, настройки модулей, изменение полей и системных таблиц
 * сделать проверку, прописаны строки вызова в файлах
 * прописать список действий, что и в каких файлах нужно прописать
 */

/**
 * Список исправлений в файлах нетката
 * Каждая строка в массиве:
 * 		путь к файлу
 * 		строка, по которой идет проверка, что мы уже поправили файл
 * 		какой блок надо найти в файле
 *	 	на что заменить найденый блок
 */
global $cheetah_test;

$cheetah_test = array(
	array(
		$nc_core->DOCUMENT_ROOT.'/vars.inc.php',
		'@include_once $MODULE_FOLDER."/cheetah/cheetah.php";',
		'$MODULE_TEMPLATE_FOLDER = $DOCUMENT_ROOT.$SUB_FOLDER.$HTTP_TEMPLATE_PATH."module/";',
		'$MODULE_TEMPLATE_FOLDER = $DOCUMENT_ROOT.$SUB_FOLDER.$HTTP_TEMPLATE_PATH."module/";
@include_once $MODULE_FOLDER."/cheetah/cheetah.php";
'
	),
	array(
		$nc_core->SYSTEM_FOLDER.'essences/nc_catalogue.class.php',
		'if(defined("_CHEETAH_LOADED")) $res = Cheetah::$Catalogue',
		'$res = $this->db->get_results("SELECT * FROM `Catalogue` ORDER BY `Priority`", ARRAY_A);',
		'if(defined("_CHEETAH_LOADED")) $res = Cheetah::$Catalogue;
else $res = $this->db->get_results("SELECT * FROM `Catalogue` ORDER BY `Priority`", ARRAY_A);'
	),
	array(
		$nc_core->SYSTEM_FOLDER.'nc_core.class.php',
		'if(defined("_CHEETAH_LOADED")) $res = Cheetah::$Settings[$catalogue_id];',
		'$res = $this->db->get_results("SELECT `Key`, `Module`, `Value` FROM `Settings` WHERE `Catalogue_ID` = $catalogue_id", ARRAY_A);',
		'if(defined("_CHEETAH_LOADED")) $res = Cheetah::$Settings[$catalogue_id];
else $res = $this->db->get_results("SELECT `Key`, `Module`, `Value` FROM `Settings` WHERE `Catalogue_ID` = $catalogue_id", ARRAY_A);'
	),
	array(
		$nc_core->SYSTEM_FOLDER.'nc_modules.class.php',
		'if(defined("_CHEETAH_LOADED")) $all_modules_data = Cheetah::$all_modules_data;',
		'$all_modules_data = $this->get_data(false, false);',
		'if(defined("_CHEETAH_LOADED")) $all_modules_data = Cheetah::$all_modules_data;
else $all_modules_data = $this->get_data(false, false);'
	),
	array(
		$nc_core->SYSTEM_FOLDER.'nc_modules.class.php',
		'if(defined("_CHEETAH_LOADED") && !$ignore_check) $modules_data = Cheetah::$all_modules_data;',
		'$modules_data = $this->get_data($reload, $ignore_check);',
		'if(defined("_CHEETAH_LOADED") && !$ignore_check) $modules_data = Cheetah::$all_modules_data;
else $modules_data = $this->get_data($reload, $ignore_check);'
	),
	array(
		$nc_core->SYSTEM_FOLDER.'nc_modules.class.php',
		'if(defined("_CHEETAH_LOADED")) $modules_data = Cheetah::$all_modules_data;',
		'$modules_data = $this->get_data();',
		'if(defined("_CHEETAH_LOADED")) $modules_data = Cheetah::$all_modules_data;
else $modules_data = $this->get_data();'
	),
	array(
		$nc_core->SYSTEM_FOLDER.'essences/nc_component.class.php',
		'if(defined("_CHEETAH_LOADED") && $this->_system_table_id) self::$all_fields[$this->_class_id . \'-\' . $this->_system_table_id] = Cheetah::$system_table_fields[$this->_system_table_id];',
		'if (!isset(self::$all_fields[$this->_class_id . \'-\' . $this->_system_table_id])) {',
		'if (!isset(self::$all_fields[$this->_class_id . \'-\' . $this->_system_table_id])) {
		if(defined("_CHEETAH_LOADED") && $this->_system_table_id) self::$all_fields[$this->_class_id . \'-\' . $this->_system_table_id] = Cheetah::$system_table_fields[$this->_system_table_id];
else '
	)
);
define('_CHEETAH_FILE', $nc_core->MODULE_FOLDER."cheetah/cheetah.php");

function cheetah_cache(){
	global $db, $nc_core;

//	$cheetah_file = $nc_core->MODULE_FOLDER."/cheetah/cheetah.php";

	if(!$nc_core->get_settings('Enabled', 'cheetah')){
		file_put_contents(_CHEETAH_FILE, '');
		return;
	}

	$cheetah = "<?php
define('_CHEETAH_LOADED', 1);
class Cheetah {\n";

//	кешируем значения
//	SELECT * FROM `Catalogue` ORDER BY `Priority`
	if($res = $db->get_results("SELECT * FROM `Catalogue` ORDER BY `Priority`", ARRAY_A)){
		$cheetah .= "\tpublic static \$Catalogue = ".var_export($res, true).";\n";
	}

//		SELECT `Key`, `Module`, `Value` FROM `Settings` WHERE `Catalogue_ID` = 0
//	это запрос с разбивкой по Catalogue_ID
	if($res = $db->get_results("SELECT `Key`, `Module`, `Value`, `Catalogue_ID` FROM `Settings`", ARRAY_A)){
		$settings = array();

		foreach($res as $row){
			$catalogue_id = $row['Catalogue_ID'];
			unset($row['Catalogue_ID']); //данный параметр нам сохранять и память под него выделять не нужно
			if(!isset($settings[$catalogue_id])) $settings[$catalogue_id] = array();
			$settings[$catalogue_id][] = $row;
		}
		unset($catalogue_id);
		$cheetah .= "\tpublic static \$Settings = ".var_export($settings, true).";\n";
		unset($settings);
	}

//	SELECT `Field_ID` as `id`,
//                            `Field_Name` as `name`,
//                            `TypeOfData_ID` as `type`,
//                            `Format` as `format`,
//                            `Description` AS `description`,
//                            `NotNull` AS `not_null`,
//                            `DefaultState` as `default`,
//                            `TypeOfEdit_ID` AS `edit_type`,
//                            `System_Table_ID` AS `system_table_id`,
//                            `Class_ID` AS `class_id`,
//                            IF(`TypeOfData_ID` IN (4, 10),
//                               SUBSTRING_INDEX(`Format`, ':', 1),
//                               '') AS `table`,
//                            1 AS `search`,
//                            `Inheritance` AS `inheritance`,
//                            `InTableView` AS `in_table_view`
//                       FROM `Field`
//                      WHERE `Checked` = 1  AND  `System_Table_ID` = '1'
//                      ORDER BY `Priority`

	$all_modules_data = $module_data = $nc_core->modules->get_data(false, false);
	$cheetah .= "\tpublic static \$all_modules_data = ".var_export($all_modules_data, true).";\n";

	$system_table_fields = array(
		1 => array(),
		2 => array(),
		3 => array(),
		4 => array()
	);
	if($res = $db->get_results("SELECT `Field_ID` as `id`,
                            `Field_Name` as `name`,
                            `TypeOfData_ID` as `type`,
                            `Format` as `format`,
                            `Description` AS `description`,
                            `NotNull` AS `not_null`,
                            `DefaultState` as `default`,
                            `TypeOfEdit_ID` AS `edit_type`,
                            `System_Table_ID` AS `system_table_id`,
                            `Class_ID` AS `class_id`,
                            IF(`TypeOfData_ID` IN (" . NC_FIELDTYPE_SELECT . ", " . NC_FIELDTYPE_MULTISELECT . "),
                               SUBSTRING_INDEX(`Format`, ':', 1),
                               '') AS `table`,
                            1 AS `search`,
                            `Inheritance` AS `inheritance`,
                            `InTableView` AS `in_table_view`
                       FROM `Field`
                      WHERE `Checked` = 1  AND `System_Table_ID`>0
                      ORDER BY `Priority`",
		ARRAY_A)){
		foreach($res as $row){
			$system_table_fields[$row['system_table_id']][] = $row;
		}
	}
	$cheetah .= "\tpublic static \$system_table_fields = ".var_export($system_table_fields, true).";\n";
	unset($system_table_fields);

	$cheetah .= "\n}";

	file_put_contents(_CHEETAH_FILE, $cheetah);
}

class cheetahListener {
	public function __construct () {
		$nc_core = nc_Core::get_object();
		$nc_core->event->bind($this, array('authorizeUser' => 'authorize_user') );
	}

	public function  authorize_user ( $user_id ) {
		return 0;
	}
}

//
//$listenObj = new  ListenUser();