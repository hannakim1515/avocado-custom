-- MANUAL ONLY. Replace avo_ with the actual prefix. No existing core engine changes.
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_meta (
  singleton tinyint NOT NULL PRIMARY KEY,
  schema_version int NOT NULL,
  action_timeout int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_config (
  dg_id int NOT NULL PRIMARY KEY,
  settings longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_session (
  ds_id int NOT NULL PRIMARY KEY,
  dg_id int NOT NULL,
  phase varchar(16) NOT NULL DEFAULT 'WAITING',
  version bigint NOT NULL DEFAULT 0,
  room_id int NOT NULL DEFAULT 0,
  battle_id bigint NOT NULL DEFAULT 0,
  snapshot longtext NOT NULL,
  expires_at datetime DEFAULT NULL,
  started_at datetime DEFAULT NULL,
  finished_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  KEY phase_expiry (phase,expires_at),
  KEY dungeon (dg_id,ds_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_member (
  dm_id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ds_id int NOT NULL,
  ch_id int NOT NULL,
  mb_id varchar(255) NOT NULL,
  name varchar(255) NOT NULL,
  state varchar(16) NOT NULL DEFAULT 'ACTIVE',
  ready tinyint NOT NULL DEFAULT 0,
  hp int NOT NULL DEFAULT 1,
  max_hp int NOT NULL DEFAULT 1,
  stats longtext NOT NULL,
  skills longtext NOT NULL,
  effects longtext NOT NULL,
  settled tinyint NOT NULL DEFAULT 0,
  reward_eligible tinyint NOT NULL DEFAULT 0,
  joined_at datetime NOT NULL,
  exited_at datetime DEFAULT NULL,
  UNIQUE KEY membership (ds_id,ch_id),
  KEY character_active (ch_id,state),
  KEY character_history (ch_id,dm_id),
  KEY daily_settled (ch_id,settled,exited_at),
  KEY party (ds_id,state,dm_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_room (
  ds_id int NOT NULL,
  room_id int NOT NULL,
  parent_id int NOT NULL,
  direction varchar(12) NOT NULL,
  depth int NOT NULL,
  kind varchar(16) NOT NULL,
  is_exit tinyint NOT NULL DEFAULT 0,
  is_boss tinyint NOT NULL DEFAULT 0,
  visited tinyint NOT NULL DEFAULT 0,
  resolved tinyint NOT NULL DEFAULT 0,
  encounter_done tinyint NOT NULL DEFAULT 0,
  payload longtext NOT NULL,
  result_text text NOT NULL,
  PRIMARY KEY (ds_id,room_id),
  KEY parent (ds_id,parent_id),
  KEY visited (ds_id,visited,room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_battle (
  battle_id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ds_id int NOT NULL,
  room_id int NOT NULL,
  state varchar(16) NOT NULL DEFAULT 'ACTIVE',
  turn_no int NOT NULL DEFAULT 1,
  deadline_at datetime DEFAULT NULL,
  monster longtext NOT NULL,
  hp int NOT NULL,
  weak_count int NOT NULL DEFAULT 0,
  weak_turn int NOT NULL DEFAULT 0,
  KEY session (ds_id,battle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_action (
  battle_id bigint NOT NULL,
  turn_no int NOT NULL,
  dm_id bigint NOT NULL,
  action varchar(16) NOT NULL DEFAULT '',
  target_id bigint NOT NULL DEFAULT 0,
  reference_id bigint NOT NULL DEFAULT 0,
  escape_vote tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (battle_id,turn_no,dm_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_inventory (
  inventory_id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ds_id int NOT NULL,
  dm_id bigint NOT NULL,
  ch_id int NOT NULL,
  it_id int NOT NULL,
  origin varchar(12) NOT NULL,
  original_id int DEFAULT NULL,
  active_original_id int DEFAULT NULL,
  original_row longtext NOT NULL,
  item_snapshot longtext NOT NULL,
  consumed tinyint NOT NULL DEFAULT 0,
  restored tinyint NOT NULL DEFAULT 0,
  UNIQUE KEY active_original (active_original_id),
  KEY owner_items (ds_id,dm_id,consumed,restored),
  KEY item_reference (it_id,restored)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_vote (
  ds_id int NOT NULL,
  target_id bigint NOT NULL,
  voter_id bigint NOT NULL,
  PRIMARY KEY (ds_id,target_id,voter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_log (
  log_id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ds_id int NOT NULL,
  dm_id bigint NOT NULL DEFAULT 0,
  kind varchar(12) NOT NULL,
  message text NOT NULL,
  created_at datetime NOT NULL,
  KEY session_log (ds_id,log_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_reward (
  ds_id int NOT NULL,
  dm_id bigint NOT NULL,
  reward_type varchar(16) NOT NULL,
  state varchar(16) NOT NULL DEFAULT 'PENDING',
  mb_id varchar(255) NOT NULL,
  amount int NOT NULL,
  completed_at datetime DEFAULT NULL,
  PRIMARY KEY (ds_id,dm_id,reward_type),
  KEY pending (state,ds_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_status (
  status_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL,
  description text NOT NULL,
  duration int NOT NULL,
  enabled tinyint NOT NULL DEFAULT 1,
  components longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avo_dungeon_maze_item_effect (
  it_id int NOT NULL PRIMARY KEY,
  kind varchar(16) NOT NULL,
  status_id int NOT NULL DEFAULT 0,
  value int NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- The runtime also checks all tables/columns/indexes; this row alone is insufficient.
INSERT INTO avo_dungeon_maze_meta (singleton,schema_version,action_timeout)
VALUES (1,1,NULL) ON DUPLICATE KEY UPDATE singleton=VALUES(singleton);
