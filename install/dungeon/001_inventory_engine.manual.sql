-- MANUAL ONLY. The application must never execute this file.
-- Run inventory_preflight.php first and review the complete output.
-- Back up inventory, verify restore, pause inventory writers, and check free
-- disk space before opting in. Engine conversion can rebuild/lock the table.
-- Keep the original SQL mode; investigate invalid defaults/data if ALTER fails.
-- No columns, indexes, IDs, character set, member or point tables are changed.
-- Running this migration does NOT make the labyrinth ready to start.

-- Change this if the deployment uses another prefix/table name.
SET @lab_inventory_table = 'avo_inventory';
-- Deliberately disabled. Set to 1 only after the manual checks above.
SET @lab_allow_inventory_conversion = 0;

SELECT TABLE_NAME, ENGINE, AUTO_INCREMENT, TABLE_COLLATION, TABLE_ROWS,
       DATA_LENGTH, INDEX_LENGTH
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @lab_inventory_table;

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @lab_inventory_table
ORDER BY ORDINAL_POSITION;

SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, INDEX_TYPE, SUB_PART
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @lab_inventory_table
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

SET @lab_inventory_engine = (
    SELECT ENGINE FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @lab_inventory_table
);
SET @lab_inventory_fulltext = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @lab_inventory_table
      AND INDEX_TYPE = 'FULLTEXT'
);
SET @lab_inventory_triggers = (
    SELECT COUNT(*) FROM information_schema.TRIGGERS
    WHERE EVENT_OBJECT_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = @lab_inventory_table
);
-- Unknown triggers/FULLTEXT require a separate review; never guess compatibility.
SET @lab_conversion_sql = CASE
    WHEN @lab_inventory_engine = 'InnoDB'
        THEN 'SELECT ''Already InnoDB: no ALTER executed'' AS migration_status'
    WHEN @lab_allow_inventory_conversion <> 1
        THEN 'SELECT ''Inspection only: manual opt-in is disabled'' AS migration_status'
    WHEN @lab_inventory_engine = 'MyISAM'
         AND @lab_inventory_fulltext = 0 AND @lab_inventory_triggers = 0
         AND @lab_inventory_table REGEXP '^[A-Za-z0-9_]+$'
        THEN CONCAT('ALTER TABLE `', @lab_inventory_table, '` ENGINE=InnoDB')
    ELSE 'SELECT ''Blocked: missing table, unsupported engine, FULLTEXT, trigger or identifier; review required'' AS migration_status'
END;
PREPARE lab_inventory_conversion FROM @lab_conversion_sql;
EXECUTE lab_inventory_conversion;
DEALLOCATE PREPARE lab_inventory_conversion;

SELECT TABLE_NAME, ENGINE, AUTO_INCREMENT, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @lab_inventory_table;
