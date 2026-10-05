<?php
include_once './_common.php';
goto_url('./maze.php?ds_id='.(int)($_REQUEST['ds_id']??0));
