Set WshShell = CreateObject("WScript.Shell")
WshShell.Run chr(34) & "C:\laragon\www\Inventory\run-scheduler.bat" & chr(34), 0
Set WshShell = Nothing