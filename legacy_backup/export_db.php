<?php
/**
 * MS SQL Server Schema & Data Export Tool
 * Supported Drivers: PDO sqlsrv
 */

session_start();

// --- Configuration & Security ---
define('DB_SERVER', 'ovinfinity.in');
define('DB_DATABASE', 'humckbre_ProductionDB');
define('DB_USERNAME', 'Ovidata');
define('DB_PASSWORD', '0cG9_3c1l');

// SECURITY PASSWORD: Change this password to lock/unlock the tool
define('ACCESS_PASSWORD', 'OviBackup2026');

// --- Session Authentication ---
if (isset($_POST['password'])) {
    if ($_POST['password'] === ACCESS_PASSWORD) {
        $_SESSION['backup_auth'] = true;
    } else {
        $login_error = "Incorrect access password!";
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['backup_auth']);
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$authenticated = (ACCESS_PASSWORD === '' || (isset($_SESSION['backup_auth']) && $_SESSION['backup_auth'] === true));

// --- Database Connection Helper ---
function getDb() {
    $dsn = "sqlsrv:Server=" . DB_SERVER . ";Database=" . DB_DATABASE;
    try {
        return new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 60
        ]);
    } catch (Exception $e) {
        throw new Exception("Connection failed: " . $e->getMessage());
    }
}

// Helper to clean up safe file paths
function getSafeFileName($dbname, $suffix = '', $dialect = 'mssql') {
    $clean = preg_replace('/[^a-zA-Z0-9_]/', '_', $dbname);
    $dialectStr = ($dialect === 'mysql') ? '_mysql' : '_mssql';
    return 'backup_' . $clean . $dialectStr . '_' . date('Ymd_His') . $suffix . '.sql';
}

// Helper to construct dialect-specific column SQL
function getColumnSqlDefinition($c, $dialect = 'mssql') {
    if ($dialect === 'mysql') {
        $name = "`" . $c['COLUMN_NAME'] . "`";
        $type = strtolower($c['DATA_TYPE']);
        $nullable = ($c['IS_NULLABLE'] === 'YES') ? 'NULL' : 'NOT NULL';
        
        $mysqlType = $type;
        $size = '';
        
        switch ($type) {
            case 'nvarchar':
                $mysqlType = 'varchar';
                $len = $c['CHARACTER_MAXIMUM_LENGTH'];
                $size = ($len == -1) ? '(65535)' : "($len)";
                if ($len == -1) {
                    $mysqlType = 'longtext';
                    $size = '';
                }
                break;
            case 'varchar':
                $len = $c['CHARACTER_MAXIMUM_LENGTH'];
                $size = ($len == -1) ? '(65535)' : "($len)";
                if ($len == -1) {
                    $mysqlType = 'longtext';
                    $size = '';
                }
                break;
            case 'nchar':
                $mysqlType = 'char';
                $len = $c['CHARACTER_MAXIMUM_LENGTH'];
                $size = "($len)";
                break;
            case 'char':
                $len = $c['CHARACTER_MAXIMUM_LENGTH'];
                $size = "($len)";
                break;
            case 'ntext':
                $mysqlType = 'text';
                break;
            case 'bit':
                $mysqlType = 'tinyint';
                $size = '(1)';
                break;
            case 'datetime2':
            case 'smalldatetime':
                $mysqlType = 'datetime';
                break;
            case 'uniqueidentifier':
                $mysqlType = 'char';
                $size = '(36)';
                break;
            case 'money':
            case 'smallmoney':
                $mysqlType = 'decimal';
                $size = '(19,4)';
                break;
            case 'xml':
                $mysqlType = 'text';
                break;
            case 'image':
            case 'varbinary':
                $len = $c['CHARACTER_MAXIMUM_LENGTH'];
                if ($len == -1 || $type === 'image') {
                    $mysqlType = 'longblob';
                    $size = '';
                } else {
                    $mysqlType = 'varbinary';
                    $size = "($len)";
                }
                break;
            case 'binary':
                $len = $c['CHARACTER_MAXIMUM_LENGTH'];
                $size = "($len)";
                break;
        }
        
        $default = '';
        if ($c['COLUMN_DEFAULT'] !== null) {
            $rawDefault = $c['COLUMN_DEFAULT'];
            while (preg_match('/^\((.*)\)$/', $rawDefault, $m)) {
                $rawDefault = $m[1];
            }
            if (strtolower($rawDefault) === 'getdate()') {
                $default = " DEFAULT CURRENT_TIMESTAMP";
            } elseif (strtolower($rawDefault) === 'newid()') {
                $default = '';
            } else {
                $default = " DEFAULT " . $rawDefault;
            }
        }
        
        $extra = '';
        if ($c['IS_IDENTITY'] == 1) {
            $extra = " AUTO_INCREMENT";
        }
        
        return "$name $mysqlType$size $nullable$default$extra";
    } else {
        $name = "[" . $c['COLUMN_NAME'] . "]";
        $type = strtolower($c['DATA_TYPE']);
        $nullable = ($c['IS_NULLABLE'] === 'YES') ? 'NULL' : 'NOT NULL';
        
        $size = '';
        if (in_array($type, ['varchar', 'nvarchar', 'char', 'nchar', 'varbinary', 'binary'])) {
            $len = $c['CHARACTER_MAXIMUM_LENGTH'];
            $size = ($len == -1) ? '(MAX)' : "($len)";
        } elseif (in_array($type, ['decimal', 'numeric'])) {
            $size = "(" . $c['NUMERIC_PRECISION'] . "," . $c['NUMERIC_SCALE'] . ")";
        }
        
        $default = '';
        if ($c['COLUMN_DEFAULT'] !== null) {
            $default = " DEFAULT " . $c['COLUMN_DEFAULT'];
        }
        
        $identity = '';
        if ($c['IS_IDENTITY'] == 1) {
            $identity = " IDENTITY(1,1)";
        }
        
        return "$name $type$size$identity $nullable$default";
    }
}

