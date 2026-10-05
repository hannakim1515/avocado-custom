<?php
// Legacy bookmarks and POST actions now land on the non-destructive installer.
header('Location: ./system_install.php', true, 303);
exit;
