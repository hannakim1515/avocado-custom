<?php
if (!defined('_GNUBOARD_')) exit;
// Dependency order is intentional. Each schema file is a published snapshot.
return array(
    'base'=>array('name'=>'기본 확장 / 스탯 / 스킬', 'version'=>'1.0.0', 'deps'=>array()),
    'map'=>array('name'=>'MAP / NPC', 'version'=>'1.0.0', 'deps'=>array('base')),
    'field'=>array('name'=>'필드 탐색 / 낚시', 'version'=>'1.0.0', 'deps'=>array('base')),
    'room'=>array('name'=>'캐릭터 방', 'version'=>'1.0.0', 'deps'=>array('base')),
    'quest'=>array('name'=>'퀘스트', 'version'=>'1.0.0', 'deps'=>array('base')),
    'dungeon'=>array('name'=>'던전 / 방 탐험', 'version'=>'1.2.0', 'deps'=>array('base')),
    'k_battle'=>array('name'=>'K 전투 / 장비', 'version'=>'2.1.0', 'deps'=>array('base'),
        'migrations'=>array('2.0.0'=>'k_battle/migrations/2.0.0.php', '2.0.2'=>null, '2.1.0'=>null)),
    'unified'=>array('name'=>'통합 전투', 'version'=>'1.0.0', 'deps'=>array('base','k_battle')),
    'monsters'=>array('name'=>'몬스터 관리 / 전투 기록', 'version'=>'1.8.0', 'deps'=>array('dungeon','k_battle')),
    'realtime'=>array('name'=>'실시간 레이드', 'version'=>'1.0.0', 'deps'=>array('unified','k_battle')),
    'inventory_engine'=>array('name'=>'Inventory 준비', 'version'=>'1.0.0', 'deps'=>array('base'), 'manual'=>true),
    'inventory_boundary'=>array('name'=>'인벤토리 안전 처리 / 조회 인덱스', 'version'=>'1.0.0', 'deps'=>array('inventory_engine')),
    'labyrinth'=>array('name'=>'던전 탐험 / 정산', 'version'=>'1.7.0', 'deps'=>array('inventory_boundary','dungeon','unified','monsters'))
);
