<?php
/**
 * Restore Engine - One-Click-Wiederherstellung
 * Sichere Wiederherstellung von Backups mit Rollback-Mechanismus
 */

require_once __DIR__ . '/config.php';

class RestoreEngine {
    private $logFile;
    private $rollbackDir;
    
    public function __construct() {
        $this->logFile = BACKUP_LOGS_DIR . '/restore_' . date('Y-m-d_H-i-s') . '.log';
        $this->rollbackDir = BACKUP_ROOT_DIR . '/rollback';
        
        // Rollback-Verzeichnis erstellen
        if (!is_dir($this->rollbackDir)) {
            mkdir($this->rollbackDir, 0755, true);
        }
    }
    
    /**
     * Datenbank wiederherstellen
     */
    public function restoreDatabase($backupFile, $createRollback = true) {
        $this->log("=== DATENBANK-WIEDERHERSTELLUNG START ===");
        $this->log("Backup-Datei: $backupFile");
        
        try {
            // Backup-Datei prüfen
            $backupPath = BACKUP_DB_DIR . '/' . basename($backupFile);
            if (!file_exists($backupPath)) {
                throw new Exception("Backup-Datei nicht gefunden: $backupFile");
            }
            
            // Rollback erstellen (aktuellen Stand sichern)
            if ($createRollback) {
                $this->log("Erstelle Rollback-Punkt...");
                $rollbackFile = $this->createDatabaseRollback();
                $this->log("✅ Rollback erstellt: $rollbackFile");
            }
            
            // Backup entpacken (falls .gz)
            $sqlFile = $backupPath;
            if (substr($backupPath, -3) === '.gz') {
                $this->log("Entpacke Backup...");
                $sqlFile = $this->decompressBackup($backupPath);
            }
            
            // Datenbank-Verbindung
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($mysqli->connect_error) {
                throw new Exception("DB-Verbindung fehlgeschlagen: " . $mysqli->connect_error);
            }
            
            $this->log("Lese SQL-Befehle...");
            $sql = file_get_contents($sqlFile);
            
            if (empty($sql)) {
                throw new Exception("Backup-Datei ist leer!");
            }
            
            // Foreign Key Checks deaktivieren
            $mysqli->query("SET FOREIGN_KEY_CHECKS=0");
            
            // SQL ausführen (in Chunks wegen großer Dateien)
            $this->log("Führe SQL-Befehle aus...");
            $queries = $this->splitSQLQueries($sql);
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query) || substr($query, 0, 2) === '--') {
                    continue;
                }
                
