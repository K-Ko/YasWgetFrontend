<?php if (!defined('APPNAME')) return; ?>

<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8"></meta>
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible"></meta>

    <title><?php echo APPSHORT, ' v', APPVERSION, ' (', count($processes) ,'/' ,count($files)  ,')'; ?></title>

    <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css" />
    <link rel="stylesheet" href="css/normalize.css" />
    <link rel="stylesheet" href="css/style.css" />

    <?php if ($_SESSION['refresh']) echo '<meta http-equiv="refresh" content="'.$_SESSION['refresh'].'; URL='.$_SERVER['DOCUMENT_URI'].'">'; ?>
</head>
<body>

