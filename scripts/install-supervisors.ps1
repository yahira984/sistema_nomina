$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$PowerShell = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
$User = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name

$workerAction = New-ScheduledTaskAction -Execute $PowerShell -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$Root\scripts\worker-supervisor.ps1`""
$schedulerAction = New-ScheduledTaskAction -Execute $PowerShell -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$Root\scripts\scheduler-supervisor.ps1`""
$trigger = New-ScheduledTaskTrigger -AtLogOn -User $User
$settings = New-ScheduledTaskSettingsSet -RestartCount 999 -RestartInterval (New-TimeSpan -Minutes 1) -ExecutionTimeLimit (New-TimeSpan -Days 3650)

Register-ScheduledTask -TaskName 'Nominas-QueueWorker' -Action $workerAction -Trigger $trigger -Settings $settings -User $User -Force | Out-Null
Register-ScheduledTask -TaskName 'Nominas-Scheduler' -Action $schedulerAction -Trigger $trigger -Settings $settings -User $User -Force | Out-Null
Start-ScheduledTask -TaskName 'Nominas-QueueWorker'
Start-ScheduledTask -TaskName 'Nominas-Scheduler'
Write-Host 'Worker y programador instalados con reinicio automatico.' -ForegroundColor Green
