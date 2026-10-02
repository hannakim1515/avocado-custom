-- MANUAL ONLY. Replace avo_ if necessary. Apply after inventory preflight and
-- 001_inventory_engine.manual.sql. No existing table is altered here.
CREATE TABLE IF NOT EXISTS avo_inventory_guard (
  ch_id int NOT NULL,
  active_journal bigint unsigned DEFAULT NULL,
  PRIMARY KEY (ch_id),
  KEY active_journal (active_journal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS avo_inventory_journal (
  journal_id bigint unsigned NOT NULL AUTO_INCREMENT,
  ch_id int NOT NULL,
  mb_id varchar(255) NOT NULL,
  operation varchar(100) NOT NULL,
  state varchar(16) NOT NULL,
  request_key varchar(64) NOT NULL,
  originals longtext NOT NULL,
  intent longtext NOT NULL,
  result_note text NOT NULL,
  created_at datetime NOT NULL,
  finished_at datetime DEFAULT NULL,
  PRIMARY KEY (journal_id),
  UNIQUE KEY request_key (request_key),
  KEY owner_state (ch_id,state),
  KEY review_queue (state,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
