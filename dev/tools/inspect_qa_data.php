<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__,2);define('_GNUBOARD_',true);define('G5_PATH',$root);require $root.'/data/dbconfig.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli(G5_MYSQL_HOST,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB);$db->set_charset('utf8mb4');$g5['connect_db']=$db;
require $root.'/lib/system_installer.lib.php';
$queries=array(
 'config'=>"SELECT cf_admin,cf_theme,cf_use_map,cf_dungeon_open,cf_dungeon_map,cf_dungeon_count,cf_dungeon_time,cf_dungeon_reset,cf_skill_count,cf_skill_count_max,cf_character_count,cf_status_select_type FROM ".system_install_ident(system_install_table('config')),
 'characters'=>"SELECT ch_id,ch_name,mb_id,ch_type,ch_state,ma_id,ch_skill_slot FROM ".system_install_ident(system_install_table('character')),
 'members'=>"SELECT mb_id,ch_id,mb_level FROM ".system_install_ident(system_install_table('member')),
 'stats'=>'SELECT * FROM '.system_install_ident(system_install_table('status')),
 'character_stats'=>'SELECT * FROM '.system_install_ident(system_install_table('status_character')),
 'codes'=>'SELECT * FROM '.system_install_ident(system_install_table('status_extra')),
 'skills'=>'SELECT sk_id,sk_name,sk_type,sk_function,sk_target,sk_cate,sk_dungeon_target_mode FROM '.system_install_ident(system_install_table('skill')),
 'items'=>'SELECT it_id,it_name,it_type,it_5,it_value,it_use_battle_able FROM '.system_install_ident(system_install_table('item')),
 'inventory'=>'SELECT ch_id,it_id,COUNT(*) n FROM '.system_install_ident(system_install_table('inventory')).' GROUP BY ch_id,it_id',
 'monsters'=>'SELECT monster_id,name,enabled,raid_id,profile FROM '.system_install_ident(system_install_table('monster_master')),
 'monster_skills'=>"SELECT sk_id,sk_name,sk_info FROM ".system_install_ident(system_install_table('k_battle_skill'))." WHERE unit_type='mo'",
 'dungeons'=>'SELECT dg_id,dg_title,dg_use,dg_count,ma_ids,dg_settings FROM '.system_install_ident(system_install_table('dungeon')),
 'sessions'=>'SELECT ds_id,dg_id,ds_state,phase,battle_id FROM '.system_install_ident(system_install_table('dungeon_state')),
 'participants'=>'SELECT ds_id,ch_id,state,settled FROM '.system_install_ident(system_install_table('dungeon_maze_member')),
 'raids'=>'SELECT ra_id,ra_title,ra_state,ra_limit,ra_limit_now FROM '.system_install_ident(system_install_table('k_battle_realtime')),
 'raid_lists'=>'SELECT * FROM '.system_install_ident(system_install_table('k_raid_list')),
 'maps'=>'SELECT ma_id,ma_name,ma_parent,ma_use,ma_use_dungeon,ma_content FROM '.system_install_ident(system_install_table('newmap_config')),
 'boards'=>"SELECT bo_table,bo_subject,bo_skin,bo_1_subj,bo_1,bo_2 FROM ".system_install_ident(G5_TABLE_PREFIX.'board'),
 'unified_config'=>'SELECT * FROM '.system_install_ident(system_install_table('unified_combat_config')),
 'stat_mapping'=>'SELECT * FROM '.system_install_ident(system_install_table('unified_combat_stat_map')),
 'battle_config'=>'SELECT * FROM '.system_install_ident(system_install_table('battle_config'))
);
$out=array();foreach($queries as $key=>$sql){try{$out[$key]=system_install_rows($sql);}catch(Throwable $e){$out[$key]=array('error'=>$e->getMessage());}}
$out['columns']=array();foreach(array('character','item','inventory','skill','skill_level','skill_has','k_battle_realtime','k_raid_list','newmap_config','newmap_event','newmap_npc_script','newmap_npc_choice','npc_setting','shop','item_recepi') as $suffix){$schema=system_install_schema(system_install_table($suffix));$out['columns'][$suffix]=$schema?array_keys($schema['columns']):array();}
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),PHP_EOL;