                if (!$mysqli->query($query)) {
                    $errorCount++;
                    $this->log("⚠️ Fehler bei Query: " . $mysqli->error);
                } else {
                    $successCount++;
                }
            }
            
            // Foreign Key Checks wieder aktivieren
            $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
            
            $mysqli->close();
            
            // Temporäre Datei löschen
            if ($sqlFile !== $backupPath) {
                unlink($sqlFile);
            }
            
            $this->log("✅ Wiederherstellung abgeschlossen");
            $this->log("Erfolgreich: $successCount Queries");
            if ($errorCount > 0) {
                $this->log("⚠️ Fehler: $errorCount Queries");
            }
            $this->log("=== WIEDERHERSTELLUNG END ===\n");
            
            return [
                'success' => true,
                'queries_executed' => $successCount,
                'queries_failed' => $errorCount,
                'rollback_file' => $createRollback ? $rollbackFile : null
            ];
            
        } catch (Exception $e) {
            $this->log("❌ FEHLER: " . $e->getMessage());
            $this->log("=== WIEDERHERSTELLUNG ABGEBROCHEN ===\n");
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Dateien wiederherstellen
     */
    public function restoreFiles($backupFile, $createRollback = true) {
        $this->log("=== DATEI-WIEDERHERSTELLUNG START ===");
        $this->log("Backup-Datei: $backupFile");
        
        try {
            // Backup-Datei prüfen
            $backupPath = BACKUP_FILES_DIR . '/' . basename($backupFile);
            if (!file_exists($backupPath)) {
                throw new Exception("Backup-Datei nicht gefunden: $backupFile");
            }
            
            // Rollback erstellen
            if ($createRollback) {
                $this->log("Erstelle Rollback-Punkt...");
                $rollbackFile = $this->createFilesRollback();
                $this->log("✅ Rollback erstellt: $rollbackFile");
            }
            
            // Temporäres Verzeichnis für Extraktion
            $tempDir = sys_get_temp_dir() . '/backup_restore_' . uniqid();
            mkdir($tempDir, 0755, true);
            
            $this->log("Extrahiere Backup...");
            
            // Entpacken
            $phar = new PharData($backupPath);
            $phar->extractTo($tempDir, null, true);
            
            // Dateien kopieren
            $this->log("Kopiere Dateien...");
            $this->recursiveCopy($tempDir, PROJECT_ROOT);
            
            // Aufräumen
            $this->removeDirectory($tempDir);
            
            $this->log("✅ Datei-Wiederherstellung abgeschlossen");
            $this->log("=== WIEDERHERSTELLUNG END ===\n");
            
            return [
                'success' => true,
                'rollback_file' => $createRollback ? $rollbackFile : null
            ];
            
        } catch (Exception $e) {
            $this->log("❌ FEHLER: " . $e->getMessage());
            $this->log("=== WIEDERHERSTELLUNG ABGEBROCHEN ===\n");
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Rollback durchführen
     */
    public function rollback($rollbackFile) {
        $this->log("=== ROLLBACK START ===");
        $this->log("Rollback-Datei: $rollbackFile");
        
        $rollbackPath = $this->rollbackDir . '/' . basename($rollbackFile);
        
        if (!file_exists($rollbackPath)) {
            $this->log("❌ Rollback-Datei nicht gefunden!");
            return ['success' => false, 'error' => 'Rollback-Datei nicht gefunden'];
        }
        
        // Typ erkennen (database oder files)
        if (strpos($rollbackFile, 'db_rollback') !== false) {
            return $this->restoreDatabaseRollback($rollbackPath);
        } else {
            return $this->restoreFilesRollback($rollbackPath);
        }
    }
    
    /**
     * Datenbank-Rollback erstellen
     */
    private function createDatabaseRollback() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "db_rollback_before_restore_{$timestamp}.sql";
        $filepath = $this->rollbackDir . '/' . $filename;
        
        // Aktuellen DB-Stand sichern (wie normales Backup)
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        $tables = [];
        $result = $mysqli->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $dump = "-- Rollback-Punkt: " . date('Y-m-d H:i:s') . "\n\n";
        $dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            $dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $createResult = $mysqli->query("SHOW CREATE TABLE `$table`");
            $createRow = $createResult->fetch_array();
            $dump .= $createRow[1] . ";\n\n";
            
            $dataResult = $mysqli->query("SELECT * FROM `$table`");
            if ($dataResult->num_rows > 0) {
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
        
        file_put_contents($filepath, $dump);
        
        // Komprimieren
        $this->compressFile($filepath);
        unlink($filepath);
        
        $mysqli->close();
        
        return basename($filepath . '.gz');
    }
    
    /**
     * Datei-Rollback erstellen
     */
    private function createFilesRollback() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "files_rollback_before_restore_{$timestamp}.tar.gz";
        $filepath = $this->rollbackDir . '/' . $filename;
        
        // Kritische Dateien/Verzeichnisse sichern
        $tempTar = str_replace('.gz', '', $filepath);
        $phar = new PharData($tempTar);
        
        // Nur kritische Dateien sichern (nicht das gesamte Projekt)
        $criticalPaths = [
            PROJECT_ROOT . '/config',
            PROJECT_ROOT . '/admin',
            PROJECT_ROOT . '/api',
            PROJECT_ROOT . '/includes',
            PROJECT_ROOT . '/.htaccess',
            PROJECT_ROOT . '/index.php'
        ];
        
        foreach ($criticalPaths as $path) {
            if (file_exists($path)) {
                $relativePath = substr($path, strlen(PROJECT_ROOT));
                if (is_file($path)) {
                    $phar->addFile($path, $relativePath);
                } else {
                    $this->addDirectoryToPhar($phar, $path, PROJECT_ROOT);
                }
            }
        }
        
        // Komprimieren
        $phar->compress(Phar::GZ);
        unlink($tempTar);
        
        return $filename;
    }
    
    /**
     * Backup entpacken
     */
    private function decompressBackup($gzFile) {
        $sqlFile = str_replace('.gz', '', $gzFile);
        
        $gzfp = gzopen($gzFile, 'rb');
        $fp = fopen($sqlFile, 'wb');
        
        while (!gzeof($gzfp)) {
            fwrite($fp, gzread($gzfp, 1024 * 512));
        }
        
        gzclose($gzfp);
        fclose($fp);
        
        return $sqlFile;
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
     * SQL-Queries aufteilen
     */
    private function splitSQLQueries($sql) {
        // Einfache Methode: an Semikolon + Newline splitten
        $queries = [];
        $currentQuery = '';
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }
            
            $currentQuery .= $line . ' ';
            
            if (substr($line, -1) === ';') {
                $queries[] = trim($currentQuery);
                $currentQuery = '';
            }
        }
        
        if (!empty(trim($currentQuery))) {
            $queries[] = trim($currentQuery);
        }
        
        return $queries;
    }
    
    /**
     * Rekursiv kopieren
     */
    private function recursiveCopy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);
        
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;
            
            if (is_dir($srcFile)) {
                $this->recursiveCopy($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
        
        closedir($dir);
    }
    
    /**
     * Verzeichnis rekursiv löschen
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }
            
            $path = $dir . '/' . $object;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Verzeichnis zu Phar hinzufügen
     */
    private function addDirectoryToPhar($phar, $directory, $baseDir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getPathname(), strlen($baseDir));
                $phar->addFile($file->getPathname(), $relativePath);
            }
        }
    }
    
    /**
     * Log-Eintrag
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        
        if (php_sapi_name() === 'cli') {
            echo $logEntry;
        }
    }
    
    /**
     * Verfügbare Rollbacks auflisten
     */
    public function listRollbacks() {
        $rollbacks = [];
        
        $files = glob($this->rollbackDir . '/*');
        rsort($files);
        
        foreach ($files as $file) {
            $rollbacks[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'size_formatted' => $this->formatBytes(filesize($file)),
                'created' => filemtime($file),
                'created_formatted' => date('d.m.Y H:i', filemtime($file)),
                'type' => strpos(basename($file), 'db_') !== false ? 'database' : 'files'
            ];
        }
        
        return $rollbacks;
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
    
    /**
     * Datenbank-Rollback wiederherstellen
     */
    private function restoreDatabaseRollback($rollbackPath) {
        // Wie normale DB-Restore, aber ohne erneuten Rollback
        return $this->restoreDatabase(basename($rollbackPath), false);
    }
    
    /**
     * Datei-Rollback wiederherstellen
     */
    private function restoreFilesRollback($rollbackPath) {
        // Wie normale Files-Restore, aber ohne erneuten Rollback
        return $this->restoreFiles(basename($rollbackPath), false);
    }
}

// CLI-Ausführung
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? '';
    $file = $argv[2] ?? '';
    
    if (empty($action) || empty($file)) {
        echo "Usage: php restore.php <database|files|rollback> <backup-file>\n";
        exit(1);
    }
    
    $restore = new RestoreEngine();
    
    switch ($action) {
        case 'database':
            $result = $restore->restoreDatabase($file);
            break;
        case 'files':
            $result = $restore->restoreFiles($file);
            break;
        case 'rollback':
            $result = $restore->rollback($file);
            break;
        default:
            echo "Ungültige Aktion: $action\n";
            exit(1);
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    exit($result['success'] ? 0 : 1);
}
