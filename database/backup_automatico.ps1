# ============================================
# SCRIPT DE BACKUP AUTOMÁTICO - SIGAE
# Versión simplificada y probada
# ============================================

# Configuración
$fecha = Get-Date -Format "yyyy-MM-dd_HHmmss"
$backupDir = "C:\xampp\htdocs\SIGAE\storage\backups"
$backupFile = "$backupDir\sigae_backup_$fecha.sql"
$logFile = "$backupDir\backup_log.txt"

# Datos de conexión (CAMBIA SEGÚN TU VERSIÓN)
$pgUser = "postgres"
$pgHost = "localhost"
$pgPort = "5432"
$pgDatabase = "sigaedb"
$pgPassword = "123456"
$pgVersion = "17"  # CAMBIA A 16 SI TIENES ESA VERSIÓN

# Crear directorio si no existe
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    Write-Host "Directorio creado: $backupDir" -ForegroundColor Green
}

Write-Host "========================================"
Write-Host "Iniciando backup de $pgDatabase..."
Write-Host "========================================"

try {
    # Ruta de pg_dump según versión
    $pgDump = "C:\Program Files\PostgreSQL\$pgVersion\bin\pg_dump.exe"
    
    if (Test-Path $pgDump) {
        Write-Host "Usando: $pgDump" -ForegroundColor Cyan
        
        # Configurar contraseña
        $env:PGPASSWORD = $pgPassword
        
        # Ejecutar backup
        & $pgDump -U $pgUser -h $pgHost -p $pgPort $pgDatabase > $backupFile
        
        # Verificar resultado
        $fileInfo = Get-Item $backupFile
        if ($fileInfo.Length -gt 0) {
            Write-Host "BACKUP EXITOSO!" -ForegroundColor Green
            Write-Host "Archivo: $backupFile" -ForegroundColor Green
            Write-Host "Tamaño: $($fileInfo.Length) bytes" -ForegroundColor Green
            
            # Guardar en log
            $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
            "$timestamp - Backup exitoso: $backupFile ($($fileInfo.Length) bytes)" | Out-File -FilePath $logFile -Append
        } else {
            Write-Host "ERROR: El archivo de backup está vacío" -ForegroundColor Red
        }
    } else {
        Write-Host "ERROR: No se encontró pg_dump.exe en $pgDump" -ForegroundColor Red
        Write-Host "Verifica la versión de PostgreSQL (16 o 17)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "ERROR: $_" -ForegroundColor Red
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    "$timestamp - ERROR: $_" | Out-File -FilePath $logFile -Append
} finally {
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
}

Write-Host "========================================"
Write-Host "Proceso completado."
Write-Host "Log: $logFile"
Write-Host "========================================"