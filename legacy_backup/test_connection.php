<?php
// test_connection.php

// 1. Connection Details
$server = 'ovinfinity.in';
$database = 'humckbre_ProductionDB';
$username = 'Ovidata';
$password = '0cG9_3c1l';

echo "--- Connection Details parsed ---\n";
echo "Server:   $server\n";
echo "Database: $database\n";
echo "Username: $username\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n\n";

// 2. Check available extensions
echo "--- Checking available PHP drivers ---\n";
$has_pdo = class_exists('PDO');
$pdo_drivers = $has_pdo ? PDO::getAvailableDrivers() : [];
$has_sqlsrv_pdo = in_array('sqlsrv', $pdo_drivers);
$has_dblib_pdo = in_array('dblib', $pdo_drivers);
$has_odbc_pdo = in_array('odbc', $pdo_drivers);
$has_sqlsrv_native = function_exists('sqlsrv_connect');

echo "PDO Installed: " . ($has_pdo ? "Yes" : "No") . "\n";
echo "PDO Drivers: " . implode(', ', $pdo_drivers) . "\n";
echo "PDO sqlsrv: " . ($has_sqlsrv_pdo ? "Available" : "Not Available") . "\n";
echo "PDO dblib: " . ($has_dblib_pdo ? "Available" : "Not Available") . "\n";
echo "PDO odbc: " . ($has_odbc_pdo ? "Available" : "Not Available") . "\n";
echo "sqlsrv native functions: " . ($has_sqlsrv_native ? "Available" : "Not Available") . "\n\n";

// 3. Attempt Connection
echo "--- Attempting Connection ---\n";

$connected = false;
$errors = [];

// Try PDO sqlsrv
if ($has_sqlsrv_pdo) {
    try {
        echo "Trying PDO sqlsrv...\n";
        $dsn = "sqlsrv:Server=$server;Database=$database";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 5
        ]);
        echo "SUCCESS: Connected via PDO sqlsrv!\n";
        $connected = true;
    } catch (Exception $e) {
        $errors['PDO sqlsrv'] = $e->getMessage();
    }
}

// Try PDO dblib
if (!$connected && $has_dblib_pdo) {
    try {
        echo "Trying PDO dblib...\n";
        $dsn = "dblib:host=$server;dbname=$database;timeout=5";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "SUCCESS: Connected via PDO dblib!\n";
        $connected = true;
    } catch (Exception $e) {
        $errors['PDO dblib'] = $e->getMessage();
    }
}

// Try PDO odbc
if (!$connected && $has_odbc_pdo) {
    try {
        echo "Trying PDO odbc...\n";
        $dsn = "odbc:Driver={SQL Server};Server=$server;Database=$database;";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "SUCCESS: Connected via PDO odbc!\n";
        $connected = true;
    } catch (Exception $e) {
        $errors['PDO odbc'] = $e->getMessage();
    }
}

// Try native sqlsrv
if (!$connected && $has_sqlsrv_native) {
    echo "Trying native sqlsrv_connect...\n";
    $connectionInfo = array(
        "Database" => $database,
        "UID" => $username,
        "PWD" => $password,
        "LoginTimeout" => 5
    );
    $conn = sqlsrv_connect($server, $connectionInfo);
    if ($conn) {
        echo "SUCCESS: Connected via native sqlsrv_connect!\n";
        $connected = true;
        sqlsrv_close($conn);
    } else {
        $errs = sqlsrv_errors();
        $errors['Native sqlsrv'] = print_r($errs, true);
    }
}

if ($connected) {
    echo "\nConnection Test: PASSED! You can query this database.\n";
} else {
    echo "\nConnection Test: FAILED.\n";
    if (empty($errors)) {
        echo "No compatible SQL Server drivers are enabled in your current PHP environment.\n";
        echo "Please enable the SQLSRV extension, configure PDO ODBC, or run this script in an environment (like IIS or Plesk) where SQL Server drivers are installed.\n";
    } else {
        echo "Error details:\n";
        foreach ($errors as $driver => $msg) {
            echo "[$driver]: $msg\n";
        }
    }
}
