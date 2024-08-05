<?
CModule::IncludeModule('iblock');
CModule::IncludeModule("blog");
CModule::IncludeModule("socialnetwork");

$id = "{{ID_ELEM}}";


$connection = Bitrix\Main\Application::getConnection();

$users = array();

/////////////////////////////////////////////////////////////////////////////////////////////////////

$setWarning = function($whom, $createdBy, $idBlog, $accountStr) {

    for($i=0; $i<count($whom); $i++) {
        $rsUser = CUser::GetByID($whom[$i]); 
        $arUser = $rsUser->Fetch();
    
        $userStr.= "<a href='/company/personal/user/".$arUser["ID"]."/'>".$arUser["NAME"]." ".$arUser["LAST_NAME"]."</a>";
    }


    $text.=$accountStr; 

    $arFields= array(
        "TITLE" => "Замечание",
        "DETAIL_TEXT" => $text,
        "DATE_PUBLISH" => date('d.m.Y H:i:s'),
        "PUBLISH_STATUS" => "P",
        "ENABLE_TRACKBACK" => 'Y',
        "ENABLE_COMMENTS" => 'Y',
        "=DATE_CREATE" => "now()",
        "AUTHOR_ID" => $createdBy,
        "BLOG_ID" => $idBlog,
        "PATH" => "/company/personal/user/445/blog/#post_id#/",
        "MICRO" => 'Y',
        "HAS_SOCNET_ALL" => 'Y',
        "PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
        'HAS_TAGS' => 'N',
        "HAS_IMAGES" => 'N',
        "PERMS_POST" => Array(),
        "PERMS_COMMENT" => Array (),
        "UF_GRATITUDE" => 1500,
        "SOCNET_RIGHTS" => Array
        (
        "UA", "G2"
        ),
    );

    $newID= CBlogPost::Add($arFields);

    if(IntVal($newID)>0){

        $arFields["ID"] = $newID;

        $arParamsNotify = Array(
            "bSoNet" => true,
            "UserID" => $createdBy,
            "user_id" => $createdBy,
        );

        CBlogPost::Notify($arFields, array(), $arParamsNotify);

        $grat_id = 81;
        $honour_iblock_id = 2;

        $el = new CIBlockElement;
        $new_grat_element_id = $el->Add(
            array(
                "IBLOCK_ID" => $honour_iblock_id, 
                "DATE_ACTIVE_FROM" => date('d.m.Y H:i:s'),
                "NAME" => "Замечание"
            ),
            false,
            false
        );

        if ($new_grat_element_id > 0)
        {
            CIBlockElement::SetPropertyValuesEx(
                $new_grat_element_id,
                $honour_iblock_id,
                array(
                    "USERS" => $whom,
                    "GRATITUDE" => array("VALUE" =>  $grat_id)
                )
            );

            CBlogPost::Update($newID, array(
                "DETAIL_TEXT_TYPE" => "text",
                "UF_GRATITUDE" => $new_grat_element_id
            ));

            $res = \Bitrix\Socialnetwork\LogTable::getList(array(
                'filter' => array(
                    'EVENT_ID' => \Bitrix\Blog\Integration\Socialnetwork\Log::EVENT_ID_POST,
                    'SOURCE_ID' => $newID
                ),
                'select' => array('ID')
            ));

            if ($logFields = $res->fetch())
            {
                $logId = $logFields['ID'];

                $eventId = \Bitrix\Blog\Integration\Socialnetwork\Log::EVENT_ID_POST_GRAT;

                $logFields = array(
                    "EVENT_ID" => $eventId
                );

                if ($post = \Bitrix\Blog\Item\Post::getById($newID))
                {
                    $logFields["TAG"] = $post->getTags();
                }

                CSocNetLog::Update(intval($logId), $logFields);
                

                $arMessageFields = array(
                    "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
                    "FROM_USER_ID" => $createdBy,
                    "NOTIFY_MESSAGE" =>  $text, 
                    "NOTIFY_MESSAGE_OUT" => $text, 
                    "NOTIFY_MODULE" => "bizproc",
                    "NOTIFY_EVENT" => "activity"
                );

                for($i=0; $i<count($whom); $i++) {
                    $arMessageFields["TO_USER_ID"] = $whom[$i];
                    CIMNotify::Add($arMessageFields);
                }

               $arMessageFields["TO_USER_ID"] = $createdBy;

               CIMNotify::Add($arMessageFields);
            }
        }
    }
};


