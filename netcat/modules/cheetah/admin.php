<?php

// get settings
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");
require_once ($MODULE_FOLDER."cheetah/function.inc.php");

// language constants
if (is_file($MODULE_FOLDER.'cheetah/'.MAIN_LANG.'.lang.php')) {
	require_once($MODULE_FOLDER.'cheetah/'.MAIN_LANG.'.lang.php');
} else {
	require_once($MODULE_FOLDER.'cheetah/en.lang.php');
}

require_once ($ADMIN_FOLDER."modules/ui.php");

$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_CHEETAH;
$UI_CONFIG = new ui_config_module('cheetah');

// default phase
switch($act){
	case "save":
		$Enabled += 0;
		$nc_core->set_settings('Enabled', $Enabled, 'cheetah');
		$db->query("UPDATE `Module` SET `Inside_Admin`={$Enabled} WHERE `Keyword`='cheetah'");
		cheetah_cache();

		//create index SessionTime USING BTREE ON `Session` (SessionTime);
		//create index User_IP USING BTREE ON `Session` (User_IP);

		ob_end_clean();
		header('Location: admin.php');
		exit;
	default:
//		if(!$db->get_var("SELECT `Inside_Admin` FROM `Module` WHERE `Keyword`='cheetah'")){
//			$db->query("UPDATE `Module` SET `Inside_Admin`=1 WHERE `Keyword`='cheetah'");
//		}

		BeginHtml($Title2, $Title1);
		$UI_CONFIG->actionButtons[] = array(
			"id"      => "submit",
			"caption" => "Сохранить настройки",
			"action"  => "mainView.submitIframeForm('mainForm')",
		);
		$settings = $nc_core->get_settings('', 'cheetah');
		?>
		<form method='post' id='mainForm' action='admin.php'>
			<input type="hidden" name="act" value="save">
			<div class="nc_admin_settings_info_checked">
				<div>
					<input id="cheetah_enabled" type="checkbox" name="Enabled" value="1" <?= $settings['Enabled'] ? ' checked="checked"' : '' ?>>
					<label for="cheetah_enabled">Разрешить работу модуля</label>
				</div>
			</div>
		</form>
		<?php
		if($settings['Enabled']){
			$ok = true;
			if(is_writeable(dirname(_CHEETAH_FILE)) || is_writeable(_CHEETAH_FILE)){
				if(!file_exists(_CHEETAH_FILE)) cheetah_cache();

				foreach ($cheetah_test as $row){
					if(file_exists($row[0])){
						$content = file_get_contents($row[0]);
						if(strpos($content, $row[1]) === false){
							if(strpos($content, $row[2]) === false){
								nc_print_status('Версия файла ' . $row[0] . ' не совпадает с искомой', 'alert');
								$ok = false;
							} else {
								if(is_writable($row[0])){
									$content = str_replace($row[2], $row[3], $content);
									file_put_contents($row[0], $content);
								} else {
									nc_print_status('Файл ' . $row[0] . ' недоступен для записи', 'alert');
									$ok = false;
								}
							}
						}
					} else {
						nc_print_status('Файл ' . $row[0] . ' отсутствует', 'alert');
						$ok = false;
					}
				}
			} else {
				$ok = false;
				nc_print_status('Невозможно записать файл ' . _CHEETAH_FILE, 'error');
			}
			if($ok) nc_print_status('Все файлы корректно отредактированы', 'info');
		}
//		$UI_CONFIG->setting_cb('EnableSearch', NETCAT_MODULE_SEARCH_ADMIN_SETTING_ENABLE_SEARCH);
		// check permission
		$perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 0);
		// show settings form
		EndHtml();
		break;
}
