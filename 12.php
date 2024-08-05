<?php
CModule::IncludeModule("iblock"); // Модуль для работы с инфоблоками
CModule::IncludeModule("crm"); // Модуль для работы с CRM (сделки, лиды и т.д.)
CModule::IncludeModule("tasks"); // модуль для работы с задачами

$idDeal = 52180; // ID сделки, с которой работаем

$str = ""; // Строка,в которую будет записываться выводимая информация (пока что пустая)

$dbRes = CCrmDeal::GetList(array(), array('ID' => $idDeal )); // Выбираем сделку с ID = 52216

if($arRes = $dbRes->Fetch()) { // Получаем ее поля (записываются в $arRes)

    $str = "Название сделки: ".$arRes["TITLE"]."\nСтоимость сделки: ".$arRes["OPPORUNITY"];

    $rsUser = CUser::GetByID($arRes["ASSIGNED_BY"]); // Получаем информацию по ответственному за сделку (информацию о пользователе)
    $arUser = $rsUser->Fetch(); // Получаем поля из запроса о пользователе

    $str.="Ответственный: ".$arUser["NAME"]." ".$arUser["LAST_NAME"]."\n"; // Добавляем информацию об ответственном

    $res = CTasks::GetList(Array(), Array( 'UF_CRM_TASK' => array('D_'.$idDeal), 'CHECK_PERMISSIONS' => 'N', 'ONLY_ROOT_TASKS' => 'N')); // Делаем выборку всех задач, привязанных к сделке (поле UF_CRM_TASK, префикс 'D' означает тип crm, в данном случае Deal - сделка)
    
    while ($arTask = $res->GetNext()) // Цикл по выбранным задачам
    {
        $str.= "Задача: ".$arTask["TITLE"].". Платежи:\n"; // Добавляем информацию по задаче

        $arSelect = array("ID", "NAME", "PROPERTY_146", "PROPERTY_147", "PROPERTY_160", "PROPERTY_150", "PROPERTY_157"); // Делаем выборку платежей по задаче
        $arFilter = array("IBLOCK_ID" =>28, "PROPERTY_157" => $arTask["ID"]);
    
        $resPays = CIBlockElement::GetList(array(), $arFilter, $arSelect);
        $minim;
        $idMin;

        while ($ob = $resPays->GetNextElement()) { // Просматриваем каждый платеж в выборке
            
            $arFieldsPays = $ob->GetFields(); // Получаем поля из записи о платеже
            $totalSum += $arFieldsPays["PROPERTY_147_VALUE"];
        
            
            if($arFieldsPays["PROPERTY_147_VALUE"] <= $minim || !isset($minim)){
                $idMin= $arFieldsPays["ID"];
                $minim = $arFieldsPays["PROPERTY_147_VALUE"];
                echo "__".$idMin."__";
            }

            $str.= $arFieldsPays["ID"]." Дата: ".$arFieldsPays["PROPERTY_146_VALUE"]."; ИНН контрагента:".$arFieldsPays["PROPERTY_150_VALUE"]."; Наличный платёж: ".$arFieldsPays["PROPERTY_160_VALUE"]."; Сумма: ".$arFieldsPays["PROPERTY_147_VALUE"].". "."\n";

        }

        $str.="\n";

    }

}

echo $str;
echo "Сумма платежей: " . $totalSum;
echo " пЛАТЕЖ с минимальной суммой ".$idMin;