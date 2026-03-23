Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "powershell -ExecutionPolicy Bypass -NoExit -Command ""cd e:\web\crm.laikephp.com; .\auto-commit-watcher.ps1""", 1, False
