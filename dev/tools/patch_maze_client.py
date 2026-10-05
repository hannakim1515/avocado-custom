from pathlib import Path
root=Path(__file__).resolve().parents[2]
def edit(file,old,new):
 p=root/file;s=p.read_text(encoding='utf-8');assert old in s,file;p.write_text(s.replace(old,new),encoding='utf-8')
edit('adm/dungeon_maze.php','<form method="post"><?php maze_admin_hidden(\'settings\'); ?>','''<form method="post" enctype="multipart/form-data"><?php maze_admin_hidden('settings'); ?>
<label>배경 이미지 URL <input type="url" name="background" value="<?php echo maze_admin_h($settings['background']); ?>"></label>
<label>배경 이미지 업로드 <input type="file" name="background_file" accept="image/png,image/jpeg,image/webp,image/gif"></label>
<p>배경은 대기실과 출발 시 스냅샷에 보관됩니다. 기존 진행 중 세션에는 적용되지 않습니다.</p>
<?php if(monster_ready()){ ?><label>공용 몬스터를 풀에 추가 <select id="maze-master"><option value="">선택</option><?php foreach(monster_rows('SELECT m.monster_id,m.name FROM `'.monster_table('monster_master').'` m JOIN `'.monster_table('monster_dungeon_profile').'` p ON p.monster_id=m.monster_id WHERE m.enabled=1') as $m) echo '<option value="'.(int)$m['monster_id'].'">'.maze_admin_h($m['name']).'</option>'; ?></select></label><button type="button" onclick="var t=document.querySelector('[name=monsters]'),id=Number(document.getElementById('maze-master').value);if(id){try{var p=JSON.parse(t.value);p.push({monster_id:id,enabled:true,boss:false,weight:1});t.value=JSON.stringify(p,null,2)}catch(e){alert('몬스터 풀 JSON을 먼저 확인하세요.')}}">일반 몬스터 추가</button><p>보스로 사용하려면 해당 항목의 boss를 true로 설정하세요.</p><?php } ?>''')
edit('adm/admin.menu994.php',"array('990330', '던전 관리'", "array('730100', '몬스터 관리', G5_ADMIN_URL.'/monster.php', ''),\n    array('730100', '던전 출현 설정', G5_ADMIN_URL.'/dungeon_pool.php', ''),\n    array('730100', '미궁 설정 / 세션', G5_ADMIN_URL.'/dungeon_maze.php', ''),\n    array('990330', '던전 관리'")
