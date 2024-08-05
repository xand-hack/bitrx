<?
CModule::IncludeModule("tasks");

$id = "{{ID_TASK}}"; 
$idTask = $id;

$connection = Bitrix\Main\Application::getConnection(); 

$sql3 = "SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID  WHERE ID = {$id}";
$recordset3 = $connection->query($sql3);
$record3 = $recordset3->fetch();
$string = $record3["DESCRIPTION"];

$pattern = '/IM_DIALOG=chat(\d+)/';
preg_match($pattern, $string, $matches);
if (strpos($string, 'IM_DIALOG=chat') !== false) {
    if (isset($matches[1])) {
        $chatNumber = $matches[1];
        $sql = "SELECT * FROM b_im_chat WHERE ID = '{$chatNumber}'";
        $recordset = $connection->query($sql);
        if ($record = $recordset->fetch()) {
            $entityIdValue = $record['ENTITY_ID'];
            $parts = explode('|', $entityIdValue);
            if (isset($parts[1])) {
                $dealNumber = $parts[1];
                if($connection->query("SELECT OPENED FROM b_crm_deal WHERE ID = {$dealNumber}") != "N"){ 
                    $crmNew = serialize(array("D_{$dealNumber}"));
                    $connection->query("UPDATE b_uts_tasks_task SET UF_CRM_TASK = '{$crmNew}' WHERE VALUE_ID = {$idTask}");

                    $recordsetCheck = $connection->query("SELECT * FROM b_utm_tasks_task WHERE VALUE_ID = {$idTask} AND FIELD_ID = 6 AND VALUE = 'D_{$dealNumber}'");
                    if (!($recordCheck = $recordsetCheck->fetch())) {
                        $connection->query("INSERT INTO b_utm_tasks_task (VALUE_ID, FIELD_ID, VALUE, VALUE_INT, VALUE_DOUBLE, VALUE_DATE) VALUES ({$idTask}, 6, 'D_{$dealNumber}', NULL, NULL, NULL)");
                    }
                }
                else{
                    $arMessageFields = array(
                        "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
                        "FROM_USER_ID" => $record3["CREATED_BY"],
                        "TO_USER_ID" =>  $record3["CREATED_BY"],
                        "NOTIFY_MESSAGE" =>  "Создаваемая задача не может быть привязана к закрытой сделке", 
                        "NOTIFY_MESSAGE_OUT" => "Создаваемая задача не может быть привязана к закрытой сделке", 
                        "NOTIFY_MODULE" => "bizproc",
                        "NOTIFY_EVENT" => "activity"
                    );
                    
                    CIMNotify::Add($arMessageFields);
                }
            }
        }
    }
}

$extractNumber = function ($string, $level) {
    $parts = explode(' ', $string);
    $numOfDashes = substr_count($parts[0], '-');

    if ($level == $numOfDashes) {
        $numberPart = $parts[0];
        $count = 0;
        
        for ($i = 0; $i < strlen($numberPart); $i++) {
            if ($numberPart[$i] == '-') {
                $count++;
            }
            
            if ($count == $level) {
                $start = $i + 1;
                break;
            }
        }
        
        $end = strpos($numberPart, ' ');
        
        if ($end !== false) {
            $number = substr($numberPart, $start, $end - $start);
        } else {
            $number = substr($numberPart, $start);
        }
        
        return $number !== '' ? $number : NULL;
    } else {
        return NULL;
    }
};

$checkDuplicatesAndGaps = function ($array) {

    if(is_countable($array)) {
        $n = count($array);
    } else {
        return false;
    }

    $expectedSum = ($n * ($n + 1)) / 2; 
    
    $currentSum = 0;
    $prevNumber = 0;
    
    foreach ($array as $number) {
        $currentSum += (int) $number; 
        if ($number == $prevNumber) {
            return false;
        } elseif ($number > $prevNumber + 1) {
            return false;
        }
        $prevNumber = $number;
    }
    
    if ($currentSum != $expectedSum) {
        return false;
    }
    
    return true;
};


$extractDealNumber = function ($string) {
    $parts = explode('-', $string);
    $number = trim($parts[0]);
    return $number !== '' ? $number : null;
};

$checkFormat = function ($string) {
    if (empty($string)) {
      return false;
    }
    
    $parts = explode("-", explode(" ", $string)[0]);
    
    foreach ($parts as $part) {
      if (!is_numeric($part)) {
        return false;
      }
    }
    
    return true;
  };

$setCrm = function($connection, $idTask, $crmNew) {

    $connection->query("DELETE FROM  b_utm_tasks_task WHERE FIELD_ID = 6 AND VALUE_ID = {$idTask}"); 
    $connection->query("UPDATE b_uts_tasks_task SET UF_CRM_TASK = '{$crmNew}' WHERE VALUE_ID = {$idTask}"); 

    $crmArray = unserialize($crmNew);

    foreach($crmArray as $cm) {

        $recordset = $connection->query("SELECT * FROM b_utm_tasks_task WHERE VALUE_ID = {$idTask} AND FIELD_ID = 6 AND VALUE = '{$cm}'"); 

        if (!($record = $recordset->fetch())) {
            $connection->query("INSERT INTO b_utm_tasks_task  (VALUE_ID, FIELD_ID, VALUE, VALUE_INT, VALUE_DOUBLE, VALUE_DATE) VALUES ({$idTask}, 6, '{$cm}', NULL, NULL, NULL)"); 
        }
    }
};

