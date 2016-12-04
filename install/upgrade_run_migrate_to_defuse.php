<?php
/**
 * @file          upgrade.ajax.php
 * @author        Nils Laumaillé
 * @version       2.1.27
 * @copyright     (c) 2009-2016 Nils Laumaillé
 * @licensing     GNU AFFERO GPL 3.0
 * @link          http://www.teampass.net
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/*
** Upgrade script for release 2.1.26
*/
require_once('../sources/SecureHandler.php');
session_start();
error_reporting(E_ERROR | E_PARSE);
$_SESSION['db_encoding'] = "utf8";
$_SESSION['CPM'] = 1;

require_once '../includes/language/english.php';
require_once '../includes/config/include.php';
require_once '../includes/config/settings.php';
require_once '../sources/main.functions.php';

$_SESSION['settings']['loaded'] = "";

$finish = false;
$next = ($_POST['nb'] + $_POST['start']);

$dbTmp = mysqli_connect(
    $_SESSION['db_host'],
    $_SESSION['db_login'],
    $_SESSION['db_pw'],
    $_SESSION['db_bdd'],
    $_SESSION['db_port']
);


fputs($dbgDuo, "\n\nSELECT id, pw, pw_iv FROM ".$_SESSION['tbl_prefix']."items
    WHERE perso = '0' LIMIT ".$_POST['start'].", ".$_POST['nb']."\n");

// get total items
$rows = mysqli_query($dbTmp,
    "SELECT id, pw, pw_iv FROM ".$_SESSION['tbl_prefix']."items
    WHERE perso = '0'"
);
if (!$rows) {
    echo '[{"finish":"1" , "error":"'.mysqli_error($dbTmp).'"}]';
    exit();
}

$total = mysqli_num_rows($rows);

// loop on items
$rows = mysqli_query($dbTmp,
    "SELECT id, pw, pw_iv, encryption_type FROM ".$_SESSION['tbl_prefix']."items
    WHERE perso = '0' LIMIT ".$_POST['start'].", ".$_POST['nb']
);
if (!$rows) {
    echo '[{"finish":"1" , "error":"'.mysqli_error($dbTmp).'"}]';
    exit();
}

while ($data = mysqli_fetch_array($rows)) {
    if ($data['encryption_type'] !== "defuse") {
        // decrypt with phpCrypt
        $old_pw = cryption_phpCrypt(
            $data['pw'],
            SALT,
            $data['pw_iv'],
            "decrypt"
        );

        // encrypt with Defuse
        $new_pw = cryption(
            $old_pw['string'],
            "",
            $_SESSION['new_salt'],
            "encrypt"
        );
        
        // store Password
        mysqli_query($dbTmp,
            "UPDATE ".$_SESSION['tbl_prefix']."items
            SET pw = '".$new_pw['string']."', pw_iv = '', encryption_type = 'defuse'
            WHERE id = ".$data['id']
        );
    }
}

if ($next >= $total) {
    $finish = 1;
}


echo '[{"finish":"'.$finish.'" , "next":"'.$next.'", "error":""}]';