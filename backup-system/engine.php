<?php
/**
 * Backup Engine - Kern-Funktionalität für automatische Backups
 * Kann via Cronjob oder manuell ausgeführt werden
 */

require_once __DIR__ . '/config.php';

class BackupEngine {
    private $logFile;
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
        $this->logFile = BACKUP_LOGS_DIR . '/backup_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Hauptmethode - führt Backup durch
     */
    public function execute($type = 'full') {
        $this->log("=== BACKUP START: $type ===");
        
        try {
            switch ($type) {
                case 'database':
                    $result = $this->backupDatabase();
                    break;
                case 'files':
                    $result = $this->backupFiles();
                    break;
                case 'full':
                default:
                    $dbResult = $this->backupDatabase();
                    $fileResult = $this->backupFiles();
                    $result = $dbResult && $fileResult;
                    break;
            }
            
            if ($result) {
                $this->log("✅ Backup erfolgreich abgeschlossen");
                $this->cleanOldBackups();
                $this->syncToExternalStorage($type);
                
                if (BACKUP_NOTIFY_ON_SUCCESS && BACKUP_NOTIFY_EMAIL) {
                    $this->sendNotification('success', $type);
                }
            } else {
                throw new Exception("Backup fehlgeschlagen");
            }
            
        } catch (Exception $e) {
            $this->log("❌ FEHLER: " . $e->getMessage());
            
            if (BACKUP_NOTIFY_ON_ERROR && BACKUP_NOTIFY_EMAIL) {
                $this->sendNotification('error', $type, $e->getMessage());
            }
            
            return false;
        }
        
        $duration = round(microtime(true) - $this->startTime, 2);
        $this->log("Dauer: {$duration}s");
        $this->log("=== BACKUP END ===\n");
        
        return true;
    }
    
    /**
     * Datenbank-Backup erstellen
     */
    private function backupDatabase() {
        $this->log("📦 Erstelle Datenbank-Backup...");
        
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "db_backup_{$timestamp}.sql";
        $filepath = BACKUP_DB_DIR . '/' . $filename;
        
        try {
            // Verbindung zur Datenbank
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($mysqli->connect_error) {
                throw new Exception("DB-Verbindung fehlgeschlagen: " . $mysqli->connect_error);
            }
            
            // Hole alle Tabellen
            $tables = [];
            $result = $mysqli->query("SHOW TABLES");
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            // SQL-Dump erstellen
            $dump = "-- KI-Lead System Backup\n";
            $dump .= "-- Erstellt am: " . date('Y-m-d H:i:s') . "\n";
            $dump .= "-- Datenbank: " . DB_NAME . "\n\n";
            $dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                $this->log("  → Sichere Tabelle: $table");
                
                // DROP TABLE IF EXISTS
                $dump .= "-- Tabelle: $table\n";
                $dump .= "DROP TABLE IF EXISTS `$table`;\n";
                
                // CREATE TABLE
                $createResult = $mysqli->query("SHOW CREATE TABLE `$table`");
                $createRow = $createResult->fetch_array();
                $dump .= $createRow[1] . ";\n\n";
                
                // INSERT DATA
                $dataResult = $mysqli->query("SELECT * FROM `$table`");
                if ($dataResult->num_rows > 0) {
                    $dump .= "-- Daten für Tabelle $table\n";
                    
                    while ($row = $dataResult->fetch_assoc()) {
                        $values = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . $mysqli->real_escape_string($value) . "'";
                            }
                        }
                        $dump .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $dump .= "\n";
                }
            }
            
            $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Speichern
            file_put_contents($filepath, $dump);
            
            // Komprimieren
            if (BACKUP_COMPRESS) {
                $this->compressFile($filepath);
                unlink($filepath); // Original löschen
                $filepath .= '.gz';
                $filename .= '.gz';
            }
            
            $size = $this->formatBytes(filesize($filepath));
            $this->log("✅ Datenbank-Backup erstellt: $filename ($size)");
            
            $mysqli->close();
            
