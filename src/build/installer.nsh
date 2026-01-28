RequestExecutionLevel admin



!macro customInstall
  DetailPrint "Installing / Restoring PostgreSQL database..."

  IfFileExists "$INSTDIR\resources\database_deploy\install_restore_pg.bat" 0 db_missing

  ; Run BAT via cmd.exe and WAIT
  ExecWait '"$SYSDIR\cmd.exe" /c ""$INSTDIR\resources\database_deploy\install_restore_pg.bat""' $0

  StrCmp $0 "0" db_ok db_fail

db_missing:
  MessageBox MB_ICONSTOP "Database install script not found:$\r$\n$INSTDIR\resources\database_deploy\install_restore_pg.bat"
  Abort

db_fail:
  MessageBox MB_ICONSTOP "Database installation failed (exit code: $0).$\r$\nCheck: C:\ProgramData\XtremeGuardParking\logs\db_install.log"
  Abort

db_ok:
  DetailPrint "Database installation completed."
!macroend
