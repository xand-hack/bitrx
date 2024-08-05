<?


$theDate = new DateTime();
$stringDate = $theDate->format('Y-m-d');

$connection = Bitrix\Main\Application::getConnection(); 

$sql = "SELECT * FROM  b_crm_dynamic_items_128 WHERE STAGE_ID = 'DT128_3:NEW' AND CLOSEDATE < '{$stringDate}'";

$recordset = $connection->query($sql);

while ($record = $recordset->fetch()) {

    $str = "Вам необходимо актуализировать Планируемую дату оплаты";

   $title = $record["TITLE"];

   $id = $record["ID"];
   $assigned = $record["ASSIGNED_BY_ID"];

   $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_ID = 'DYNAMIC_128|".$id."'";

   $recordset3 = $connection->query($sql3);

   try {

       if ($record3 = $recordset3->fetch()) {
           
            $chatId = $record3["ID"];

                $chat = new \CIMChat(); 

            $chat->AddUser($chatId, $assigned, $hideHistory = false, $skipMessage = false, $skipRecent = false); 
           $chat->AddUser($chatId, 487, $hideHistory = false, $skipMessage = false); 

           \CIMChat::AddMessage(array( 
               'FROM_USER_ID' => 487,  
               'TO_CHAT_ID' => $chatId,  
               'MESSAGE' => $str
));
       } else {
            $chat = new \CIMChat; 

           $chatId = $chat->Add(array( 
               'TITLE' => 'Счет на оплату: '.$title, 
               'TYPE' => 'C',
               'AUTHOR_ID' => $assigned,
               'ENTITY_TYPE' => 'CRM',
               'ENTITY_ID' => 'DYNAMIC_128|'.$id,
               'EXTRANET' => 'Y'
           ));

           $attach = new CIMMessageParamAttach(null, "#95c255");
           $attach->AddLink(Array(
               "NAME" => $name,
               "LINK" => "https://gkyw.ru/crm/type/128/details/".$id."/"
           ));

           \CIMChat::AddMessage(array( 
               'FROM_USER_ID' => 487,  
               'TO_CHAT_ID' => $chatId,
               'SYSTEM' => 'Y',
               'MESSAGE' => 'Создан чат для обсуждения Счета на оплату',
               "ATTACH" => Array($attach)
           ));

          $chat->AddUser($chatId, $assigned, $hideHistory = false, $skipMessage = false, $skipRecent = false); 
          $chat->AddUser($chatId, 487, $hideHistory = false, $skipMessage = false, $skipRecent = false); 

           \CIMChat::AddMessage(array( 
               'FROM_USER_ID' => 487,  
               'TO_CHAT_ID' => $chatId,  
               'MESSAGE' =>  $str
));
       }
   } catch (Exception $e) {
   }
}
