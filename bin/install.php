#!/usr/bin/php
<?php

namespace CB;

/**
 * install CaseBox script designed to help first configuration of casebox
 *
 * Requirements:
 *     on Windows platform path to mysql/bin should be added to "Path" environment variable
 */

echo "Notice: on Windows platform path to mysql/bin should be added to \"Path\" environment variable.\n\n";

$cfg = array();

// we include config_platform het will load config.ini if exist and will define $cfg variable
// If config.ini doesnt exist it wil raise an exception: Can't load config file

try {
    require_once '../httpsdocs/config_platform.php';
} catch (\Exception $e) {
    //config.ini could not exist

    //we don't need to do anything here because this script will create confing.ini in result
    //we just use values form config.ini as defaults, if it exists
}

require_once 'install_functions.php';

// initialize default values in cofig if not detected
$defaultValues = array(
   'prefix' => 'cb'
    ,'db_host' => 'localhost'
    ,'db_port' => '3306'
    ,'db_user' => 'local'
    ,'db_pass' => ''

    ,'solr_home' => '/var/solr/data/'
    ,'solr_host' => '127.0.0.1'
    ,'solr_port' => '8983'

    ,'session.lifetime' => '180'

    //;ADMIN_EMAIL: email adress used to notify admin on any casebox problems
    ,'admin_email' => 'your.email@server.com'
    //;SENDER_EMAIL: email adress placed in header for sent mails
    ,'sender_email' => 'emails.sender@server.com'

    ,'webdav_domain' => 'https://subdomain.domain.org/'

    ,'comments_email' => 'comments@subdomain.domain.com'
    ,'comments_host' => '127.0.0.1'
    ,'comments_port' => 993
    ,'comments_ssl' => true
    ,'comments_user' => ''
    ,'comments_pass' => ''

    ,'PYTHON' => 'python'
);

$cfg = $cfg + $defaultValues;

//init prefix
$l = readALine('Specify prefix used for database names, solr core and log files (default "' . $cfg['prefix'] . '"):' . "\n");
if (!empty($l)) {
    $cfg['prefix'] = $l;
}

//init db config
do {
    initDBConfig();
} while (!verifyDBConfig());

//init solr connection
initSolrConfig();

$l = readALine('Specify administrator email address (default "' . $cfg['admin_email'] . '"):' . "\n");
if (!empty($l)) {
    $cfg['admin_email'] = $l;
}

$l = readALine('Specify sender email address, placed in header for sent mails (default "' . $cfg['sender_email'] . '"):' . "\n");
if (!empty($l)) {
    $cfg['sender_email'] = $l;
}
//check webdav domain
$l = readALine('Specify WebDav domain, including protocol (default "' . $cfg['webdav_domain'] . '"):' . "\n");
if (!empty($l)) {
    $cfg['webdav_domain'] = $l;
}

//define comments email params
if (confirm('Would you like to define comments email parametters? (y/n)')) {
    $l = readALine('Specify comments email address, used to receive replies for Casebox comment notifications (default "' . $cfg['comments_email'] . '"):' . "\n");
    if (!empty($l)) {
        $cfg['comments_email'] = $l;
    }

    $l = readALine('Specify comments email server host (default "' . $cfg['comments_host'] . '"):' . "\n");
    if (!empty($l)) {
        $cfg['comments_host'] = $l;
    }

    $l = readALine('Specify comments email server port (default "' . $cfg['comments_port'] . '"):' . "\n");
    if (!empty($l)) {
        $cfg['comments_port'] = $l;
    }

    $l = readALine('Specify if ssl connection is used for comments email server (y/n):' . "\n");
    if (!empty($l)) {
        $cfg['comments_ssl'] = $l;
    }

    $l = readALine('Specify username for comments email server connection (can be left blank if email could be used as username):' . "\n");
    if (!empty($l)) {
        $cfg['comments_user'] = $l;
    }

    $l = readALine('Specify password for comments email server connection:' . "\n");
    if (!empty($l)) {
        $cfg['comments_pass'] = $l;
    }
}

$l = readALine('Specify python path (default "' . $cfg['PYTHON'] . '"):' . "\n");
if (!empty($l)) {
    $cfg['PYTHON'] = $l;
}

echo "\nYou have configured main options for casebox.\n".
    "Saving your settings to casebox.ini ... ";

backupFile(DOC_ROOT . 'config.ini');

do {

    $r = putIniFile(
        DOC_ROOT . 'config.ini',
        array_intersect_key($cfg, $defaultValues)
    );

    if ($r === false) {
        $r = !confirm(' error saving to config.ini file. retry (y/n)?:');
    } else {
        echo "Ok\n\n";
    }
} while ($r === false);

//creating solr symlinks
$solrCSPath = $cfg['solr_home'] . 'configsets' . DIRECTORY_SEPARATOR;
$CBCSPath = SYS_DIR . 'solr_configsets' . DIRECTORY_SEPARATOR;
$r = true;
if (file_exists($solrCSPath . 'cb_default')) {
    $r = @symlink($CBCSPath . 'default_config' . DIRECTORY_SEPARATOR, $solrCSPath . 'cb_default');
}
if (file_exists($solrCSPath . 'cb_log')) {
    $r = $r && @symlink($CBCSPath . 'log_config' . DIRECTORY_SEPARATOR, $solrCSPath . 'cb_log');
}

if ($r) {
    echo "Solr configsets symlinks created sucessfully.\n\r";
} else {
    echo "Error creating symlinks to solr configsets.\n\r";
}

//try to create cb_log core
$solr = Solr\Service::verifyConfigConnection(
    array(
        'host' => $cfg['solr_host']
        ,'port' => $cfg['solr_port']
        ,'core' => 'cb_log'
        ,'SOLR_CLIENT' => $cfg['SOLR_CLIENT']
    )
);

if ($solr === false) {
    if (confirm('Solr core "cb_log" doesnt exist. Would you like to create it? (y/n): ')) {
        echo 'Creating solr core ... ';

        $h = fopen(
            'http://' . $cfg['solr_host']. ':' . $cfg['solr_port'] . '/solr/admin/cores?action=CREATE&' .
            'name=cb_log&configSet=cb_log',
            'r'
        );

        fclose($h);

        echo "Ok\n";
    }
} else {
    echo "cb_log solr core already exists.\n\r";
}

//create default database (<prefix>__casebox)
$cbDb = $cfg['prefix'] . '__casebox';

$r = DB\dbQuery('use `' . $cbDb . '`');
if ($r) {
    if (confirm("'$cbDb' database exists. Would you like to backup it and overwrite with dump from current installation? (y/n): ")) {
        echo 'Backuping .. ';
        backupDB($cbDb, $cfg['db_user'], $cfg['db_pass']);
        echo "Ok\n";

        echo 'Applying dump .. ';
        exec('mysql --user=' . $cfg['db_user'] . ' --password=' . $cfg['db_pass'] . ' ' . $cbDb . ' < ' . APP_DIR . 'install/mysql/_casebox.sql');
        echo "Ok\n";
    }
} else {
    if (confirm("$cbDb database does not exist. Would you like to create it from current installation dump file? (y/n): ")) {
        if (DB\dbQuery('CREATE DATABASE `' . $cbDb . '` CHARACTER SET utf8 COLLATE utf8_general_ci')) {
            exec('mysql --user=' . $cfg['db_user'] . ' --password=' . $cfg['db_pass'] . ' ' . $cbDb . ' < ' . APP_DIR . 'install/mysql/_casebox.sql');
        } else {
            echo 'Cant create database "' . $cbDb . '".';
        }
    }
}