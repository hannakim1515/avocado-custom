<?php
// Pure map generation: no DB connection or fixture data changes.
define('_GNUBOARD_', true);
require dirname(__DIR__, 2).'/extend/dungeon_maze.lib.php';
foreach (array(3, 9, 12) as $count) foreach (array(false, true) as $boss) {
    $max = (int)floor(($count - ($boss ? 2 : 1)) / 2);
    foreach (array_unique(array(0, $max)) as $branches) {
        $settings = array('rooms_min'=>$count,'rooms_max'=>$count,'branches_min'=>$branches,'branches_max'=>$branches,
            'boss'=>$boss,'dead_end_percent'=>100,'weights'=>array('EMPTY'=>1),'monsters'=>array(),'events'=>array(),'encounter_percent'=>0);
        for ($i=0; $i<10; $i++) {
            $rooms=maze_generate($settings); $exit=null; $boss_room=null; $children=array();
            foreach ($rooms as $room) { if ($room['is_exit']) $exit=$room; if ($room['is_boss']) $boss_room=$room; if ($room['parent_id']) $children[$room['parent_id']][]= $room['room_id']; }
            $branch_count=count(array_filter($children,function($ids){return count($ids)>1;}));
            if (count($rooms)!==$count || $branch_count!==$branches || !$exit || isset($children[$exit['room_id']]) || $exit['depth']!==max(array_column($rooms,'depth')) || $exit['depth']<(int)ceil(($count-1)/2)) throw new RuntimeException('Exit depth or room/branch count mismatch');
            if ($boss && (!$boss_room || $exit['parent_id']!==$boss_room['room_id'] || $boss_room['depth']<1)) throw new RuntimeException('Boss must be immediately before exit');
        }
    }
}
echo "PASS deepest exit, minimum route depth, room/branch counts, boss before exit\n";
