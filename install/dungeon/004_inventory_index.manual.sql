-- MANUAL ONLY. Run after inventory engine/schema inspection and backup.
-- Change the table name for deployments with a different prefix.
SET @maze_inventory_table = 'avo_inventory';
SET @maze_allow_index = 0;
SET @maze_owner_index = (SELECT COUNT(*) FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=@maze_inventory_table
 AND SEQ_IN_INDEX=1 AND COLUMN_NAME='ch_id' AND SUB_PART IS NULL);
SET @maze_index_name_used = (SELECT COUNT(*) FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=@maze_inventory_table AND INDEX_NAME='maze_owner_rows');
SET @maze_index_engine = (SELECT ENGINE FROM information_schema.TABLES
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=@maze_inventory_table);
SET @maze_index_sql = CASE
 WHEN @maze_owner_index>0 THEN 'SELECT ''Existing owner index retained'' AS migration_status'
 WHEN @maze_allow_index<>1 THEN 'SELECT ''Inspection only: index opt-in disabled'' AS migration_status'
 WHEN @maze_index_engine='InnoDB' AND @maze_index_name_used=0 AND @maze_inventory_table REGEXP '^[A-Za-z0-9_]+$'
 THEN CONCAT('ALTER TABLE `',@maze_inventory_table,'` ADD INDEX maze_owner_rows (ch_id,in_id)')
 ELSE 'SELECT ''Blocked: inspect engine, table and index names'' AS migration_status' END;
PREPARE maze_inventory_index FROM @maze_index_sql;
EXECUTE maze_inventory_index;
DEALLOCATE PREPARE maze_inventory_index;