            // Metadaten speichern
            $this->saveBackupMetadata('database', $filename, filesize($filepath));
            
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ DB-Backup fehlgeschlagen: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Datei-Backup erstellen
     */
    private function backupFiles() {
        $this->log("📁 Erstelle Datei-Backup...");
        
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "files_backup_{$timestamp}.tar";
        $filepath = BACKUP_FILES_DIR . '/' . $filename;
        
        try {
            // Temporäres Tar-Archiv erstellen
            $phar = new PharData($filepath);
            
            // Dateien hinzufügen
            $this->addFilesToArchive($phar, PROJECT_ROOT);
            
            // Komprimieren
            if (BACKUP_COMPRESS) {
                $this->log("  → Komprimiere Archiv...");
                $phar->compress(Phar::GZ);
                unlink($filepath); // Unkomprimiertes TAR löschen
                $filepath .= '.gz';
                $filename .= '.gz';
            }
            
            $size = $this->formatBytes(filesize($filepath));
            $this->log("✅ Datei-Backup erstellt: $filename ($size)");
            
            // Metadaten speichern
            $this->saveBackupMetadata('files', $filename, filesize($filepath));
            
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ Datei-Backup fehlgeschlagen: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Dateien rekursiv zum Archiv hinzufügen
     */
    private function addFilesToArchive($phar, $directory, $prefix = '') {
        global $excludeDirectories;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), strlen(PROJECT_ROOT));
            
            // Excludierte Verzeichnisse überspringen
            $skip = false;
            foreach ($excludeDirectories as $exclude) {
                if (strpos($relativePath, $exclude) === 0) {
                    $skip = true;
                    break;
                }
            }
            
            if (!$skip && $file->isFile()) {
                $phar->addFile($file->getPathname(), $relativePath);
            }
        }
    }
    
    /**
     * Datei komprimieren
     */
    private function compressFile($file) {
        $gzFile = $file . '.gz';
        $fp = fopen($file, 'rb');
        $gzfp = gzopen($gzFile, 'wb9');
        
        while (!feof($fp)) {
            gzwrite($gzfp, fread($fp, 1024 * 512));
        }
        
        fclose($fp);
        gzclose($gzfp);
    }
    
    /**
     * Alte Backups aufräumen
     */
    private function cleanOldBackups() {
        $this->log("🧹 Räume alte Backups auf...");
        
        $cutoffDate = strtotime("-" . BACKUP_RETENTION_DAYS . " days");
        $deleted = 0;
        
        foreach ([BACKUP_DB_DIR, BACKUP_FILES_DIR] as $dir) {
            $files = glob($dir . '/*');
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffDate) {
                    unlink($file);
                    $deleted++;
                    $this->log("  → Gelöscht: " . basename($file));
                }
            }
        }
        
        if ($deleted > 0) {
            $this->log("✅ $deleted alte Backup(s) gelöscht");
        } else {
            $this->log("ℹ️ Keine alten Backups zum Löschen");
        }
    }
    
    /**
     * Backup zu externen Speicherorten synchronisieren
     */
    private function syncToExternalStorage($type) {
        global $externalStorageConfig;
        
        // FTP Upload
        if ($externalStorageConfig['ftp']['enabled']) {
            $this->syncToFTP($type);
        }
        
        // Lokaler externer Pfad
        if ($externalStorageConfig['local_external']['enabled']) {
            $this->syncToLocalExternal($type);
        }
        
        // Cloud Storage
        if ($externalStorageConfig['cloud']['enabled']) {
            $this->syncToCloud($type);
        }
    }
    
    /**
     * FTP-Upload
     */
    private function syncToFTP($type) {
        global $externalStorageConfig;
        $this->log("☁️ Synchronisiere zu FTP...");
        
        $config = $externalStorageConfig['ftp'];
        
        try {
            $conn = ftp_connect($config['host'], $config['port']);
            if (!$conn) throw new Exception("FTP-Verbindung fehlgeschlagen");
            
            $login = ftp_login($conn, $config['username'], $config['password']);
            if (!$login) throw new Exception("FTP-Login fehlgeschlagen");
            
            ftp_pasv($conn, true);
            
            // Remote-Verzeichnis erstellen falls nicht vorhanden
            @ftp_mkdir($conn, $config['remote_path']);
            
            // Neueste Backups hochladen
            $dirs = $type === 'database' || $type === 'full' ? [BACKUP_DB_DIR] : [];
            if ($type === 'files' || $type === 'full') $dirs[] = BACKUP_FILES_DIR;
            
            foreach ($dirs as $dir) {
                $files = glob($dir . '/*');
                rsort($files); // Neueste zuerst
                
                if (!empty($files)) {
                    $latestFile = $files[0];
                    $remotePath = $config['remote_path'] . '/' . basename($latestFile);
                    
                    if (ftp_put($conn, $remotePath, $latestFile, FTP_BINARY)) {
                        $this->log("  → Hochgeladen: " . basename($latestFile));
                    }
                }
            }
            
            ftp_close($conn);
            $this->log("✅ FTP-Sync abgeschlossen");
            
        } catch (Exception $e) {
            $this->log("❌ FTP-Sync fehlgeschlagen: " . $e->getMessage());
        }
    }
    
    /**
     * Lokaler externer Speicher
     */
    private function syncToLocalExternal($type) {
        global $externalStorageConfig;
        $this->log("💾 Synchronisiere zu lokalem Speicher...");
        
        $config = $externalStorageConfig['local_external'];
        $targetPath = $config['path'];
        
        if (!is_dir($targetPath)) {
            $this->log("⚠️ Zielpfad existiert nicht: $targetPath");
            return;
        }
        
        try {
            $dirs = $type === 'database' || $type === 'full' ? [BACKUP_DB_DIR] : [];
            if ($type === 'files' || $type === 'full') $dirs[] = BACKUP_FILES_DIR;
            
            foreach ($dirs as $dir) {
                $files = glob($dir . '/*');
                rsort($files);
                
                if (!empty($files)) {
                    $latestFile = $files[0];
                    $targetFile = $targetPath . '/' . basename($latestFile);
                    
                    if (copy($latestFile, $targetFile)) {
                        $this->log("  → Kopiert: " . basename($latestFile));
                    }
                }
            }
            
            $this->log("✅ Lokaler Sync abgeschlossen");
            
        } catch (Exception $e) {
            $this->log("❌ Lokaler Sync fehlgeschlagen: " . $e->getMessage());
        }
    }
    
    /**
     * Cloud-Upload (generisch)
     */
    private function syncToCloud($type) {
        // Placeholder für Cloud-Integration (AWS S3, Google Cloud, etc.)
        $this->log("☁️ Cloud-Sync: Noch nicht implementiert");
    }
    
    /**
     * Backup-Metadaten speichern
     */
    private function saveBackupMetadata($type, $filename, $size) {
        $metaFile = BACKUP_ROOT_DIR . '/metadata.json';
        
        $metadata = [];
        if (file_exists($metaFile)) {
            $metadata = json_decode(file_get_contents($metaFile), true) ?: [];
        }
        
        $metadata[] = [
            'type' => $type,
            'filename' => $filename,
            'size' => $size,
            'created_at' => date('Y-m-d H:i:s'),
            'timestamp' => time()
        ];
        
        // Nur die neuesten Einträge behalten
        usort($metadata, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        $metadata = array_slice($metadata, 0, MAX_BACKUPS_PER_TYPE * 2);
        
        file_put_contents($metaFile, json_encode($metadata, JSON_PRETTY_PRINT));
    }
    
    /**
     * Benachrichtigung senden
     */
    private function sendNotification($status, $type, $error = '') {
        $subject = $status === 'success' 
            ? "✅ Backup erfolgreich: $type" 
            : "❌ Backup fehlgeschlagen: $type";
        
        $message = "Backup-Status: " . strtoupper($status) . "\n";
        $message .= "Typ: $type\n";
        $message .= "Zeitpunkt: " . date('Y-m-d H:i:s') . "\n";
        
        if ($error) {
            $message .= "\nFehler:\n$error\n";
        }
        
        $message .= "\nLog-Datei: " . $this->logFile;
        
        mail(BACKUP_NOTIFY_EMAIL, $subject, $message);
    }
    
    /**
     * Log-Eintrag erstellen
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        
        // Auch auf Konsole ausgeben falls CLI
        if (php_sapi_name() === 'cli') {
            echo $logEntry;
        }
    }
    
    /**
     * Bytes formatieren
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// CLI-Ausführung
if (php_sapi_name() === 'cli') {
    $type = $argv[1] ?? 'full';
    
    echo "🚀 Starte Backup: $type\n";
    
    $engine = new BackupEngine();
    $success = $engine->execute($type);
    
    exit($success ? 0 : 1);
}
