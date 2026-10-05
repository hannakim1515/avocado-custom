<?php
/** Compare every exported row and installed object with the actual main database. */
require dirname(__DIR__).'/tools/qa_bootstrap.php';
qa_assert($qaFixture,'Export comparison requires isolated target');
$source=new mysqli(G5_MYSQL_HOST,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB,3306);$source->set_charset('utf8mb4');
function export_objects($connection){return $connection->query('SELECT TABLE_NAME,TABLE_TYPE,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME')->fetch_all(MYSQLI_ASSOC);}
function export_hash($connection,$table){$rows=$connection->query('SELECT * FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC);$strings=array_map(function($row){return json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);},$rows);sort($strings,SORT_STRING);return array('rows'=>count($strings),'sha256'=>hash('sha256',implode("\n",$strings)));}
$objects=export_objects($source);qa_assert($objects===export_objects($db),'Object names, views and table engines differ after import');
$report=array('ok'=>true,'objects'=>count($objects),'tables'=>array());
foreach($objects as $object){$table=$object['TABLE_NAME'];$before=export_hash($source,$table);$after=export_hash($db,$table);qa_assert($before===$after,'Export row mismatch: '.$table);$report['tables'][$table]=$after;}
echo json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;
