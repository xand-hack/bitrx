<?php


CModule::IncludeModule("Main");
CModule::IncludeModule('timeman');
CModule::IncludeModule('im');

use Bitrix\Main\Config\Option;

function parseDates($datesString):array {
    $currentYear = date("Y");
    preg_match_all("/((?P<day>[1-3]?\d{1}).(?P<month>[01]?\d{1}),?)/", $datesString, $matches, PREG_SET_ORDER);

    return array_map(function ($match) use ($currentYear) {
        return strtotime("$currentYear-{$match["month"]}-{$match["day"]}");
    }, $matches);
}

const DEFAULT_WEEK_HOLIDAYS = "SA|SU";

$yearHolidays = Option::get("calendar", "year_holidays", "");
$weekHolidays = Option::get("calendar", "week_holidays", DEFAULT_WEEK_HOLIDAYS);

$currentWeek = substr(strtoupper(date("D")), 0, 2);

$todayTimestamp = strtotime(date("Y-m-d"));

$todayIsWorkDay = !in_array($currentWeek, explode("|", $weekHolidays));

if($todayIsWorkDay) {
 $todayIsWorkDay = !in_array($todayTimestamp, parseDates($yearHolidays));
}

if($todayIsWorkDay) {

$dependencyManager = Bitrix\Timeman\Service\DependencyManager::getInstance();

    $theDate    = new DateTime();
    $stringDate = $theDate->format('Y-m-d');

    $connection = Bitrix\Main\Application::getConnection(); 

    $sql2 = "SELECT b_user.ID AS u_id FROM b_user INNER JOIN b_uts_user ON b_user.ID = b_uts_user.VALUE_ID WHERE b_user.ID NOT IN (SELECT b_user.ID FROM b_timeman_entries INNER JOIN b_user 
    ON b_timeman_entries.USER_ID = b_user.ID 
    WHERE b_user.ACTIVE = 'Y' 
    AND b_timeman_entries.DATE_START > '{$stringDate} 00:00:00' AND b_timeman_entries.DATE_START < '$stringDate 23:59:59') AND b_uts_user.UF_DEPARTMENT IS NOT NULL AND b_uts_user.UF_DEPARTMENT != ''";

    $recordset2 = $connection->query($sql2);

    while ($record2 = $recordset2->fetch()) { 

        if (!empty(unserialize($record2['UF_DEPARTMENT']))){

            $TimemanUser = new CTimeManUser($record2["u_id"]);

            $userSchedules = $dependencyManager->getScheduleProvider()->findSchedulesByUserId($record2["u_id"]);//$TimemanUser->GetCurrentInfo();

            foreach ($userSchedules as $userSchedule) {

                if($userSchedule->getId() == 1 || $userSchedule->getId() == 2 || $userSchedule->getId() == 4) {

                    $arSelect = Array("ID");
                    $arFilter = array("IBLOCK_ID"=>1, "PROPERTY_USER" => $record2["u_id"],
                    "<=DATE_ACTIVE_FROM" => array(false, ConvertTimeStamp(false, "SHORT")),
                    ">=DATE_ACTIVE_TO"   => array(false, ConvertTimeStamp(false, "SHORT")),
                    );
                    $res = CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);

                    if(!$ob = $res->GetNextElement()) {

                    // if($record2["u_id"] == 5) {

                            $el = new CIBlockElement;

                            $PROP = array();
                            $PROP[1] = $record2["u_id"];
                            $PROP[4] = 6;
                            $name = "Прогул";

                            $arLoadProductArray = Array(      
                                "IBLOCK_ID"      => 1,
                                "PROPERTY_VALUES"=> $PROP,
                                "NAME" => $name,
                                "ACTIVE_FROM" => ConvertDateTime($theDate->format('d.m.Y'), CSite::GetDateFormat("SHORT")),
                                "ACTIVE_TO" => ConvertDateTime($theDate->format('d.m.Y'), CSite::GetDateFormat("SHORT")),
                            );
                            
                        if($PRODUCT_ID = $el->Add($arLoadProductArray)) {
                                $rsUser = CUser::GetByID($record2["u_id"]);
                                $arUser = $rsUser->Fetch();

                                $messToChat = "[USER=".$record2["u_id"]."]".$arUser["NAME"]." ".$arUser["LAST_NAME"]."[/USER] получает прогул";

                                $ar = Array(
                                    "TO_CHAT_ID" => 2597, 
                                    "FROM_USER_ID" => 487, 
                                    "MESSAGE" => $messToChat, 
                                );

                                CIMChat::AddMessage($ar);

                            } else {

                            }
                    }
                    }

                    break;
            //   }

            }
        }

    }

}