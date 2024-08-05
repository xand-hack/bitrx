<?

use Bitrix\Crm\Service;
use \Bitrix\Main\Application;
$factory = Service\Container::getInstance()->getFactory(128);
CModule::IncludeModule("im");

$cDB = Application::getConnection();
$id = "{{ID}}";

$item  = $factory->getItem($id);
$stage = $item->getStageId();
$prevStage = $item->getPreviousStageId();
$tmpUser = "{{Кем передвинут}}";
$user = substr($tmpUser, 5);

if($stage == 'DT128_3:SUCCESS')
{
    $sql = $cDB->query("SELECT `GROUP_ID` FROM `b_user_group` WHERE `GROUP_ID` = 25 AND `USER_ID` = ".(int)$user);
    $result = $sql->fetch();
    if(empty($result))
    {
        $item->setStageId($prevStage);
        $item->save();

        #   Уведомление
        $users = array();
        $sql = $cDB->query("SELECT CONCAT(`u`.`LAST_NAME`, ' ', LEFT(`u`.`NAME`, 1),'.') AS `user`
                            FROM `b_user_group` AS `g`
                            LEFT JOIN `b_user` AS `u` ON `u`.`ID` = `g`.`USER_ID`
                            WHERE `g`.`GROUP_ID` = 25");
        $result = $sql->fetchAll();
        if(!empty($result))
        {
            foreach($result as $val)
            {
                $users[] = $val['user'];
            }

            if (\Bitrix\Main\Loader::includeModule('im'))
            {
                $im = new \CIMMessenger;
                $im->Add(array(
                    'TO_USER_ID' => $user,
                    'FROM_USER_ID' => 487,
                    'MESSAGE_TYPE' => 'S',
                    'NOTIFY_MODULE' => 'im',
                    'NOTIFY_MESSAGE' => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами[/b], а именно: [b]'.implode(', ', $users).'[/b]'
                ));
            }
        }
        else
        {
            if (\Bitrix\Main\Loader::includeModule('im'))
            {
                $im = new \CIMMessenger;
                $im->Add(array(
                    'TO_USER_ID' => $user,
                    'FROM_USER_ID' => 487,
                    'MESSAGE_TYPE' => 'S',
                    'NOTIFY_MODULE' => 'im',
                    'NOTIFY_MESSAGE' => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами[/b].'
                ));
            }
        }
    }
    else
    {
        if($stage == 'DT128_3:SUCCESS')
        {
            if(\Bitrix\Main\Loader::includeModule('im'))
            {
                $sql = $cDB->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_128|".(int)$  ."'");
                $iChat = $sql->fetch();
                $chat = new \CIMChat;
                $chat->AddUser($iChat['ID'], $user, null, true, true);
                $chat->AddMessage(array(
                    "TO_CHAT_ID" => $iChat['ID'],
                    "FROM_USER_ID" => $user,
                    "SYSTEM" => 'N',
                    "MESSAGE"  => 'Документы собраны'
                ));
            }
        }
        else
        {
            if(\Bitrix\Main\Loader::includeModule('im'))
            {
                $sql = $cDB->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_128|".(int)$id."'");
                $iChat = $sql->fetch();
                $chat = new \CIMChat;
                $chat->AddUser($iChat['ID'], $user, null, true, true);
                $chat->AddMessage(array(
                    "TO_CHAT_ID" => $iChat['ID'],
                    "FROM_USER_ID" => $user,
                    "SYSTEM" => 'N',
                    "MESSAGE"  => 'Документы не требуются'
                ));
            }
        }
    }
}