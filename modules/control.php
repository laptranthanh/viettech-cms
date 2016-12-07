<?php
	$menuPage = $db->alone_data_where('menu','file','home');
	if( isset($name) ) {
		$menuPage = $db->alone_data_where('menu','name',$name);

		$configMenu = $db->alone_data_where('file','file',$menuPage->file);
		if($configMenu){
			$listAdd = $db->list_data_where_where('config','type','add','file','idList');
			foreach($listAdd as $configAdd){
				$nameAdd = $configAdd->name;
				$configMenu->$nameAdd = $db->list_data_where_where_order('file_data','parent',$configMenu->id,'group',$nameAdd,'pos','ASC');
			}
		}
		$GLOBALS['configMenu'] = $configMenu;
		$file = $menuPage->file;
		if(isset($_POST['action'])){
			$table = $_POST['table'];
			$action = $_POST['action'];
			unset($_POST['table']);
			unset($_POST['action']);
			switch ($action) {
				case 'add':
					$sql = 'INSERT INTO `'.dbPrefix.$table.'`(';
						foreach($_POST as $key=>$get){
							$sql.= '`'.$key.'`,';
						}
					$sql.= '`title`) VALUES (';
						foreach($_POST as $key=>$get){
							$sql.= '"'.$get.'",';
						}
					$sql.= '"None");';
					if($db->execute_sql($sql)){
						$success = 'Thêm thành công !';
					}else{
						echo $sql;
					}
					break;
				
				case 'del':
					$value = $_POST['value'];
					if($value !== '' && $value !== 0 && $value !== '0'){
						$sql = 'DELETE FROM `'.dbPrefix.$table.'` WHERE `id` = "'.$value.'"; ';
						delFile($db->alone_data_where($table,'id',$value));
						switch ($table) {
							case 'menu':
								$allListMenuChild = array();
								$allListMenuChild = $db->allListMenuChild($value,$allListMenuChild);
								$allListDataChild = $db->allListDataChild($value);
								foreach($allListMenuChild as $menu){
									if($menu->id !== 0 && $menu->id !== '0' && $menu->id !== ''){
										$sql.='DELETE FROM `'.dbPrefix.'menu` WHERE `menu_parent` = "'.$menu->id.'"; ';
										$sql.='DELETE FROM `'.dbPrefix.'data` WHERE `menu` = "'.$menu->id.'"; ';
									}
								}
								$sql.='DELETE FROM `'.dbPrefix.'data` WHERE `data_parent` = -1 '; 
								foreach($allListDataChild as $data){
									$sql.=' OR `data_parent` = '.$data->id;
								}
								$sql.=' ; ';
								break;
							case 'data':
								$sql.='DELETE FROM `'.dbPrefix.'data` WHERE `data_parent` = "'.$value.'"; ';
								break;
						}
					}
					if($db->execute_sql($sql)){
						$success = 'Xóa thành công !';
					}else{
						echo $sql;
					}
					break;
			}
		}else if(count($_POST)){
			$timeNow = '-'.renameTitle(timeNow());
			$target_dir = '../upload/';
			$_POST['id'] = (isset($idList))?$idList:$menuPage->id;
			$_POST['table'] = 'menu';
			if(isset($id)){
				$_POST['id'] = $id;
				$_POST['table'] = 'data';
			}
			if($file == 'info'){
				$array = [];
				$dataPage = $db->list_data('page');
			}

			if(isset($_FILES)){

				foreach ($_FILES as $keyAction => $arFile) {
					switch ($keyAction) {
						case 'slideData':
							foreach($arFile['name'] as $key=>$vl){
								$fileName = $arFile['name'][$key];
								$tmpName = $arFile['tmp_name'][$key];
								$uploadFile = uploadFile($fileName,$tmpName);
								if($uploadFile['success']){
									$post = array(
										'data_parent'=>$_POST['id'],
										'type'=>'slide',
										'img'=>$uploadFile['img'],
									);
									$db->insertImage('data',$post);
								}					
							}
							break;
						case 'listImageType':
							foreach($arFile['name'] as $type=>$listFile){
								foreach ($arFile['name'][$type] as $key => $vl) {
									$fileName = $arFile['name'][$type][$key];
									$tmpName = $arFile['tmp_name'][$type][$key];
									$uploadFile = uploadFile($fileName,$tmpName);
									if($uploadFile['success']){
										$post = array(
											'menu'=>$_POST['id'],
											'type'=>$type,
											'img'=>$uploadFile['img'],
										);
										$db->insertImage('data',$post);
									}
								}
							}
							break;
						case 'info':
							foreach ($arFile['name'] as $key => $vl) {
								$fileName = $arFile['name'][$key];
								$tmpName = $arFile['tmp_name'][$key];
								$uploadFile = uploadFile($fileName,$tmpName);
								if($uploadFile['success']){
									$data = $db->alone_data_where('page','name',$key);
									delFileCol($data,'content');
									$array[$key] = $uploadFile['img'];
								}
							}
							break;
						default:
							$thumb = '';
							if($keyAction == 'img'){
								$thumb = 'thumb';
							}
							$uploadFile = uploadFile($arFile['name'],$arFile['tmp_name'],$thumb);
							if($uploadFile['success']){
								$data = $db->alone_data_where($_POST['table'],'id',$_POST['id']);
								delFileCol($data,$keyAction);
								$_POST[$keyAction] = $uploadFile['img'];
								if($keyAction == 'img'){
									$_POST['thumbnail'] = $uploadFile['thumb'];
									delFileCol($data,'thumbnail');
								}
							}
							break;
					}
				}
			}
			if(isset($dataPage)){
				foreach($dataPage as $data){
					if(isset($_POST[$data->name])) $array[$data->name] = $_POST[$data->name];
				}
				if($db->updateTable('page',$array,'content','name')){
					$success = 'Lưu thành công !';
				}

			}
			if(isset($_POST['listRow'])){
				$listRow = $_POST['listRow'];
				foreach ($listRow as $table => $row) {
					foreach ($row as $rowId => $data ) {
						$db->updateRow($table,$data,'id',$rowId);
					}
				}
			}
			if(isset($_POST['table'])){
				$table = $_POST['table'];
				$value = $_POST['id'];
				if($db->updateRow($table,$_POST,'id',$value)){
					$success = 'Lưu thành công !';
				}
			}
			clearCache();
		}
		
		$menuPage = $db->alone_data_where('menu','name',$name);
		$idMenu = $menuPage->id;

		if (isset($id)) {
			$page = $db->alone_data_where('data','id',$id);
			$update['view'] = $page->view + 1;
			$db->updateRow('data',$update,'id',$id);
			$idMenu = $page->menu;
		}else if(isset($idList)){
			$page = $db->alone_data_where('menu','id',$idList);
			$idMenu = $page->id;
		}
	}

	$password = $db->alone_data_where('page','name','password');
	$password = $password->content;

	$listMenu = $db->list_data_where_where_order('menu','menu_parent',0,'hide',0,'pos','ASC');
	$listMenuAdmin = $db->list_data_where_order('menu','menu_parent',0,'pos','ASC');
	$allListMenu = $db->allListMenu();
	$listPage = $db->list_data('page');

	$listConfig = $db->list_data('config');
	$config = new stdClass();
	foreach ($listConfig as $vl) {
		$key = $vl->name;
		if(strlen($key)){
			$config->$key = $vl->value;
		}
	}
	$infoPage = new stdClass();
	foreach ($listPage as $vl) {
		$key = $vl->name;
		if(strlen($key)){
			$infoPage->$key = $vl->content;
		}
	}

	if(isset($menuPage)){
		$title = $infoPage->title;
		$image = $infoPage->logo;
		$des = $infoPage->des;
		$keywords = $infoPage->keywords;
		
		if(isset($page) || isset($id)){
			$title = $page->title;
			$image = $page->img;
			$des = $page->des;
			if(isset($page->keywords) && strlen($page->keywords) > 0) $keywords = $page->keywords;
			if(isset($id) && $page->price !== '0' && $page->price !== '') $des = $page->price.' - '.$des;
		}else if($menuPage->file !== 'home'){
			$title = $menuPage->title;
			$image = $menuPage->img;
			$des = $menuPage->des;
			$keywords = $menuPage->keywords;
		}
	}
	foreach($allListMenu as $menu){
		$nameMenu = 'menu'.ucfirst($menu->file);
		$$nameMenu = $db->alone_data_where('menu','file',$menu->file);
	}
	$listImageHome = $db->list_data_where_order('file_data','type','listImg','pos','ASC');

	if(count($listImageHome)){
		$list = new stdClass;
		foreach($listImageHome as $listImage){
			if($listImage->name){
				$listName = $listImage->name;
				$list->$listName = $db->list_data_where_where_order('data','menu',$menuHome->id,'type',$listName,'pos','ASC');
			}
		}
	}
	
?>