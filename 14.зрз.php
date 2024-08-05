<?php
CModule::IncludeModule("im");

$id = "{{ID_TASK}}";
$createdBy = $this->GetVariable("Created_by");

$createdBy = explode("_", $createdBy)[1];

$connection = Bitrix\Main\Application::getConnection(); 

$sql2 = "SELECT * FROM b_tasks INNER JOIN b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE VALUE_ID = {$id}";

$recordset2 = $connection->query($sql2);

if ($record2 = $recordset2->fetch()) { 

    $status = $record2["STATUS"];
    
        if($record2["PRIORITY"] != $record2["UF_PREV_PRIORITY"]){
    
            if($record2["PRIORITY"] == 2){
                $sql3 = "SELECT * FROM b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;
                $recordset3 = $connection->query($sql3);
                $record3 = $recordset3->fetch();
                 
                $chatId = $record3["ID"];
    
                $chat = new \CIMChat(0);

                $chat->AddUser($chatId, $createdBy, $hideHistory = false, $skipMessage = false);
                
                \CIMChat::AddMessage(array(
                    'FROM_USER_ID' => $createdBy,
                    'TO_CHAT_ID' => $chatId,
                    'MESSAGE' => "Это важная задача"
                ));
            }
            if($record2["PRIORITY"] == 1){
                $sql3 = "SELECT * FROM b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;
                $recordset3 = $connection->query($sql3);
                $record3 = $recordset3->fetch();
                 
                $chatId = $record3["ID"];
    
                $chat = new \CIMChat(0);
                $chat->AddUser($chatId, $createdBy, $hideHistory = false, $skipMessage = false);
                \CIMChat::AddMessage(array(
                    'FROM_USER_ID' => $createdBy,
                    'TO_CHAT_ID' => $chatId,
                    'MESSAGE' => "Это не важная задача"
                ));
            }
        }  
    

    if(isset($record2["UF_PREV_STAGE"])) {

        if($record2["UF_PREV_STAGE"] != 0 && $record2["UF_PREV_STAGE"] != "") {

            if(($record2["UF_PREV_STAGE"] != $record2["STATUS"]) && $record2["STATUS"] == 5) {

                $mess="";

                $recordset33 = $connection->query("SELECT * FROM b_tasks WHERE PARENT_ID = {$id} AND STATUS != 5");

                while ($record33 = $recordset33->fetch()) {
                    $idTest = $record33["ID"];
                    $titleTask = $record33["TITLE"];
                    $mess.= "<a href='https://gkyw.ru/company/personal/user/1/tasks/task/view/{$idTest}/'>{$titleTask}</a><br>";
                }
                
                if($mess!="") {

                        $status = $record2["UF_PREV_STAGE"];

                        $connection->query("UPDATE b_tasks SET STATUS  = ".$record2["UF_PREV_STAGE"]." WHERE ID = {$id}");

                        $idMainTask = $record2["ID"];
                        $titleMainTask = $record2["TITLE"];

                        $hrefMainTask = "<a href='https://gkyw.ru/company/personal/user/1/tasks/task/view/{$idMainTask}/'>{$titleMainTask}</a>";

                        $arMessageFields = array(
                        "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
                        "FROM_USER_ID" => $createdBy,
                        "TO_USER_ID" =>  $createdBy,
                        "NOTIFY_MESSAGE" =>  "Закрыть задачу {$hrefMainTask} нельзя, так как у нее имеются следующие незакрытые дочерние задачи: ".$mess, 
                        "NOTIFY_MESSAGE_OUT" => "Закрыть задачу {$hrefMainTask} нельзя, так как у нее имеются следующие незакрытые дочерние задачи: ".$mess,  
                        "NOTIFY_MODULE" => "bizproc",
                        "NOTIFY_EVENT" => "activity"
                    );
                
                    CIMNotify::Add($arMessageFields);
                } else {

                    $sql44 = "SELECT * FROM b_uts_user WHERE VALUE_ID = ".$record2["RESPONSIBLE_ID"];

                    $recordset44 = $connection->query($sql44);

                    if ($record44 = $recordset44->fetch()) {
                        $systemWork = $record44["UF_USR_1691651430895"];
                    }

                    if($systemWork != 72 && ($record2["UF_FOT"] != 0 && $record2["UF_FOT"] !=0 && $record2["UF_FOT"] != NULL) && 
                        ($record2["UF_FOT_RESPONSE"] != 0 && $record2["UF_FOT_RESPONSE"] !=0 && $record2["UF_FOT_RESPONSE"] != NULL)) {

                        $status = $record2["UF_PREV_STAGE"];

                        $connection->query("UPDATE b_tasks SET STATUS  = ".$record2["UF_PREV_STAGE"]." WHERE ID = {$id}");

                        $idMainTask = $record2["ID"];
                        $titleMainTask = $record2["TITLE"];

                        $hrefMainTask = "<a href='https://gkyw.ru/company/personal/user/1/tasks/task/view/{$idMainTask}/'>{$titleMainTask}</a>";

                        $arMessageFields = array(
                        "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
                        "FROM_USER_ID" => $createdBy,
                        "TO_USER_ID" =>  $createdBy,
                        "NOTIFY_MESSAGE" =>  "Закрыть задачу {$hrefMainTask} нельзя, так как ответственный не относится к производственному сектору", 
                        "NOTIFY_MESSAGE_OUT" => "Закрыть задачу {$hrefMainTask} нельзя, так как ответственный не относится к производственному сектору",  
                        "NOTIFY_MODULE" => "bizproc",
                        "NOTIFY_EVENT" => "activity"
                        );
                    
                        CIMNotify::Add($arMessageFields);

                    } else {

                        $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;

                        $recordset3 = $connection->query($sql3);

                        if ($record3 = $recordset3->fetch()) {
                            
                            $chatId = $record3["ID"];

                            $chat = new \CIMChat; 

                            $chat->AddUser( $chatId, $createdBy, null, false, false);

                            \CIMChat::AddMessage(array( 
                                'FROM_USER_ID' => $createdBy,  
                                'TO_CHAT_ID' => $chatId,  
                                'MESSAGE' => "Задача завершена"
                            ));
                        }
            
                    } 
                }
            } else if(($record2["UF_PREV_STAGE"] != $record2["STATUS"]) && $record2["STATUS"] == 3) {
                /*$rsUser = CUser::GetByID($createdBy);
                $arUser = $rsUser->Fetch();*/

                $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;

                $recordset3 = $connection->query($sql3);

                if ($record3 = $recordset3->fetch()) {
                    
                    $chatId = $record3["ID"];

                    $chat = new \CIMChat; 

                    $chat->AddUser( $chatId, $createdBy, null, false, false);

                    \CIMChat::AddMessage(array( 
                        'FROM_USER_ID' => $createdBy,  
                        'TO_CHAT_ID' => $chatId,  
                        'MESSAGE' => "Начато выполнение задачи"
                    ));
                }
            } else if(($record2["UF_PREV_STAGE"] != $record2["STATUS"]) && $record2["STATUS"] == 4) {

                $rsUser = CUser::GetByID($record2["CREATED_BY"]);
                $arUser = $rsUser->Fetch();

                $mess.="[USER=".$arUser ["ID"]."]".$arUser["NAME"]." ".$arUser ["LAST_NAME"]."[/USER], задача выполнена, прошу принять работу.";

                $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;

                $recordset3 = $connection->query($sql3);

                if ($record3 = $recordset3->fetch()) {
                    
                    $chatId = $record3["ID"];

                    $sql22 = "SELECT * FROM  b_tasks INNER JOIN b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE VALUE_ID = {$id}";

                    $recordset22 = $connection->query($sql22);

                    if ($record22 = $recordset22->fetch()) { 

                        $chat = new \CIMChat; 

                        $chat->AddUser( $chatId, $record22["RESPONSIBLE_ID"], null, false, false);

                        \CIMChat::AddMessage(array( 
                            'FROM_USER_ID' => $record22["RESPONSIBLE_ID"],  
                            'TO_CHAT_ID' => $chatId,  
                            'MESSAGE' => $mess
                        ));
                    }
                }

            } else if(($record2["UF_PREV_STAGE"] != $record2["STATUS"]) && $record2["UF_PREV_STAGE"] == 4 && $record2["STATUS"] == 2) {

                $mess.="Задача возвращена на доработку.";

                $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;

                $recordset3 = $connection->query($sql3);

                if ($record3 = $recordset3->fetch()) {
                    
                    $chatId = $record3["ID"];

                    $sql22 = "SELECT * FROM  b_tasks INNER JOIN b_uts_tasks_task ON b_tasks.ID = b_uts_tasks_task.VALUE_ID WHERE VALUE_ID = {$id}";

                    $recordset22 = $connection->query($sql22);

                    if ($record22 = $recordset22->fetch()) { 

                        $chat = new \CIMChat; 

                        $chat->AddUser( $chatId, $record22["RESPONSIBLE_ID"], null, false, false);

                        \CIMChat::AddMessage(array( 
                            'FROM_USER_ID' => $record2["CREATED_BY"],  
                            'TO_CHAT_ID' => $chatId,  
                            'MESSAGE' => $mess
                        ));
                    }
                }
            }
            else if(($record2["UF_PREV_STAGE"] != $record2["STATUS"]) && $record2["STATUS"] == 6) {
                /*$rsUser = CUser::GetByID($createdBy);
                $arUser = $rsUser->Fetch();*/

                $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;

                $recordset3 = $connection->query($sql3);

                if ($record3 = $recordset3->fetch()) {
                    
                    $chatId = $record3["ID"];

                    $chat = new \CIMChat; 

                    $chat->AddUser( $chatId, $createdBy, null, false, false);

                    \CIMChat::AddMessage(array( 
                        'FROM_USER_ID' => $createdBy,  
                        'TO_CHAT_ID' => $chatId,  
                        'MESSAGE' => "Выполнение задачи отложено"
                    ));
                }
            }  else if(($record2["UF_PREV_STAGE"] != $record2["STATUS"]) && $record2["STATUS"] == 2 && $record2["UF_PREV_STAGE"] == 5) {
                $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;

                $recordset3 = $connection->query($sql3);

                if ($record3 = $recordset3->fetch()) {
                    
                    $chatId = $record3["ID"];

                    $chat = new \CIMChat; 

                    $chat->AddUser( $chatId, $createdBy, null, false, false);

                    \CIMChat::AddMessage(array( 
                        'FROM_USER_ID' => $createdBy,  
                        'TO_CHAT_ID' => $chatId,  
                        'MESSAGE' => "Задача возвращена в работу"
                    ));
                }
            } else if(($record2["UF_PREV_STAGE"] != $record2["STATUS"]) && $record2["STATUS"] == 2 && $record2["UF_PREV_STAGE"] == 3) {
                $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;

                $recordset3 = $connection->query($sql3);

                if ($record3 = $recordset3->fetch()) {
                    
                    $chatId = $record3["ID"];

                    $chat = new \CIMChat; 

                    $chat->AddUser( $chatId, $createdBy, null, false, false);

                    \CIMChat::AddMessage(array( 
                        'FROM_USER_ID' => $createdBy,  
                        'TO_CHAT_ID' => $chatId,  
                        'MESSAGE' => "Выполнение задачи приостановлено."
                    ));
                }
            }
        }
    }

    if($record2["DEADLINE"] != $record2["UF_OLD_DEADLINE"]) {
        $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;

        $recordset3 = $connection->query($sql3);

        if ($record3 = $recordset3->fetch()) {
            
            $chatId = $record3["ID"];

            $chat = new \CIMChat; 

            $chat->AddUser( $chatId, $createdBy, null, false, false);

            \CIMChat::AddMessage(array( 
                'FROM_USER_ID' => $createdBy,  
                'TO_CHAT_ID' => $chatId,  
                'MESSAGE' => "Новый срок выполнения задачи: ".$record2["DEADLINE"]
            ));
        }
    }

    if($record2["RESPONSIBLE_ID"] != $record2["UF_PREV_RESPONS"]) {

        if(isset($record2["UF_PREV_RESPONS"]) && $record2["UF_PREV_RESPONS"] != 0) {

            $rsUser = CUser::GetByID($record2["RESPONSIBLE_ID"]);
            $arUser = $rsUser->Fetch();

            if($arUser) {

                $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_TYPE = 'TASKS' AND ENTITY_ID = ".$id;

                $recordset3 = $connection->query($sql3);
        
                if ($record3 = $recordset3->fetch()) {
                    
                    $chatId = $record3["ID"];
        
                    $chat = new \CIMChat; 
        
                    $chat->AddUser( $chatId, $createdBy, null, false, false);

                    \CIMChat::AddMessage(array( 
                        'FROM_USER_ID' => $createdBy,  
                        'TO_CHAT_ID' => $chatId,  
                        'MESSAGE' => "Новый ответственный за выполнение задачи: [USER=".$record2["UF_PREV_RESPONS"]."]".$arUser["NAME"]." ".$arUser["LAST_NAME"]."[/USER]"
                    ));
                }
            }

        }

    }

    $connection->query("UPDATE b_uts_tasks_task SET UF_PREV_PRIORITY = {$record2['PRIORITY']} WHERE VALUE_ID = {$id}");
    $connection->query("UPDATE b_uts_tasks_task SET UF_PREV_RESPONS = (SELECT RESPONSIBLE_ID FROM b_tasks WHERE ID = {$id}) WHERE VALUE_ID = {$id}");
    $connection->query("UPDATE b_uts_tasks_task SET UF_PREV_STAGE = ".$status." WHERE VALUE_ID = {$id}");
    $connection->query("UPDATE b_uts_tasks_task SET UF_OLD_DEADLINE = (SELECT DEADLINE FROM b_tasks WHERE ID = {$id}) WHERE VALUE_ID = {$id}");
}