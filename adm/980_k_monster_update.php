<?php
$sub_menu='980700';
include_once './_common.php';
require_once __DIR__.'/dungeon_admin.lib.php';
da_monster_auth('w');
check_token();
alert('몬스터는 공용 몬스터 관리 화면에서 저장해 주세요.', './monster.php');
