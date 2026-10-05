param([ValidateSet('start','stop')][string]$Action = 'start', [string]$MysqlBin = 'C:/xampp/mysql/bin')
$ErrorActionPreference = 'Stop'
$testData = Join-Path ([IO.Path]::GetTempPath()) 'codex-system-installer-db'
$testPid = Join-Path $testData 'installer.pid'
if ($Action -eq 'stop') {
    if (!(Test-Path -LiteralPath $testPid)) { throw 'No test-server pid file.' }
    $testProcessId = [int](Get-Content -LiteralPath $testPid)
    $testProcess = Get-CimInstance Win32_Process -Filter "ProcessId = $testProcessId"
    if (!$testProcess -or $testProcess.Name -ne 'mysqld.exe' -or $testProcess.CommandLine -notlike "*$testData*") { throw 'Refusing to stop a server outside the isolated test datadir.' }
    & (Join-Path $MysqlBin 'mysqladmin.exe') --no-defaults --host=127.0.0.1 --port=33381 --user=root shutdown
    exit $LASTEXITCODE
}
if (Get-NetTCPConnection -LocalPort 33381 -State Listen -ErrorAction SilentlyContinue) { throw 'Port 33381 is already in use. Inspect the existing test server first.' }
if (!(Test-Path -LiteralPath (Join-Path $testData 'mysql'))) {
    & (Join-Path $MysqlBin 'mysql_install_db.exe') "--datadir=$testData" --port=33381
    if ($LASTEXITCODE -ne 0) { throw 'Isolated database initialization failed.' }
}
$testArguments = @('--no-defaults', ('--datadir="' + $testData + '"'), '--port=33381', '--bind-address=127.0.0.1', '--innodb-buffer-pool-size=64M', ('--pid-file="' + $testPid + '"'), ('--log-error="' + (Join-Path $testData 'installer.err') + '"'))
Start-Process -FilePath (Join-Path $MysqlBin 'mysqld.exe') -ArgumentList $testArguments -WindowStyle Hidden | Out-Null
Write-Output "Isolated MariaDB starting on 127.0.0.1:33381: $testData"