$traverse = function($connection, $idTask, $crmNew) use (&$traverse) {

    $recordset12 = $connection->query("SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE PARENT_ID = {$idTask}"); 

    while ($record12 = $recordset12->fetch()) {
        $crmArray = unserialize($crmNew);

        $pid = $record12["ID"];

        $arFields = Array(
            "UF_CRM_TASK" => $crmArray,
        );
    
        $obTask = new CTasks;
        $success = $obTask->Update($pid, $arFields);

       // $setCrm($connection, $pid, $crmNew);

        //$traverse($connection, $pid, $crmNew);
    }  
};

$id = "{{ID_TASK}}"; 
$idTask = $id;

$connection = Bitrix\Main\Application::getConnection(); 

$sql3 = "SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID  WHERE ID = {$id}";

$recordset3 = $connection->query($sql3);

if ($record3 = $recordset3->fetch()) {

    if(isset($record3["UF_TEMPLATE_ID"])) {

        $idT = $record3["UF_TEMPLATE_ID"];

        $recordset0 = $connection->query("SELECT * FROM b_tasks_template WHERE ID = {$idT}"); 

        if ($record0 = $recordset0->fetch()) {
            $createdBy = $record0["CREATED_BY"];

            $connection->query("UPDATE b_tasks SET CREATED_BY = {$createdBy} WHERE ID = {$id}"); 
        }
    }

    if(isset($record3["FORKED_BY_TEMPLATE_ID"])) {
        $idT = $record3["FORKED_BY_TEMPLATE_ID"];

        $recordset0 = $connection->query("SELECT * FROM b_tasks_template WHERE ID = {$idT}"); 

        if ($record0 = $recordset0->fetch()) {
            $createdBy = $record0["CREATED_BY"];

            $connection->query("UPDATE b_tasks SET CREATED_BY = {$createdBy} WHERE ID = {$id}"); 
        }
    }

    $crm = $record3["UF_CRM_TASK"];

   /* $arMessageFields = array(
        "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
        "FROM_USER_ID" => $record3["CREATED_BY"],
        "TO_USER_ID" =>  $record3["CREATED_BY"],
        "NOTIFY_MESSAGE" =>  "_".count($crmAr)." ".print_r($crmAr, true), 
        "NOTIFY_MESSAGE_OUT" =>   "_".count($crmAr)." ".print_r($crmAr, true), 
        "NOTIFY_MODULE" => "bizproc",
        "NOTIFY_EVENT" => "activity"
    );
    
    CIMNotify::Add($arMessageFields);**/



        if(isset($record3["PARENT_ID"])) {

            $parent = $record3["PARENT_ID"];


            $recordset11 = $connection->query("SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE ID = {$parent}"); 

            if ($record11 = $recordset11->fetch()) {


                $crmParent = $record11["UF_CRM_TASK"];

                if($crm != $crmParent) {

                    $setCrm($connection, $id, $crmParent); 
                    
                    $crm = $crmParent;
                }
            }
        }


        $crmAr = unserialize($crm);
    

if(is_array($crmAr)) {

	$px = explode("_", $crmAr[0])[0];

	if($px == "D") {

		$idD =  explode("_", $crmAr[0])[1];
	
		$recordset = $connection->query("SELECT * FROM  b_crm_deal WHERE ID = {$idD}"); 

		if ($record = $recordset->fetch()) {

			if(strpos($record["STAGE_ID"], "WON") !== FALSE || strpos($record["STAGE_ID"], "LOSE") !== FALSE) {

				$connection->query("UPDATE b_uts_tasks_task SET UF_CRM_TASK = NULL WHERE VALUE_ID = {$id}"); 

				$crmAr = "_";

				$arMessageFields = array(
					"NOTIFY_TYPE" => IM_NOTIFY_FROM, 
					"FROM_USER_ID" => $record3["CREATED_BY"],
					"TO_USER_ID" =>  $record3["CREATED_BY"],
					"NOTIFY_MESSAGE" =>  "Создаваемая задача не может быть привязана к закрытой сделке", 
					"NOTIFY_MESSAGE_OUT" => "Создаваемая задача не может быть привязана к закрытой сделке", 
					"NOTIFY_MODULE" => "bizproc",
					"NOTIFY_EVENT" => "activity"
				);
				
				CIMNotify::Add($arMessageFields);
			}
		}
	}
}

if(is_array($crmAr)) {

            if(is_countable($crmAr) && count($crmAr) > 1) {
                $crmAr = array($crmAr[0]);
    
                $crm = serialize($crmAr);
    
                $setCrm($connection, $id, $crm); 
            }

$crmTo = $crm;
        
 //       $traverse($connection, $id, $crm);

        $title = $record3["TITLE"];

        $titleTask = $record3["TITLE"];

        $crm = unserialize($crm)[0];

        $px = explode("_", $crm)[0];

        if(!$crm || ($px != "D" && $px != "L")) {

            if(isset($record3["UF_FOLDER_ID"])) {
                $folder = \Bitrix\Disk\Folder::getById($record3["UF_FOLDER_ID"]);

                if($folder) {
                    $idFolder = $folder->getChild( 
                        array( 
                            '=NAME' => '!ИД_СДЕЛКИ',  
                            'TYPE' => \Bitrix\Disk\Internals\FolderTable::TYPE_FOLDER 
                        ) 
                    ); 

                    if($idFolder) {
                        $idFolder->markDeleted(1);
                    }

                    $fd = $record3["UF_FOLDER_ID"];

                    $sql99 = "SELECT * FROM b_disk_object WHERE LINK_OBJECT_ID = {$fd}";
            
                    $recordset99 = $connection->query($sql99);
                
                    while ($record99 = $recordset99->fetch()) {
                        if($record99["ID"] != $fd && $record99["STORAGE_ID"] == 488) {
                            $sourceObject = \Bitrix\Disk\BaseObject::loadById($record99["ID"]);

                            if($sourceObject) {
                                $sourceObject->markDeleted(1);
                            }
                        }
                    }
                }
            }
        }

        $titleTaskFolder = $titleTask;

        $forbidden = '\/:*?"<>|+%!@';
        $forbiddenText = 'CRM';
        $titleTaskFolder  = preg_replace("/[${forbidden}]/", '', $titleTaskFolder); 
        $titleTaskFolder  = preg_replace("/[${forbiddenText}]/", '', $titleTaskFolder);

        if($titleTaskFolder [strlen($titleTaskFolder )-1] == ".") {
            $titleTaskFolder [strlen($titleTaskFolder )-1] = " ";
        }
    

        ////////////////////////////////////////////////

        if($px == "L") {

            $crm = explode("_", $crm)[1];

            $sql4 = "SELECT * FROM  b_crm_lead INNER JOIN  b_uts_crm_lead ON b_crm_lead.ID =  b_uts_crm_lead.VALUE_ID 
                        WHERE ID = {$crm}";

            $recordset4 = $connection->query($sql4);

            if ($record4 = $recordset4->fetch()) {
                if(isset($record4["UF_CATALOG_LEAD"])) {

                    if(!isset($record3["UF_FOLDER_ID"])) {
                        $commonTaskFolder = \Bitrix\Disk\Folder::getById($record4["UF_CATALOG_LEAD"]);

                        if($commonTaskFolder) {
                            $taskFolder = $commonTaskFolder->addSubFolder(    array( 
                                'NAME' => $titleTaskFolder, 
                                'CREATED_BY' => 1 
                            )); 
        
                            if($taskFolder) {

                                $fld = $taskFolder;

                                $taskFolderId = $taskFolder->getId();
        
                                if(isset($taskFolderId)) {


                                 /*   $connection->query("UPDATE b_uts_tasks_task SET UF_FOLDER_ID = {$taskFolderId} WHERE VALUE_ID = {$id}"); 

                                    $leadFolder = \Bitrix\Disk\Folder::getById($record4["UF_CATALOG_LEAD"]);


                                    if($leadFolder) {


                                        $tskInFolder = $leadFolder->addSubFolder(    array( 
                                            'NAME' => $titleTaskFolder ,  
                                            'CREATED_BY' => 1 
                                        )); 

                                        if($tskInFolder) {
                                            $tid = $tskInFolder->getId();
                                        }
                                    }


                                    if($tid) {

                                        $sql66 = "SELECT * FROM b_disk_object WHERE ID = {$tid}";

                                        $recordset66 = $connection->query($sql66);

                                        if ($record66 = $recordset66->fetch()) {
                                            if($record66["REAL_OBJECT_ID"] != $taskFolderId ) {
                                                $connection->query("UPDATE b_disk_object SET REAL_OBJECT_ID = {$taskFolderId} WHERE ID = {$tid}");

                                                $arMessageFields = array(
                                                    "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
                                                    "FROM_USER_ID" => 5,
                                                    "TO_USER_ID" =>  5,
                                                    "NOTIFY_MESSAGE" =>  "_".$taskFolderId." ".$tid, 
                                                    "NOTIFY_MESSAGE_OUT" => "_".$taskFolderId." ".$tid, 
                                                    "NOTIFY_MODULE" => "bizproc",
                                                    "NOTIFY_EVENT" => "activity"
                                                );
                                                
                                                CIMNotify::Add($arMessageFields);
                                            }
                                        }
                                    } */
                                }
                            }

                        }
                    } else {
                        $folder = \Bitrix\Disk\Folder::getById($record3["UF_FOLDER_ID"]);

if($folder)
                        $folder->rename($titleTaskFolder);
            
                        $fld = $folder;
                    }

                    if($fld) {
                        $users = array();

                        $recordset12 = $connection->query("SELECT * FROM b_tasks_member WHERE TASK_ID = {$id}");
                        
                        while ($record12 = $recordset12->fetch()) {
                            $users[]=$record12["USER_ID"];
                        }

                        $rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager(); 

                        $accessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);
            
                        $newRights = array();

                        $fd = $fld->getId();
            
                            $errorCollection = new \Bitrix\Disk\Internals\Error\ErrorCollection();

                            $sql = "SELECT * FROM b_crm_observer WHERE ENTITY_TYPE_ID = 1 AND ENTITY_ID = {$crm}";

                            $recordset = $connection->query($sql);
                        
                            while ($record = $recordset->fetch()) {
                                $users[]=$record["USER_ID"];
                            }
    
                            $sql = "SELECT * FROM b_crm_lead WHERE ID = {$crm}";
    
                            $recordset = $connection->query($sql);
                        
                            if ($record = $recordset->fetch()) {
                                $users[]=$record["ASSIGNED_BY_ID"];
                                $users[]=$record["CREATED_BY"];
                            }
    
                
                        foreach($users as $user) {
                            if($user != "") {
                                $newRights[]=    array(
                                    'ACCESS_CODE' =>  "U".$user,
                                    'TASK_ID' =>  $accessTaskId,
                                );
                            }
                        }
                
                
                            Bitrix\Disk\Sharing::connectToUserStorage(
                                    $user, array(
                                        'SELF_CONNECT' => true,
                                        'CREATED_BY' => $user,
                                        'REAL_OBJECT' => $fld,
                                    ), $errorCollection
                                );
                
                            }


                            $sql5 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";
                
                            $recordset5 = $connection->query($sql5);
                        
                            while ($record5 = $recordset5->fetch()) {
                                $est  = false;
                
                                foreach($users as $user) {
                                    if($user == $record5["CREATED_BY"]) {
                                        $est = true;
                                        break;
                                    }
                                }
                
                                $sourceObject = \Bitrix\Disk\BaseObject::loadById($record5["LINK_OBJECT_ID"]);
                
                                if(!$est) {
                if($sourceObject)
                                    $sourceObject->markDeleted($record5["CREATED_BY"]);
                                }   else {
if($sourceObject)
                                    $sourceObject->rename($titleTaskFolder);
                                }
                            }
            
                        $rightsManager->delete($fld);
                        $rightsManager->set($fld, $newRights);
                    } 
                }
            }
        

        ////////////////////////////////////////////////
        if($px == "D") {
            $crm = explode("_", $crm)[1];

$idDeal=$crm;

            $sql4 = "SELECT * FROM  b_crm_deal INNER JOIN  b_uts_crm_deal ON b_crm_deal.ID =  b_uts_crm_deal.VALUE_ID   WHERE ID = {$crm}";

            $recordset4 = $connection->query($sql4);

            if ($record4 = $recordset4->fetch()) {

            $arAuditors = array();

            $arAuditors[] = $record4["ASSIGNED_BY_ID"];

            $sql89 = "SELECT * FROM b_crm_observer WHERE ENTITY_TYPE_ID = 2 AND ENTITY_ID = {$idDeal}";

            $recordset89 = $connection->query($sql89);

            while ($record89 = $recordset89->fetch()) {

                if($record89["USER_ID"] != 487) {
                    $arAuditors[]=$record89["USER_ID"];
                }
            }

            CTasks::AddAuditors($id, $arAuditors);

            if($crm > 1 /*>= 49602 || $record4["UF_OLD_DEAL_CATALOG"] == 1*/) {
if($crm >= 52102) {
       $numberDeal = sprintf("%04d", $crm - 52101); 
    }
              else  if(isset($record4["UF_OLD_ID"])) {
                    $numberDeal = $record4["UF_OLD_ID"];
                }
                else if($crm > 42102) {
                    $numberDeal = $crm - 42102;
                } else {
                    $numberDeal = 42102;
                }

                  //  $i=0;
                 //   $j=0;

				 $deal = $numberDeal;

				 $sql9 = "SELECT * FROM b_tasks INNER JOIN b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE ID = {$idTask}";
	
	$recordset9 = $connection->query($sql9);
	
	$maybeNotNeed = false;
	
	if ($record9 = $recordset9->fetch()) {
	
		if($record9["UF_DEPTH_LEVEL"]) {
	
			$depth = $record9["UF_DEPTH_LEVEL"];
			$parent = $record9["PARENT_ID"];
	
		//    echo $parent."\n";
	
			$countTasks = 0;
	
						$parentStr = "PARENT_ID = {$parent}";

						if(!$parent) {
							$parentStr = "(PARENT_ID = 0 OR PARENT_ID IS NULL)";
						}

                                $sql98 = "SELECT * FROM b_tasks INNER JOIN b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID 
WHERE UF_DEPTH_LEVEL = {$depth} AND UF_CRM_TASK LIKE '%\\\\\"D_{$crm}\\\\\"%' AND {$parentStr} AND ZOMBIE = 'N'  ORDER BY ID ASC";
	
			$recordset98 = $connection->query($sql98);
	
			$numbersOnLevel = array();
	
			$currentNumber = NULL;
	
			while ($record98 = $recordset98->fetch()) {
	
				$number = $extractNumber($record98["TITLE"], $depth);
				$dealNumber = $extractDealNumber($record98["TITLE"]);
	
			  //  echo $record98["ID"]." ".$record98["TITLE"]." ".$number."\n";
	
				if($record98["ID"] == $idTask && $number != NULL && $dealNumber == $deal) {
					$maybeNotNeed = true;
				}
	
				$numbersOnLevel[]=$number;
	
				$countTasks++;
			} 
	
			sort($numbersOnLevel);
	
		 //   echo print_r($numbersOnLevel);
	
			if(!$checkDuplicatesAndGaps($numbersOnLevel)) {

	
				$notCorrect = false;
				$parentTitle = "";
	
				if($parent) {
					$sql8 = "SELECT * FROM b_tasks WHERE ID = {$parent}";
	
					$recordset8 = $connection->query($sql8);
	
					if ($record8 = $recordset8->fetch()) {
	
						$title = $record8["TITLE"];
	
						if($extractDealNumber($record8["TITLE"]) == $deal) {
	
							$parentTitle = explode(" ", $record8["TITLE"])[0];
	
						} else {
							$notCorrect = true;
						}
	
					}
				}
	
				if(!$notCorrect) {
	
						$parentStr = "PARENT_ID = {$parent}";

						if(!$parent) {
							$parentStr = "(PARENT_ID = 0 OR PARENT_ID IS NULL)";
						}

                                $sql98 = "SELECT * FROM b_tasks INNER JOIN b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID 
WHERE UF_DEPTH_LEVEL = {$depth} AND UF_CRM_TASK LIKE '%\\\\\"D_{$crm}\\\\\"%' AND {$parentStr} AND ZOMBIE = 'N'  ORDER BY ID ASC";
		
					$recordset98 = $connection->query($sql98);
	
					$i=1;
	
					while ($record98 = $recordset98->fetch()) {
						$title = $record98["TITLE"];
	
						if($checkFormat($title)) {
							//$title = explode(" ", $title)[1];
$title =  implode(" ", array_slice(explode(" ", $title), 1));
						}
	
						if($parentTitle != "") {
							$title = $parentTitle."-".$i." ".$title;
						} else {
							$title = $numberDeal."-".$i." ".$title;
						}
	
						$connection->query("UPDATE b_tasks SET TITLE = '{$title}' WHERE ID =".$record98["ID"]);

if($record98["ID"] == $idTask) {
$titleTask = $title;
}
	
						$i++;
	
					 //   echo $title;
					}
				}
	
			} else {
	
				if(!$maybeNotNeed) {
	
					if($parent) {
						$sql8 = "SELECT * FROM b_tasks WHERE ID = {$parent}";
		
						$recordset8 = $connection->query($sql8);
		
						if ($record8 = $recordset8->fetch()) {
		
							$title = $record8["TITLE"];
		
							if($extractDealNumber($record8["TITLE"]) == $deal) {
		
								$parentTitle = explode(" ", $record8["TITLE"])[0];
		
							} else {
								$notCorrect = true;
							}
		
						}
					}
		
					if(!$notCorrect) {
	
						$sql8 = "SELECT * FROM b_tasks WHERE ID = {$idTask}";
		
						$recordset8 = $connection->query($sql8);
		
						if ($record8 = $recordset8->fetch()) {

$title = $record8["TITLE"];
	
							if($parentTitle != "") {
								$title = $parentTitle."-".$countTasks." ".$title;
							} else {
								$title = $numberDeal."-".$countTasks." ".$title;
							}
	
							$connection->query("UPDATE b_tasks SET TITLE = '{$title}' WHERE ID =".$idTask);
	$titleTask = $title;
	
						}
					}
				}
			}
	
		}
	}

$traverse($connection, $id, $crmTo);

                    $crm = "D_".$crm;

                  /*  $mainTask = $id;

                    if(isset($record3["PARENT_ID"]) && $record3["PARENT_ID"] !=0) {
                        $mainTask = $record3["PARENT_ID"];

                        $recordset12 = $connection->query("SELECT * FROM  b_tasks WHERE PARENT_ID = {$mainTask} AND ZOMBIE ='N'  ORDER BY ID ASC"); 

                        while ($record12 = $recordset12->fetch()) {
                            $j++;
        
                            if($record12["ID"] == $id) {
                                break;
                            }
                        }
                    }

                    $recordset11 = $connection->query("SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE UF_CRM_TASK LIKE '%\"{$crm}\"%' AND ZOMBIE ='N' AND (PARENT_ID IS NULL OR PARENT_ID = 0)  ORDER BY ID ASC"); 

                    while ($record11 = $recordset11->fetch()) {
                        $i++;

                        if($record11["ID"] == $mainTask) {
                            break;
                        }
                    }

                    $ts = explode(" ", $titleTask)[0];

                    if($ts) {
                        $tsAr = explode("-", $ts);
    
                        if(is_countable($tsAr) && count($tsAr) == 2) {
    
                            $ts01 = explode("-", $ts)[0];
                            $ts02 = explode("-", $ts)[1];
    
                            if(is_numeric($ts01) && is_numeric($ts02)) {
    
                                $titleTasks = explode(" ", $titleTask);
    
                                $titleTask="";
    
                                for($h=1; $h<count($titleTasks); $h++) {
                                    $titleTask.=$titleTasks[$h];
    
                                    if($h!=count($titleTasks)-1) {
                                        $titleTask.=" ";
                                    }
                                }
    
                            }
                        }
    
                        if(is_countable($tsAr) && count($tsAr) == 3) {
                            $ts01 = explode("-", $ts)[0];
                            $ts02 = explode("-", $ts)[1];
                            $ts03 = explode("-", $ts)[2];
    
                            if(is_numeric($ts01) && is_numeric($ts02) && is_numeric($ts03)) {
                                $titleTasks = explode(" ", $titleTask);
    
                                $titleTask="";
    
                                for($h=1; $h<count($titleTasks); $h++) {
                                    $titleTask.=$titleTasks[$i];
    
                                    if($h!=count($titleTasks)-1) {
                                        $titleTask.=" ";
                                    }
                                }
                            }
                        }
    
                    }

                    if(isset($record3["PARENT_ID"]) && $record3["PARENT_ID"]!=0) {
                        $titleTask = $numberDeal."-".$i."-".$j." ".$titleTask;
                    } else {
                        $titleTask = $numberDeal."-".$i." ".$titleTask;
                    }*/

                    $titleTask = mb_substr($titleTask , 0, 50);

                    $connection->query("UPDATE b_tasks SET TITLE = '{$titleTask}' WHERE ID={$id}");

                //  $connection->query("SELECT * FROM  b_tasks INNER JOIN  b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE UF_CRM_TASK LIKE '%\"{$crm}\"%' ORDER BY ID ASC");
                
                if($record3["UF_IS_FOLDER"] == 1) {
                    if(!isset($record3["UF_FOLDER_ID"])) {

        $titleTaskFolder = $titleTask;

        $forbidden = '\/:*?"<>|+%!@';
        $forbiddenText = 'CRM';
        $titleTaskFolder  = preg_replace("/[${forbidden}]/", '', $titleTaskFolder); 
        $titleTaskFolder  = preg_replace("/[${forbiddenText}]/", '', $titleTaskFolder);

        if($titleTaskFolder [strlen($titleTaskFolder )-1] == ".") {
            $titleTaskFolder [strlen($titleTaskFolder )-1] = " ";
        }
                
                        $commonTaskFolder = \Bitrix\Disk\Folder::getById($record4["UF_CATALOG"]);

                        if($commonTaskFolder) {
                            $taskFolder = $commonTaskFolder->addSubFolder(    array( 
                                'NAME' => $titleTaskFolder, 
                                'CREATED_BY' => 1 
                            )); 
        
                            if($taskFolder) {
                                $taskFolderId = $taskFolder->getId();
        
                                if(isset($taskFolderId)) {
                                    $connection->query("UPDATE b_uts_tasks_task SET UF_FOLDER_ID = {$taskFolderId} WHERE VALUE_ID = {$id}"); 

                                    $fld = $taskFolder;
                                }
                            }

                        }
                    } else {
                        $folder = \Bitrix\Disk\Folder::getById($record3["UF_FOLDER_ID"]);

if( $folder)
                        $folder->rename($titleTaskFolder);
            
            
                        $fld = $folder;
                    }

                    if($fld) {
                        $users = array();

                        $recordset12 = $connection->query("SELECT * FROM b_tasks_member WHERE TASK_ID = {$id}");
                        
                        while ($record12 = $recordset12->fetch()) {
                            $users[]=$record12["USER_ID"];
                        }

                        $rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager(); 

                        $accessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);
            
                        $newRights = array();

                        $fd = $fld->getId();


                        if($record3["STATUS"] == 3) {

            
                            $errorCollection = new \Bitrix\Disk\Internals\Error\ErrorCollection();
                
                            foreach($users as $user) {
                
                
                                Bitrix\Disk\Sharing::connectToUserStorage(
                                        $user, array(
                                            'SELF_CONNECT' => true,
                                            'CREATED_BY' => $user,
                                            'REAL_OBJECT' => $fld,
                                        ), $errorCollection
                                    );
                
                            }

                            $sql5 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = ".$record4["UF_CATALOG"];
                
                            $recordset5 = $connection->query($sql5);
                        
                            while ($record5 = $recordset5->fetch()) {
                                $users[]=$record5["CREATED_BY"];
                            }


                            $sql5 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";
                
                            $recordset5 = $connection->query($sql5);
                        
                            while ($record5 = $recordset5->fetch()) {
                                $est  = false;
                
                                foreach($users as $user) {
                                    if($user == $record5["CREATED_BY"]) {
                                        $est = true;
                                        break;
                                    }
                                }
                
                                $sourceObject = \Bitrix\Disk\BaseObject::loadById($record5["LINK_OBJECT_ID"]);
                
                                if(!$est) {
                if($sourceObject)
                                    $sourceObject->markDeleted($record5["CREATED_BY"]);
                                }   else {
if($sourceObject)
                                    $sourceObject->rename($titleTaskFolder);
                                }
                            }

                        } else {
                            $sql51 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$fd}";
                
                            $recordset51 = $connection->query($sql51);
                        
                            while ($record51 = $recordset51->fetch()) {
                                $sourceObject = \Bitrix\Disk\BaseObject::loadById($record51["LINK_OBJECT_ID"]);

                                if($sourceObject) {
                                    $sourceObject->markDeleted($record51["CREATED_BY"]);
                                }
                            }
                        }

                        $sql = "SELECT * FROM b_crm_observer WHERE ENTITY_TYPE_ID = 2 AND ENTITY_ID = {$idDeal}";

                        $recordset = $connection->query($sql);
                    
                        while ($record = $recordset->fetch()) {
                            $users[]=$record["USER_ID"];
                        }

                        $sql = "SELECT * FROM b_crm_deal WHERE ID = {$idDeal}";

                        $recordset = $connection->query($sql);
                    
                        if ($record = $recordset->fetch()) {
                            $users[]=$record["ASSIGNED_BY_ID"];
                            $users[]=$record["CREATED_BY"];
                        }


                        foreach($users as $user) {
                            if($user != "") {
                                $newRights[]=    array(
                                    'ACCESS_CODE' =>  "U".$user,
                                    'TASK_ID' =>  $accessTaskId,
                                );
                            }
                        }
            
                        $rightsManager->delete($fld);
                        $rightsManager->set($fld, $newRights);

                        $dealCatalog = $record4["UF_CATALOG"]; 

                        $dealFolder = \Bitrix\Disk\Folder::getById($dealCatalog);

                        /////////////////////////////////////////////////////////////////////// $fld

                        $gp = $record3["GROUP_ID"]; 


                        if($gp && $gp != 0) {


                            $fd = $fld->getId();

                            $sql90 = "SELECT * FROM  b_disk_storage WHERE ENTITY_TYPE = 'Bitrix\\\\Disk\\\\ProxyType\\\\Group' AND ENTITY_ID = {$gp}";
                
                            $recordset90  = $connection->query($sql90);
                        
                            if ($record90 = $recordset90->fetch()) {

                                $gpRoot = \Bitrix\Disk\BaseObject::loadById($record90["ROOT_OBJECT_ID"]);

                                if($gpRoot) {

                                    $gpRootId = $gpRoot->getId();

                                    $sql111 = "SELECT * FROM  b_disk_object WHERE REAL_OBJECT_ID = {$fd} AND PARENT_ID = {$gpRootId}";

                                    $recordset111 = $connection->query($sql111);
                            
                                    if ($record111 = $recordset111->fetch()) {

                                        if($record111["NAME"] != $titleTaskFolder) {

                                            $sourceObject = \Bitrix\Disk\BaseObject::loadById($record111["ID"]);
if($sourceObject)
                                            $sourceObject->rename($titleTaskFolder);
                                        }
                                        
                                    } else {

                                        $sql999 = "SELECT * FROM  b_sonet_group WHERE ID = {$gp}";
                
                                        $recordset999  = $connection->query($sql999);
                                    
                                        if ($record999 = $recordset999->fetch()) {

                                            $owner = $record999["OWNER_ID"];
                                        }

                                        if($owner) {

                                            $inGpFolder = $gpRoot->addSubFolder(    array( 
                                                'NAME' => $titleTaskFolder,  
                                                'CREATED_BY' => $owner 
                                            )); 
                
                                            if($inGpFolder) {

                                                $gid = $inGpFolder->getId();

                                                $connection->query("UPDATE b_disk_object SET REAL_OBJECT_ID = {$fd} WHERE ID = {$gid}");
                                            }
                                        }
                                    }
                                }
                            }

                            $sql222 = "SELECT b_disk_object.ID as id, b_disk_storage.ENTITY_ID as e_id FROM  b_disk_object INNER JOIN b_disk_storage ON b_disk_object.PARENT_ID = b_disk_storage.ROOT_OBJECT_ID
                                WHERE REAL_OBJECT_ID = {$fd} AND ENTITY_TYPE = 'Bitrix\\\\Disk\\\\ProxyType\\\\Group'";

                            $recordset222 = $connection->query($sql222);
                            
                            while ($record222 = $recordset222->fetch()) {
                                if($record222["e_id"] != $gp) {
                                    $sourceObject = \Bitrix\Disk\BaseObject::loadById($record222["id"]);

                                    if($sourceObject) {
                                        $sourceObject->markDeleted(1);
                                    }
                                }
                            }
                        }

                        /////////////////////////////////////////////////////////////////////// $fld

                        if($dealFolder) {

                            $fd = $fld->getId();

                         /*   $tskInFolder = $dealFolder->getChild( 
                                array( 
                                    '=NAME' => $titleTaskFolder,  
                                    'TYPE' => \Bitrix\Disk\Internals\FolderTable::TYPE_FOLDER 
                                ) 
                            ); 

                            if($tskInFolder) {
                                $tid = $tskInFolder->getId();
                            } else {

                                $tskInFolder = $dealFolder->addSubFolder(    array( 
                                    'NAME' => $titleTaskFolder ,  
                                    'CREATED_BY' => 1
                                )); 

                                if($tskInFolder) {
                                    $tid = $tskInFolder->getId();
                                }
                            }


                            if($tid) {
                                $sql66 = "SELECT * FROM b_disk_object WHERE ID = {$tid}";
            
                                $recordset66 = $connection->query($sql66);
                            
                                if ($record66 = $recordset66->fetch()) {
                                    if($record66["REAL_OBJECT_ID"] != $fd ) {
                                        $connection->query("UPDATE b_disk_object SET REAL_OBJECT_ID = {$fd} WHERE ID = {$tid}");
                                    }
                                }
                            }

                            $sql88 = "SELECT * FROM b_disk_object WHERE REAL_OBJECT_ID = {$fd}";
            
                            $recordset88 = $connection->query($sql88);
                        
                            while ($record88 = $recordset88->fetch()) {
                                if($record88["ID"] != $tid && $record88["ID"] != $fd && $record88["STORAGE_ID"] == 3) {
                                    $sourceObject = \Bitrix\Disk\BaseObject::loadById($record88["ID"]);

                                    if($sourceObject) {
                                        $sourceObject->markDeleted(1);
                                    }
                                }
                            }*/

                        ////////////////////////////////////////
                            $idFolder = $fld->getChild( 
                                array( 
                                    '=NAME' => '!ИД_СДЕЛКИ',  
                                    'TYPE' => \Bitrix\Disk\Internals\FolderTable::TYPE_FOLDER 
                                ) 
                            ); 

                            if($idFolder) {
                                $iFid = $idFolder->getId();
                            } else {

                                $idFolder = $fld->addSubFolder(    array( 
                                    'NAME' => '!ИД_СДЕЛКИ',  
                                    'CREATED_BY' => 1 
                                )); 

                                if($idFolder) {
                                    $iFid = $idFolder->getId();
                                }
                            }

                            if($iFid) {

                                $idDeal =  $dealFolder->getChild( 
                                    array( 
                                        '=NAME' => '!ИД',  
                                        'TYPE' => \Bitrix\Disk\Internals\FolderTable::TYPE_FOLDER 
                                    ) 
                                ); 

                                if($idDeal) {

                                    $idDealId = $idDeal->getId();

                                    $sql55 = "SELECT * FROM b_disk_object WHERE ID = {$iFid}";
            
                                    $recordset55 = $connection->query($sql55);
                                
                                    if ($record55 = $recordset55->fetch()) {
                                        if($record55["REAL_OBJECT_ID"] != $idDealId ) {
                                            $connection->query("UPDATE b_disk_object SET REAL_OBJECT_ID = {$idDealId} WHERE ID = {$iFid}");

                                            $sql66 = "SELECT * FROM b_disk_sharing WHERE REAL_OBJECT_ID = {$iFid} AND LINK_STORAGE_ID = 488 AND REAL_STORAGE_ID = 488";

                                            $recordset66 = $connection->query($sql66);
        
                                            if (!($record66 = $recordset66->fetch())) {
                                                $connection->query("INSERT INTO b_disk_sharing (CREATED_BY, FROM_ENTITY, TO_ENTITY, LINK_STORAGE_ID, LINK_OBJECT_ID, REAL_OBJECT_ID, REAL_STORAGE_ID,
                                                DESCRIPTION, CAN_FORWARD, STATUS, TYPE, TASK_NAME, IS_EDITABLE)
                                                VALUES (485, 'U485', 'U485', 488, {$iFid}, {$idDealId}, 488, '', 0, 3, 2, 'disk_access_read', 0 )");
        
                                            }
                                        }
                                    }
                                }
                            } 
                        }
                    }

                }
            }

        }
    }
} else {
    $arMessageFields = array(
        "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
        "FROM_USER_ID" => $record3["CREATED_BY"],
        "TO_USER_ID" =>  $record3["CREATED_BY"],
        "NOTIFY_MESSAGE" =>  "Задача не привязана ни к одному элементу CRM и поэтому не будет создана", 
        "NOTIFY_MESSAGE_OUT" => "Задача не привязана ни к одному элементу CRM и поэтому не будет создана", 
        "NOTIFY_MODULE" => "bizproc",
        "NOTIFY_EVENT" => "activity"
    );
    
    CIMNotify::Add($arMessageFields);

$this->SetVariable("isBreak", 1);

    CTasks::Delete($id);

    $connection->query("DELETE FROM b_uts_tasks_task WHERE VALUE_ID ={$id}");

}

} 