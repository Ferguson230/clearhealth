<?php

	$overwrite=true;


	require_once dirname(__FILE__)."/../../../cellini/bootstrap.php";
	set_time_limit(0);
	$import_file = "user_dataset.php";
	require $import_file;

	$default_password="password";
	$use_default_password=true;

	$address =& ORDataObject::factory('Address');
	$states = array_flip($address->getStatelist());

	$controller = new Controller();



	foreach($users as $user) {
		unset($userObject);
		unset($person);
		unset($provider);
		unset($importMap);	

		$importMap =& ORDataObject::factory('ImportMap',$user['user_id'],'user');
		
		if(($importMap->_populated&&
		  (!$overwrite))||
		  ($user['user_id']==1)){
			echo "User Already In:".$user['user_id']."\n";
			$patient_key=$importMap->new_id;
		}
		else{ 

		$userObject =& ORDataObject::factory('User');
		if($use_default_password==true){$user['password']=$default_password;}
		$user['disabled']='no';

		$userObject->populate_array($user);
		$user_key=$userObject->get('user_id');

		$person_key='';
		if($user['provider']){
			$provider =& ORDataObject::factory('Provider');
			$provider->persist();
			$person_key=$provider->get('person_id');
			$person =& ORDataObject::factory('Person',$person_key);
			$person->set_type(2);
		}
		else{
			$person =& ORDataObject::factory('Person');
			$person->set_type(4);
		}
		$person->set('first_name',$user['username']);
		$person->set('last_name',"System Generated");
		$person->set('identifier',$user['nickname']);
		$person->persist();
		$person_key=$person->get('person_id');
		$userObject->person_id=$person_key;

// cut and past Gacl code here. from M_User


			$t_list = $person->getTypeList();
			$types = $person->get('types');

			if (count($types) > 0) {
				$type = array_shift($types);
				if ($type > 0) {
					$group = strtolower(str_replace(' ','_',$t_list[$type]));
					$gacl_groups = $controller->security->sort_groups();
					$flat_groups = array();
					foreach($gacl_groups as $grp) {
						foreach($grp as $k => $v) {
							$flat_groups[$k] = $v;
						}
					}
					$userObject->groups = array();
					foreach($flat_groups as $id => $name) {
						$data = $controller->security->get_group_data($id);
						if ($data[2] == $group) {
							$gid = $data[0];
							$userObject->groups[$gid] = array('id'=>$data[0]);
							// move persist outside this loop for efficiency
							$userObject->persist();
							break;
						}
					}
				}
			}




// end pasted code
		$userObject->persist();

		$importMap->set('old_table_name','user');	
		$importMap->set('new_object_name','user');	
		$importMap->set('new_id',$user_key);	
		$importMap->persist();
		unset($importMap);
		echo "Imported User: ".$user_key." From: ".$user["user_id"]."\n";
		
		}

		flush();
	}
?>