// --- AJAX API Handlers ---
if (isset($_GET['ajax']) && $authenticated) {
    header('Content-Type: application/json');
    try {
        $db = getDb();
        $action = $_GET['ajax'];
        
        if ($action === 'init') {
            $exportType = $_GET['type'] ?? 'both'; // both, schema, data
            $dialect = $_GET['dialect'] ?? 'mssql'; // mssql, mysql
            
            // Clean up old temporary files
            if (isset($_SESSION['temp_backup_file']) && file_exists($_SESSION['temp_backup_file'])) {
                @unlink($_SESSION['temp_backup_file']);
            }
            
            $tempFile = __DIR__ . '/' . getSafeFileName(DB_DATABASE, '_temp', $dialect);
            $_SESSION['temp_backup_file'] = $tempFile;
            $_SESSION['export_type'] = $exportType;
            $_SESSION['export_dialect'] = $dialect;
            
            // Open file and write header
            $fh = fopen($tempFile, 'w');
            if (!$fh) throw new Exception("Cannot create backup file on server.");
            
            if ($dialect === 'mysql') {
                fwrite($fh, "-- ==================================================\n");
                fwrite($fh, "-- MySQL Database Dump (Translated from MS SQL Server)\n");
                fwrite($fh, "-- Database: " . DB_DATABASE . "\n");
                fwrite($fh, "-- Export Type: " . strtoupper($exportType) . "\n");
                fwrite($fh, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
                fwrite($fh, "-- ==================================================\n\n");
                
                fwrite($fh, "SET FOREIGN_KEY_CHECKS = 0;\n");
                fwrite($fh, "SET NAMES utf8mb4;\n\n");
            } else {
                fwrite($fh, "-- ==================================================\n");
                fwrite($fh, "-- MS SQL Server Database Dump\n");
                fwrite($fh, "-- Database: " . DB_DATABASE . "\n");
                fwrite($fh, "-- Export Type: " . strtoupper($exportType) . "\n");
                fwrite($fh, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
                fwrite($fh, "-- ==================================================\n\n");
            }
            
            // Get all tables in database
            $sql = "SELECT TABLE_SCHEMA, TABLE_NAME 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_CATALOG = ?
                    ORDER BY TABLE_SCHEMA, TABLE_NAME";
            $stmt = $db->prepare($sql);
            $stmt->execute([DB_DATABASE]);
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If schema is included and using SQL Server, write Drop Foreign Keys script at the top
            if (($exportType === 'both' || $exportType === 'schema') && $dialect === 'mssql') {
                fwrite($fh, "-- --------------------------------------------------\n");
                fwrite($fh, "-- Drop Existing Foreign Keys\n");
                fwrite($fh, "-- --------------------------------------------------\n\n");
                
                // Get all foreign keys
                $fkSql = "SELECT 
                            obj.name AS FK_NAME,
                            sch.name AS TABLE_SCHEMA,
                            tab1.name AS TABLE_NAME
                          FROM sys.foreign_key_columns fkc
                          INNER JOIN sys.objects obj ON obj.object_id = fkc.constraint_object_id
                          INNER JOIN sys.tables tab1 ON tab1.object_id = fkc.parent_object_id
                          INNER JOIN sys.schemas sch ON tab1.schema_id = sch.schema_id";
                $fkStmt = $db->prepare($fkSql);
                $fkStmt->execute();
                $fkeys = $fkStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($fkeys as $fk) {
                    $dropFk = "IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = '{$fk['FK_NAME']}' AND parent_object_id = OBJECT_ID('[{$fk['TABLE_SCHEMA']}].[{$fk['TABLE_NAME']}]'))\n";
                    $dropFk .= "    ALTER TABLE [{$fk['TABLE_SCHEMA']}].[{$fk['TABLE_NAME']}] DROP CONSTRAINT [{$fk['FK_NAME']}];\n";
                    fwrite($fh, $dropFk);
                }
                fwrite($fh, "\n");
            }
            
            fclose($fh);
            
            echo json_encode([
                'success' => true,
                'tables' => array_map(function($t) {
                    return ['schema' => $t['TABLE_SCHEMA'], 'name' => $t['TABLE_NAME']];
                }, $tables)
            ]);
            exit;
        }
        
        if ($action === 'export') {
            $schema = $_GET['schema'] ?? 'dbo';
            $table = $_GET['table'] ?? '';
            $exportType = $_SESSION['export_type'] ?? 'both';
            $dialect = $_SESSION['export_dialect'] ?? 'mssql';
            $tempFile = $_SESSION['temp_backup_file'] ?? '';
            
            if (!$table || !$tempFile || !file_exists($tempFile)) {
                throw new Exception("Invalid export state.");
            }
            
            $fh = fopen($tempFile, 'a');
            if (!$fh) throw new Exception("Cannot append to backup file.");
            
            // 1. Get Columns metadata
            $colSql = "SELECT 
                        COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, 
                        NUMERIC_PRECISION, NUMERIC_SCALE, IS_NULLABLE, COLUMN_DEFAULT,
                        COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') AS IS_IDENTITY
                       FROM INFORMATION_SCHEMA.COLUMNS
                       WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ? AND TABLE_CATALOG = ?
                       ORDER BY ORDINAL_POSITION";
            $colStmt = $db->prepare($colSql);
            $colStmt->execute([$table, $schema, DB_DATABASE]);
            $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 2. Get Primary Keys
            $pkSql = "SELECT ku.COLUMN_NAME
                      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE ku
                      JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                        ON tc.CONSTRAINT_NAME = ku.CONSTRAINT_NAME
                        AND tc.TABLE_NAME = ku.TABLE_NAME
                        AND tc.TABLE_SCHEMA = ku.TABLE_SCHEMA
                      WHERE tc.CONSTRAINT_TYPE = 'PRIMARY KEY' 
                        AND tc.TABLE_NAME = ? AND tc.TABLE_SCHEMA = ? AND tc.TABLE_CATALOG = ?";
                      
            $pkStmt = $db->prepare($pkSql);
            $pkStmt->execute([$table, $schema, DB_DATABASE]);
            $pkRows = $pkStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Write table drop/create schema
            if ($exportType === 'both' || $exportType === 'schema') {
                if ($dialect === 'mysql') {
                    fwrite($fh, "-- --------------------------------------------------\n");
                    fwrite($fh, "-- Schema for Table `{$table}`\n");
                    fwrite($fh, "-- --------------------------------------------------\n");
                    fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
                    
                    $colDefs = [];
                    foreach ($columns as $c) {
                        $colDefs[] = "    " . getColumnSqlDefinition($c, 'mysql');
                    }
                    
                    if (!empty($pkRows)) {
                        $pkCols = implode(", ", array_map(function($p) { return "`$p`"; }, $pkRows));
                        $colDefs[] = "    PRIMARY KEY ($pkCols)";
                    }
                    
                    fwrite($fh, "CREATE TABLE `{$table}` (\n" . implode(",\n", $colDefs) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n");
                } else {
                    fwrite($fh, "-- --------------------------------------------------\n");
                    fwrite($fh, "-- Schema for Table [{$schema}].[{$table}]\n");
                    fwrite($fh, "-- --------------------------------------------------\n");
                    
                    // Drop Table
                    fwrite($fh, "IF OBJECT_ID('[{$schema}].[{$table}]', 'U') IS NOT NULL\n");
                    fwrite($fh, "    DROP TABLE [{$schema}].[{$table}];\n");
                    
                    $colDefs = [];
                    foreach ($columns as $c) {
                        $colDefs[] = "    " . getColumnSqlDefinition($c, 'mssql');
                    }
                    
                    if (!empty($pkRows)) {
                        $pkCols = implode(", ", array_map(function($p) { return "[$p]"; }, $pkRows));
                        $colDefs[] = "    CONSTRAINT [PK_{$schema}_{$table}] PRIMARY KEY ($pkCols)";
                    }
                    
                    fwrite($fh, "CREATE TABLE [{$schema}].[{$table}] (\n" . implode(",\n", $colDefs) . "\n);\n\n");
                }
            }
            
            // 3. Export Data
            $rowsExported = 0;
            if ($exportType === 'both' || $exportType === 'data') {
                $hasIdentity = false;
                foreach ($columns as $c) {
                    if ($c['IS_IDENTITY'] == 1) {
                        $hasIdentity = true;
                        break;
                    }
                }
                
                $dataQuery = "SELECT * FROM [{$schema}].[{$table}]";
                $dataStmt = $db->prepare($dataQuery);
                $dataStmt->execute();
                
                $firstRow = true;
                while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($firstRow) {
                        if ($dialect === 'mysql') {
                            fwrite($fh, "-- Data for Table `{$table}`\n");
                        } else {
                            fwrite($fh, "-- Data for Table [{$schema}].[{$table}]\n");
                            if ($hasIdentity) {
                                fwrite($fh, "SET IDENTITY_INSERT [{$schema}].[{$table}] ON;\n");
                            }
                        }
                        $firstRow = false;
                    }
                    
                    $colsList = [];
                    $valsList = [];
                    foreach ($row as $colName => $val) {
                        $colsList[] = ($dialect === 'mysql') ? "`$colName`" : "[$colName]";
                        
                        $colMeta = null;
                        foreach ($columns as $c) {
                            if ($c['COLUMN_NAME'] === $colName) {
                                $colMeta = $c;
                                break;
                            }
                        }
                        
                        // Handle PHP stream resources (common for varbinary/image/large texts in sqlsrv)
                        if (is_resource($val)) {
                            $streamData = '';
                            while (!feof($val)) {
                                $streamData .= fread($val, 8192);
                            }
                            $val = $streamData;
                        }
                        
                        if ($val === null) {
                            $valsList[] = 'NULL';
                        } else {
                            $type = strtolower($colMeta['DATA_TYPE'] ?? '');
                            if ($type === 'bit') {
                                $valsList[] = $val ? '1' : '0';
                            } elseif (in_array($type, ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'numeric', 'float', 'real'])) {
                                $valsList[] = is_numeric($val) ? $val : '0';
                            } elseif (in_array($type, ['varbinary', 'binary', 'image'])) {
                                $valsList[] = '0x' . bin2hex($val);
                            } else {
                                $escaped = str_replace("'", "''", $val);
                                if ($dialect === 'mysql') {
                                    $valsList[] = "'" . $escaped . "'";
                                } else {
                                    if (in_array($type, ['nvarchar', 'nchar', 'ntext'])) {
                                        $valsList[] = "N'" . $escaped . "'";
                                    } else {
                                        $valsList[] = "'" . $escaped . "'";
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($dialect === 'mysql') {
                        $insert = "INSERT INTO `{$table}` (" . implode(", ", $colsList) . ") VALUES (" . implode(", ", $valsList) . ");\n";
                    } else {
                        $insert = "INSERT INTO [{$schema}].[{$table}] (" . implode(", ", $colsList) . ") VALUES (" . implode(", ", $valsList) . ");\n";
                    }
                    fwrite($fh, $insert);
                    $rowsExported++;
                }
                
                if (!$firstRow) {
                    if ($dialect === 'mssql' && $hasIdentity) {
                        fwrite($fh, "SET IDENTITY_INSERT [{$schema}].[{$table}] OFF;\n");
                    }
                    fwrite($fh, "\n");
                }
            }
            
            fclose($fh);
            echo json_encode(['success' => true, 'rows' => $rowsExported]);
            exit;
        }
        
        if ($action === 'finalize') {
            $exportType = $_SESSION['export_type'] ?? 'both';
            $dialect = $_SESSION['export_dialect'] ?? 'mssql';
            $tempFile = $_SESSION['temp_backup_file'] ?? '';
            
            if (!$tempFile || !file_exists($tempFile)) {
                throw new Exception("No export session found.");
            }
            
            $fh = fopen($tempFile, 'a');
            if (!$fh) throw new Exception("Cannot append to backup file.");
            
            // Append Foreign Keys at the bottom
            if ($exportType === 'both' || $exportType === 'schema') {
                fwrite($fh, "-- --------------------------------------------------\n");
                fwrite($fh, "-- Restore Foreign Key Constraints\n");
                fwrite($fh, "-- --------------------------------------------------\n\n");
                
                $fkSql = "SELECT 
                            obj.name AS FK_NAME,
                            sch.name AS TABLE_SCHEMA,
                            tab1.name AS TABLE_NAME,
                            col1.name AS COLUMN_NAME,
                            sch2.name AS REFERENCED_SCHEMA,
                            tab2.name AS REFERENCED_TABLE_NAME,
                            col2.name AS REFERENCED_COLUMN_NAME
                          FROM sys.foreign_key_columns fkc
                          INNER JOIN sys.objects obj ON obj.object_id = fkc.constraint_object_id
                          INNER JOIN sys.tables tab1 ON tab1.object_id = fkc.parent_object_id
                          INNER JOIN sys.schemas sch ON tab1.schema_id = sch.schema_id
                          INNER JOIN sys.columns col1 ON col1.object_id = fkc.parent_object_id AND col1.column_id = fkc.parent_column_id
                          INNER JOIN sys.tables tab2 ON tab2.object_id = fkc.referenced_object_id
                          INNER JOIN sys.schemas sch2 ON tab2.schema_id = sch2.schema_id
                          INNER JOIN sys.columns col2 ON col2.object_id = fkc.referenced_object_id AND col2.column_id = fkc.referenced_column_id";
                $fkStmt = $db->prepare($fkSql);
                $fkStmt->execute();
                $fkRows = $fkStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Group by constraint name (supports multi-column constraints)
                $groupedFks = [];
                foreach ($fkRows as $row) {
                    $fkName = $row['FK_NAME'];
                    if (!isset($groupedFks[$fkName])) {
                        $groupedFks[$fkName] = [
                            'schema' => $row['TABLE_SCHEMA'],
                            'table' => $row['TABLE_NAME'],
                            'ref_schema' => $row['REFERENCED_SCHEMA'],
                            'ref_table' => $row['REFERENCED_TABLE_NAME'],
                            'cols' => [],
                            'ref_cols' => []
                        ];
                    }
                    $groupedFks[$fkName]['cols'][] = $row['COLUMN_NAME'];
                    $groupedFks[$fkName]['ref_cols'][] = $row['REFERENCED_COLUMN_NAME'];
                }
                
                foreach ($groupedFks as $fkName => $fk) {
                    if ($dialect === 'mysql') {
                        $cols = implode(", ", array_map(function($c) { return "`$c`"; }, $fk['cols']));
                        $refCols = implode(", ", array_map(function($c) { return "`$c`"; }, $fk['ref_cols']));
                        
                        $addFk = "ALTER TABLE `{$fk['table']}` ADD CONSTRAINT `{$fkName}` ";
                        $addFk .= "FOREIGN KEY ($cols) REFERENCES `{$fk['ref_table']}` ($refCols);\n";
                    } else {
                        $cols = implode(", ", array_map(function($c) { return "[$c]"; }, $fk['cols']));
                        $refCols = implode(", ", array_map(function($c) { return "[$c]"; }, $fk['ref_cols']));
                        
                        $addFk = "ALTER TABLE [{$fk['schema']}].[{$fk['table']}] ADD CONSTRAINT [$fkName] ";
                        $addFk .= "FOREIGN KEY ($cols) REFERENCES [{$fk['ref_schema']}].[{$fk['ref_table']}] ($refCols);\n";
                    }
                    fwrite($fh, $addFk);
                }
            }
            
            if ($dialect === 'mysql') {
                fwrite($fh, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
            }
            
            fclose($fh);
            
            // Rename temp file to final file
            $finalFile = __DIR__ . '/' . getSafeFileName(DB_DATABASE, '', $dialect);
            rename($tempFile, $finalFile);
            
            $_SESSION['final_backup_file'] = $finalFile;
            
            $sizeBytes = filesize($finalFile);
            $sizeStr = ($sizeBytes > 1024*1024) ? round($sizeBytes/(1024*1024), 2) . ' MB' : round($sizeBytes/1024, 2) . ' KB';
            
            echo json_encode([
                'success' => true,
                'filename' => basename($finalFile),
                'filesize' => $sizeStr,
                'downloadUrl' => '?download=' . urlencode(basename($finalFile))
            ]);
            
            unset($_SESSION['temp_backup_file']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// --- Download Handler ---
if (isset($_GET['download']) && $authenticated) {
    $filename = basename($_GET['download']);
    $filePath = __DIR__ . '/' . $filename;
    
    // Safety check: ensure file is indeed a backup and belongs to this folder
    if (file_exists($filePath) && strpos($filename, 'backup_') === 0 && substr($filename, -4) === '.sql') {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        
        // Optionally delete file after download to keep server clean
        // unlink($filePath);
        exit;
    } else {
        die("Invalid file request.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Server Exporter | OVInfinity</title>
    <!-- Google Fonts Outfit and JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(17, 24, 39, 0.65);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --accent-primary: #6366f1;
            --accent-secondary: #a855f7;
            --accent-glow: rgba(99, 102, 241, 0.15);
            --success: #10b981;
            --error: #ef4444;
            --terminal-bg: #05070c;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.1) 0%, transparent 40%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            line-height: 1.5;
        }

        .container {
            width: 100%;
            max-width: 800px;
            padding: 2rem;
            z-index: 10;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
        }

        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            background: linear-gradient(135deg, #fff 40%, var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 300;
        }

        .db-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .status-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .status-item:hover {
            border-color: rgba(99, 102, 241, 0.3);
            background: rgba(99, 102, 241, 0.02);
            transform: translateY(-2px);
        }

        .status-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .status-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .status-value.connected {
            color: var(--success);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-value.connected::before {
            content: '';
            width: 8px;
            height: 8px;
            background-color: var(--success);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px var(--success);
            animation: pulse 1.5s infinite;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        select, input[type="password"] {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
        }

        select:focus, input[type="password"]:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 10px var(--accent-glow);
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            border: none;
            font-family: inherit;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: #fff;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
            opacity: 0.95;
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: #fff;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.25);
            display: none;
            margin-top: 1.5rem;
            animation: fadeIn 0.5s ease-out;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
        }

        /* Console Terminal UI */
        .console-container {
            margin-top: 2rem;
            display: none;
            animation: slideDown 0.4s ease-out;
        }

        .console-header {
            background: #111827;
            border: 1px solid var(--card-border);
            border-bottom: none;
            border-radius: 12px 12px 0 0;
            padding: 0.65rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .console-dot {
            display: flex;
            gap: 0.4rem;
        }

        .console-dot span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .console-dot span:nth-child(1) { background: #ef4444; }
        .console-dot span:nth-child(2) { background: #f59e0b; }
        .console-dot span:nth-child(3) { background: #10b981; }

        .console-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .console {
            background-color: var(--terminal-bg);
            border: 1px solid var(--card-border);
            border-radius: 0 0 12px 12px;
            padding: 1.2rem;
            height: 220px;
            overflow-y: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            line-height: 1.6;
            color: #d1d5db;
            scroll-behavior: smooth;
        }

        .console::-webkit-scrollbar {
            width: 6px;
        }

        .console::-webkit-scrollbar-track {
            background: transparent;
        }

        .console::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .console .log-entry {
            margin-bottom: 0.25rem;
        }

        .console .log-time {
            color: #4b5563;
            margin-right: 0.5rem;
        }

        .console .log-success { color: var(--success); }
        .console .log-error { color: var(--error); }
        .console .log-info { color: #3b82f6; }

        /* Progress Bar */
        .progress-container {
            margin-top: 1rem;
            display: none;
            animation: fadeIn 0.4s ease-in;
        }

        .progress-bar-track {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
            border-radius: 3px;
            transition: width 0.3s ease;
            box-shadow: 0 0 8px var(--accent-primary);
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
        }

        /* Login Layout */
        .login-card {
            max-width: 420px;
            width: 100%;
        }

        .logout-link {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .logout-link:hover {
            opacity: 1;
            color: var(--error);
        }

        /* Animations */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.6; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="container">
        <?php if (!$authenticated): ?>
            <!-- Login Gate -->
            <div class="card login-card" style="margin: 0 auto;">
                <div class="header">
                    <h1>OVI Exporter Gate</h1>
                    <p>Enter the master security password</p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">Security Password</label>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                        <?php if (isset($login_error)): ?>
                            <p style="color: var(--error); font-size: 0.85rem; margin-top: 0.5rem;"><?php echo $login_error; ?></p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Verify & Unlock</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Dashboard -->
            <div class="card">
                <a href="?logout=1" class="logout-link">Logout</a>
                
                <div class="header">
                    <h1>SQL Server Database Exporter</h1>
                    <p>Streamlined backup utility for production databases</p>
                </div>

                <div class="db-status">
                    <div class="status-item">
                        <div class="status-label">Database Host</div>
                        <div class="status-value"><?php echo DB_SERVER; ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Initial Catalog</div>
                        <div class="status-value"><?php echo DB_DATABASE; ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Connection Status</div>
                        <div class="status-value connected">Online</div>
                    </div>
                </div>

                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label for="export_type">Backup Mode</label>
                        <select id="export_type">
                            <option value="both">Schema & Data</option>
                            <option value="schema">Schema Only</option>
                            <option value="data">Data Only</option>
                        </select>
                    </div>
                    <div>
                        <label for="export_dialect">Target SQL Format (Dialect)</label>
                        <select id="export_dialect">
                            <option value="mssql">Microsoft SQL Server (T-SQL)</option>
                            <option value="mysql">MySQL / MariaDB</option>
                        </select>
                    </div>
                </div>

                <button id="start-btn" onclick="startExport()" class="btn btn-primary">Start Database Export</button>

                <!-- Progress Tracker -->
                <div class="progress-container" id="progress-container">
                    <div class="progress-text">
                        <span id="progress-status">Preparing export...</span>
                        <span id="progress-percent">0%</span>
                    </div>
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill" id="progress-bar-fill"></div>
                    </div>
                </div>

                <!-- Console Output Log -->
                <div class="console-container" id="console-container">
                    <div class="console-header">
                        <div class="console-dot">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="console-title">Live Export Session Log</div>
                    </div>
                    <div class="console" id="console"></div>
                </div>

                <!-- Download Link -->
                <a href="#" id="download-btn" class="btn btn-success">Download Exported SQL File</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($authenticated): ?>
        <script>
            let tables = [];
            let currentIndex = 0;
            let exportType = 'both';
            let exportDialect = 'mssql';

            function getFormattedTime() {
                const now = new Date();
                return now.toTimeString().split(' ')[0];
            }

            function log(message, type = '') {
                const consoleDiv = document.getElementById('console');
                const entry = document.createElement('div');
                entry.className = 'log-entry';
                
                const timeSpan = document.createElement('span');
                timeSpan.className = 'log-time';
                timeSpan.innerText = `[${getFormattedTime()}]`;
                
                const contentSpan = document.createElement('span');
                if (type) {
                    contentSpan.className = `log-${type}`;
                }
                contentSpan.innerText = message;
                
                entry.appendChild(timeSpan);
                entry.appendChild(contentSpan);
                consoleDiv.appendChild(entry);
                consoleDiv.scrollTop = consoleDiv.scrollHeight;
            }

            async function startExport() {
                document.getElementById('start-btn').disabled = true;
                document.getElementById('download-btn').style.display = 'none';
                
                // Show tracking widgets
                document.getElementById('progress-container').style.display = 'block';
                document.getElementById('console-container').style.display = 'block';
                
                const consoleDiv = document.getElementById('console');
                consoleDiv.innerHTML = ''; // Clear previous logs
                
                exportType = document.getElementById('export_type').value;
                exportDialect = document.getElementById('export_dialect').value;
                currentIndex = 0;
                tables = [];
                
                updateProgress(0, 'Connecting & scanning database metadata...');
                log(`Initializing backup session (Dialect: ${exportDialect.toUpperCase()})...`, 'info');

                try {
                    const res = await fetch(`?ajax=init&type=${exportType}&dialect=${exportDialect}`);
                    if (!res.ok) throw new Error('Initialization request failed.');
                    const data = await res.json();
                    
                    if (!data.success) {
                        log(`Initialization Error: ${data.message}`, 'error');
                        document.getElementById('start-btn').disabled = false;
                        return;
                    }
                    
                    tables = data.tables;
                    log(`Metadata loaded. Found ${tables.length} tables to process.`, 'success');
                    
                    if (tables.length === 0) {
                        log('No tables found in database.', 'info');
                        finalizeExport();
                        return;
                    }
                    
                    exportNextTable();
                } catch (err) {
                    log(`Error starting export: ${err.message}`, 'error');
                    document.getElementById('start-btn').disabled = false;
                }
            }

            async function exportNextTable() {
                if (currentIndex >= tables.length) {
                    finalizeExport();
                    return;
                }
                
                const table = tables[currentIndex];
                const pct = Math.round((currentIndex / tables.length) * 90); // 90% is data export, 10% is finalization
                
                const displayName = exportDialect === 'mysql' ? `\`${table.name}\`` : `[${table.schema}].[${table.name}]`;
                updateProgress(pct, `Exporting ${displayName}...`);
                log(`Processing Table ${currentIndex + 1} of ${tables.length}: ${displayName}...`);
                
                try {
                    const url = `?ajax=export&schema=${encodeURIComponent(table.schema)}&table=${encodeURIComponent(table.name)}`;
                    const res = await fetch(url);
                    if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
                    const data = await res.json();
                    
                    if (data.success) {
                        log(`Successfully exported ${displayName}: Schema created, ${data.rows} rows exported.`, 'success');
                    } else {
                        log(`Warning exporting ${displayName}: ${data.message}`, 'error');
                    }
                } catch (err) {
                    log(`Failed to export ${displayName}: ${err.message}`, 'error');
                }
                
                currentIndex++;
                setTimeout(exportNextTable, 50); // Small delay to allow UI rendering thread to breath
            }

            async function finalizeExport() {
                updateProgress(95, 'Writing constraints and finalizing SQL script...');
                log('Finalizing constraints, building indexes, and writing keys...', 'info');
                
                try {
                    const res = await fetch('?ajax=finalize');
                    if (!res.ok) throw new Error('Finalization request failed.');
                    const data = await res.json();
                    
                    if (!data.success) {
                        log(`Finalization Error: ${data.message}`, 'error');
                        document.getElementById('start-btn').disabled = false;
                        return;
                    }
                    
                    updateProgress(100, 'Backup generated successfully!');
                    log('==================================================', 'info');
                    log(`Backup File: ${data.filename}`, 'success');
                    log(`Backup File Size: ${data.filesize}`, 'success');
                    log('==================================================', 'success');
                    
                    const downloadBtn = document.getElementById('download-btn');
                    downloadBtn.href = data.downloadUrl;
                    downloadBtn.style.display = 'inline-flex';
                    
                } catch (err) {
                    log(`Error finalizing export: ${err.message}`, 'error');
                } finally {
                    document.getElementById('start-btn').disabled = false;
                }
            }

            function updateProgress(percentage, statusText) {
                document.getElementById('progress-bar-fill').style.width = `${percentage}%`;
                document.getElementById('progress-percent').innerText = `${percentage}%`;
                document.getElementById('progress-status').innerText = statusText;
            }
        </script>
    <?php endif; ?>

</body>
</html>
