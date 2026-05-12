@echo off

echo =============================== >> "C:\laragon\www\Inventory\storage\logs\scheduler-debug.log"
echo Scheduler BAT started at %date% %time% >> "C:\laragon\www\Inventory\storage\logs\scheduler-debug.log"

cd /d "C:\laragon\www\Inventory"

"C:\laragon\bin\php\php-8.4\php.exe" artisan schedule:run -vvv >> "C:\laragon\www\Inventory\storage\logs\scheduler.log" 2>> "C:\laragon\www\Inventory\storage\logs\scheduler-error.log"

echo Scheduler BAT finished at %date% %time% >> "C:\laragon\www\Inventory\storage\logs\scheduler-debug.log"