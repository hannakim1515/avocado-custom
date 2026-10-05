"""Published additive monster/instance contract; run explicitly during development."""
import json
from pathlib import Path
root = Path(__file__).resolve().parents[2]
I = "int(11) NOT NULL DEFAULT '0'"
V = "varchar(255) NOT NULL DEFAULT ''"
def table(columns, key, indexes=None):
    return dict(columns=columns, indexes={'PRIMARY':dict(columns=key,unique=True), **(indexes or {})}, create=True, engine='InnoDB', charset='utf8mb4', require_engine=True, source='lib/monster.lib.php')
schema = {
 'dungeon_skill_target':table(dict(sk_id=I,target_mode="varchar(12) NOT NULL DEFAULT 'SINGLE'"),['sk_id']),
 'monster_master':table(dict(monster_id='int(11) NOT NULL AUTO_INCREMENT',name=V,image='text NULL',description='text NULL',enabled="tinyint(1) NOT NULL DEFAULT '1'",raid_id='int(11) NULL'),['monster_id'],{'raid_id':dict(columns=['raid_id'],unique=True)}),
 'monster_dungeon_profile':table(dict(monster_id=I,profile='longtext NOT NULL'),['monster_id']),
 'dungeon_monster_config':table(dict(dg_id=I,min_count="int(11) NOT NULL DEFAULT '1'",max_count="int(11) NOT NULL DEFAULT '1'"),['dg_id']),
 'dungeon_monster_pool':table(dict(dg_id=I,monster_id=I,weight="int(11) NOT NULL DEFAULT '1'",enabled="tinyint(1) NOT NULL DEFAULT '1'",is_boss=I),['dg_id','monster_id']),
 'dungeon_state_monster':table(dict(instance_id='int(11) NOT NULL AUTO_INCREMENT',ds_id=I,monster_id=I,slot_no=I,is_boss=I,hp=I,max_hp=I,state="varchar(12) NOT NULL DEFAULT 'ALIVE'",weak_count=I,weak_turn=I,snapshot='longtext NOT NULL'),['instance_id'],{'session_slot':dict(columns=['ds_id','slot_no'],unique=True)}),
 'dungeon_combat_event':table(dict(event_id='bigint(20) NOT NULL AUTO_INCREMENT',source="varchar(12) NOT NULL DEFAULT 'dungeon'",ds_id=I,turn_no=I,actor=V,target=V,action=V,amount=I,result="varchar(32) NOT NULL DEFAULT ''",created_at='datetime NOT NULL'),['event_id'],{'session_event':dict(columns=['source','ds_id','event_id'],unique=False)}),
 'dungeon_log':dict(columns={'monster_instance_id':I},indexes={},create=False,source='lib/monster.lib.php')
}
schema['monster_runtime']=table(dict(battle_key="varchar(191) NOT NULL DEFAULT ''",unit_key="varchar(191) NOT NULL DEFAULT ''",state='longtext NOT NULL'),['battle_key','unit_key'])
schema['monster_runtime']['source']='lib/monster_shared.lib.php'
out=root/'install/system/monsters/schema-1.1.0.json'
out.parent.mkdir(parents=True,exist_ok=True)
out.write_text(json.dumps(schema,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