/////////////////////////////////////////////////////////////////////////////////////////////////////

$arSelect01 = Array("ID", "NAME", "ACTIVE_FROM", "PROPERTY_USERS");
$arFilter01 = Array("IBLOCK_ID" => 2, "ID" => $id);

$res01 = CIBlockElement::GetList(Array(), $arFilter01, false, false, $arSelect01);

while($ob01 = $res01->GetNextElement()) { 
    $arFields01 = $ob01->GetFields();
    $users[] = $arFields01["PROPERTY_USERS_VALUE"];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////

/*CModule::IncludeModule("im");

$arMessageFields = array(
    "NOTIFY_TYPE" => IM_NOTIFY_FROM, 
    "FROM_USER_ID" => 5,
    "TO_USER_ID" =>  5,
    "NOTIFY_MESSAGE" =>  print_r($users, true),
    "NOTIFY_MESSAGE_OUT" => print_r($users, true),
    "NOTIFY_MODULE" => "bizproc",
    "NOTIFY_EVENT" => "activity"
);

 CIMNotify::Add($arMessageFields);*/

 //$allStrikes = array();

if(is_countable($users) && count($users) > 0) {

    for($i=0; $i<count($users); $i++) {

        $userId = $users[$i];

        $lastStrikeDate = "";

        /////////////////////////////////////////////////////////////////////////////

        $sql4 = "SELECT * FROM b_uts_user  WHERE VALUE_ID =".$userId;

        $recordset4 = $connection->query($sql4);

        if ($record4 = $recordset4->fetch()) {
            $lastStrikeDate = $record4["UF_LAST_STRIKE_DATE"];
        }

        ///////////////////////////////////////////////////////////////////////////

        $arSelect = Array("ID", "NAME", "ACTIVE_FROM", "PROPERTY_GRATITUDE", "PROPERTY_CHECKED");
        $arFilter = Array("IBLOCK_ID" => 2, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y", "PROPERTY_USERS" => $userId);

        $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, false, $arSelect);

        $count = 0;

        $ids = array();

        while($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();

			//echo $lastStrikeDate."\n";

            if($arFields["PROPERTY_GRATITUDE_ENUM_ID"] == 80 
				&& (strtotime($arFields["ACTIVE_FROM"]) > strtotime($lastStrikeDate) || $lastStrikeDate == "")) {

				//echo " ".$userId ." ".$arFields["ACTIVE_FROM"]." ".$lastStrikeDate."\n";
                $count++;

                array_push($ids, $arFields["ID"]);

               // array_push($allStrikes, $arFields["ID"]);
            }

            if($count >=3 ) {

				$time =strtotime($arFields["ACTIVE_FROM"]);

				$connection->query("UPDATE  b_uts_user SET UF_LAST_STRIKE_DATE = FROM_UNIXTIME(".$time.") WHERE VALUE_ID =".$userId);

				//echo $time."\n";

				//echo $userId." ".$arFields["CREATED_DATE"];

                break;
            }
        }


        if(is_countable($ids) && count($ids) >=3) {

            $str = "Замечание за систематическое нарушение правил Компании \nВ соответствии с п2.5. Стандарта, замечание является эквивалентом 3-м предупреждениям за месяц.\nПолученные предупреждения:\n";

            for($i=0; $i < count($ids); $i++) {

                $sql3 = "SELECT * FROM b_uts_blog_post INNER JOIN b_blog_post ON b_uts_blog_post.VALUE_ID = b_blog_post.ID WHERE UF_GRATITUDE =".$ids[$i];

                $recordset3 = $connection->query($sql3);

                if ($record3 = $recordset3->fetch()) {
                    $str.=explode(" ", $record3["DATE_CREATE"])[0]." <a href='https://gkyw.ru/company/personal/user/".$record3["AUTHOR_ID"]."/blog/".$record3["ID"]."/'>".$record3["TITLE"]."</a>\n";
                }

            }

            $whom = array($userId);

            $createdBy = 3;

            $arBlog = CBlog::GetByOwnerID($createdBy);
            $idBlog = $arBlog["ID"];
			$setWarning($whom, $createdBy, $idBlog, $str);
        }
    }
}