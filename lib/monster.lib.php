<?php
if (!defined('_GNUBOARD_')) exit;

function monster_table($suffix) { return G5_TABLE_PREFIX.$suffix; }
function monster_quote($value) { return "'".sql_escape_string((string)$value)."'"; }
function monster_rows($sql) {
    $result = sql_query($sql); $rows = array();
    while ($row = sql_fetch_array($result)) $rows[] = $row;
    return $rows;
}
/** Legacy K readers use this view; all definition values live on monster_master. */
function monster_raid_view_sql() {
    $master='`'.monster_table('monster_master').'`';
    $view='`'.monster_table('k_battle_monster').'`';
    $json=function($path,$fallback="'0'") { return 'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(profile, \''.str_replace("'", "''", $path).'\')),'.$fallback.')'; };
    $columns=array(
        'raid_id AS mo_id', 'name AS mo_name', "COALESCE(image,'') AS mo_thumb",
        $json('$.k_description',"COALESCE(description,'')").' AS mo_1',
        'CAST('.$json('$.dg_mon_hp').' AS SIGNED) AS mo_hp',
        'CAST('.$json('$.k_mp',$json('$.shared.mp')).' AS SIGNED) AS mo_mp'
    );
    for($i=0;$i<10;$i++)$columns[]='CAST('.$json('$.k_stats['.$i.']').' AS SIGNED) AS st_'.($i+1);
    $columns[]=$json('$.k_raid_type',"'realtime'").' AS raid_type';
    $columns[]='CAST('.$json('$.k_pattern',"'1'").' AS SIGNED) AS mo_pattern';
    $columns[]='CAST('.$json('$.k_default_act',"'1'").' AS SIGNED) AS mo_default_act';
    return 'CREATE SQL SECURITY INVOKER VIEW '.$view.' AS SELECT '.implode(',',$columns).' FROM '.$master.' WHERE raid_id IS NOT NULL';
}
function monster_raid_catalog_ready() {
    $row=sql_fetch('SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='.monster_quote(monster_table('k_battle_monster')),false);
    return $row && $row['TABLE_TYPE']==='VIEW';
}
function monster_dungeon_legacy_fields() {
    $legacy=json_decode(file_get_contents(__DIR__.'/../install/system/dungeon/schema-1.0.0.json'),true)['dungeon']['columns'];
    return array_filter($legacy,function($ddl,$field) {
        return strpos($field,'dg_mon_')===0 || $field==='dg_defence' || strpos($field,'dg_d_attack_')===0 || strpos($field,'dg_w_attack_')===0 || strpos($field,'dg_s1_attack_')===0 || strpos($field,'dg_s2_attack_')===0 || strpos($field,'dg_strong_')===0 || strpos($field,'dg_weak_')===0;
    },ARRAY_FILTER_USE_BOTH);
}
/** Legacy dungeon combat field names are projected from one chosen pool monster. */
function monster_dungeon_read_view_sql() {
    $prefix=G5_TABLE_PREFIX;
    $d='`'.$prefix.'dungeon`';
    $p='`'.$prefix.'dungeon_monster_pool`';
    $m='`'.$prefix.'monster_master`';
    $view='`'.$prefix.'dungeon_monster_read`';
    $legacy=monster_dungeon_legacy_fields();
    $columns=array('d.*',"COALESCE(m.name,'') AS dg_mon_name","COALESCE(m.image,'') AS dg_mon_img","COALESCE(d.dg_description,'') AS dg_mon_descript");
    foreach($legacy as $field=>$ddl) {
        if(in_array($field,array('dg_mon_name','dg_mon_img','dg_mon_descript'),true))continue;
        $value='JSON_UNQUOTE(JSON_EXTRACT(m.profile,\'$.'.$field.'\'))';
        $columns[]=preg_match('/^int\(/',$ddl)?'CAST(COALESCE('.$value.',\'0\') AS SIGNED) AS '.$field:'COALESCE('.$value.',\'\') AS '.$field;
    }
    return 'CREATE SQL SECURITY INVOKER VIEW '.$view.' AS SELECT '.implode(',',$columns).' FROM '.$d.' d LEFT JOIN '.$p.' p ON p.dg_id=d.dg_id AND p.monster_id=(SELECT p2.monster_id FROM '.$p.' p2 JOIN '.$m.' m2 ON m2.monster_id=p2.monster_id WHERE p2.dg_id=d.dg_id AND p2.enabled=1 AND m2.enabled=1 ORDER BY p2.is_boss DESC,p2.monster_id LIMIT 1) LEFT JOIN '.$m.' m ON m.monster_id=p.monster_id';
}
function monster_dungeon_read_table() { return monster_table('dungeon_monster_read'); }
function monster_dungeon_read_ready() {
    $row=sql_fetch('SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='.monster_quote(monster_dungeon_read_table()),false);
    return $row && $row['TABLE_TYPE']==='VIEW';
}
function monster_raid_next_id($preferred=0) {
    $table='`'.monster_table('monster_master').'`';
    $preferred=(int)$preferred;
    if($preferred>0 && !sql_fetch('SELECT monster_id FROM '.$table.' WHERE raid_id='.$preferred))return $preferred;
    $row=sql_fetch('SELECT GREATEST(COALESCE(MAX(raid_id),0),COALESCE(MAX(monster_id),0))+1 AS next_id FROM '.$table);
    return (int)$row['next_id'];
}
function monster_ready() {
    static $ready;
    if ($ready === null) {
        $ready = (bool)sql_fetch('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='.monster_quote(monster_table('dungeon_monster_pool')), false)
            && (bool)sql_fetch('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='.monster_quote(monster_table('monster_master'))." AND COLUMN_NAME='profile'", false);
    }
    return $ready;
}
function monster_profile($id, $enabled = true) {
    $row = sql_fetch('SELECT * FROM `'.monster_table('monster_master').'` WHERE monster_id='.(int)$id.($enabled?' AND enabled=1':''));
    if (!$row) throw new RuntimeException('사용 가능한 던전 몬스터 프로필이 없습니다.');
    $profile = json_decode($row['profile'], true);
    if (!is_array($profile) || empty($profile['dg_mon_hp'])) throw new RuntimeException('몬스터 전투 수치를 확인하세요.');
    $profile=array_replace($profile, array('dg_mon_name'=>$row['name'],'dg_mon_img'=>$row['image'],'dg_mon_descript'=>$row['description'],'monster_id'=>(int)$row['monster_id']));
    if(!empty($profile['shared'])) {require_once __DIR__.'/monster_shared.lib.php';$profile['shared']=monster_shared_compile($profile);}
    return $profile;
}
function monster_sync_raid($id) {
    // The old synchronization entry point is retained for extensions. K reads
    // the same master row through a view, so there is no second catalog to sync.
    return monster_raid_catalog_ready();
}
function combat_event($source,$ds_id,$turn,$actor,$target,$action,$amount,$result) {
    if (!monster_ready()) return;
    $values=array($source,(int)$ds_id,(int)$turn,$actor,$target,$action,(int)$amount,$result,date('Y-m-d H:i:s'));
    sql_query('INSERT INTO `'.monster_table('dungeon_combat_event').'` (source,ds_id,turn_no,actor,target,action,amount,result,created_at) VALUES ('.implode(',',array_map('monster_quote',$values)).')');
}
function monster_image_input($url_key,$file_key) {
    $url=trim($_POST[$url_key]??'');
    if ($url!=='' && (!filter_var($url,FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($url,PHP_URL_SCHEME)),array('http','https'),true))) throw new RuntimeException('이미지는 http/https URL을 입력하세요.');
    if (!empty($_FILES[$file_key]['name'])) {
        $file=$_FILES[$file_key]; $image=@getimagesize($file['tmp_name']);
        $types=array(IMAGETYPE_GIF=>'gif',IMAGETYPE_JPEG=>'jpg',IMAGETYPE_PNG=>'png',IMAGETYPE_WEBP=>'webp');
        if ($file['error'] || $file['size']>8*1024*1024 || !$image || !isset($types[$image[2]])) throw new RuntimeException('8MB 이하의 PNG/JPG/GIF/WebP 이미지를 선택하세요.');
        $directory=G5_DATA_PATH.'/monster'; if (!is_dir($directory)) mkdir($directory,0755,true);
        $name=bin2hex(random_bytes(16)).'.'.$types[$image[2]];
        if (!move_uploaded_file($file['tmp_name'],$directory.'/'.$name)) throw new RuntimeException('이미지를 저장할 수 없습니다.');
        $url=G5_DATA_URL.'/monster/'.$name;
    }
    return $url;
}
function monster_field_label($field) {
    $labels=array('dg_mon_hp'=>'최대 HP','dg_defence'=>'방어','dg_strong_code'=>'강점 코드','dg_strong_value'=>'강점 배율','dg_weak_code'=>'약점 코드','dg_weak_value'=>'약점 배율','dg_weak_effect_count'=>'그로기 발동 횟수','dg_weak_effect_turn'=>'그로기 지속 횟수');
    if (isset($labels[$field])) return $labels[$field];
    foreach(array('d'=>'일반','w'=>'전체','s1'=>'조건 1','s2'=>'조건 2') as $key=>$name) {
        $prefix='dg_'.$key.'_attack_';
        if (strpos($field,$prefix)===0) { $suffix=substr($field,strlen($prefix)); $names=array('min'=>'최소 피해','max'=>'최대 피해','turn'=>'발동 간격(턴)','count'=>'대상 수','comment'=>'행동 문구','st_id'=>'조건 스탯 ID','st_value'=>'조건 수치','type'=>'조건 방향 (이상/이하)'); return $name.' · '.($names[$suffix]??$suffix); }
    }
    return $field;
}
