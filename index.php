<?php
/**
 *
 *
 * @author     Knut Kohl <github@knutkohl.de>
 * @copyright  (c) 2014 Knut Kohl
 * @licence    MIT License - http://opensource.org/licenses/MIT
 * @version    1.0.0
 */

define('APPNAME',    'Yet another simple Wget frontend');
define('APPVERSION', '1.1.1');

ini_set('display_errors', 0);
error_reporting(0);

/**
 * Load settings
 */
include 'config.default.php';
// Custom settings?!
if (file_exists('config.php')) include 'config.php';

/**
 * Definitions
 */
define('DS',         DIRECTORY_SEPARATOR);
// Make hidden server unique directory name to store files
define('F_DIR',      '.f'.substr(md5(__DIR__), -11));
define('FILES_DIR',  __DIR__.DS.F_DIR);
define('LOG_DIR',    FILES_DIR.DS.'.log');

/**
 * Check directories
 */
is_writable(FILES_DIR) || mkdir(FILES_DIR, 0700);
is_writable(LOG_DIR)   || mkdir(LOG_DIR,  0700);

if (!is_writable(FILES_DIR)) {
    die('Can\'t create/write to "'.FILES_DIR.'", please check permissions.');
}

session_start();

if (array_key_exists('refresh', $_GET)) {
    if (isset($_GET['off'])) {
        $_SESSION['refresh'] = FALSE;
    } else {
        $_SESSION['refresh'] = is_numeric($_GET['refresh']) ? $_GET['refresh'] : FALSE;
    }
}

if (!array_key_exists('refresh', $_SESSION)) $_SESSION['refresh'] = FALSE;

echo '
<html>
<head>
    '.($_SESSION['refresh'] ? '<meta http-equiv="refresh" content="'.$_SESSION['refresh'].'; URL='.$_SERVER['DOCUMENT_URI'].'">' : '').'
    <title>',APPNAME,' v',APPVERSION,'</title>
    <style>
        body { font-family: Verdana, Helvetia, sans-serif; font-size: 1em; max-width: 1200px; margin:0 auto }
        h1 { font-size: 125% }
        h2 { font-size: 115% }
        form { display: inline }
        input { font-size: 1em }
        input[type=text] { padding: 5px }
        hr { height: 0; border-top: dashed gray 1px }
        /* Adjust fixed width output */
        tt, pre { font-size: 110% }
        /* Processes table */
        #processes { width: 100% }
        #processes th, #processes td { padding: .25em 1em .25em 0 }
        #processes th { text-align: left }
        #processes td { font-family: monospace, font-size: 120% }
        #add td { padding: .5em 1em .5em 0 }
    </style>
</head>
<body>

<!-- Header -->
<form style="float:right;text-align:right">
    <input style="text-align:right" type="text" name="refresh" value="'.($_SESSION['refresh'] ?: 10).'" size="1">s
    <input type="submit" value="Auto refresh">
    <input type="submit" name="off" value="Stop">
</form>

<h1>',APPNAME,'</h1>
<hr style="clear:both" />';

if (isset($_GET['get'])) {
    // Download file
    $file = FILES_DIR.DS.$_GET['get'];
    if (file_exists($file)) {
        // Headers for direct download
        Header('Content-Description: File Transfer');
        Header('Content-Type: application/octet-stream');
        Header('Content-Disposition: attachment; filename="'.basename($file).'"');
        Header('Content-Transfer-Encoding: binary');
        Header('Connection: Keep-Alive');
        Header('Expires: 0');
        Header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        Header('Pragma: public');
        Header('Content-Length: '.filesize($file));
        readfile($file);
        exit;
    } else {
        die('File "'.$_GET['get'].'" unknown!');
    }

} elseif (!empty($_POST)) {

    if (isset($_POST['kill']) AND isset($_POST['confirmed']) AND $_POST['confirmed'] == 'X') {
        // Stop wget process

        exec('kill '.$_POST['kill']);

    } elseif (isset($_POST['delete']) AND isset($_POST['confirmed']) AND $_POST['confirmed'] == 'X') {
        // Delete file (and log)

        $file = FILES_DIR.DS.$_POST['delete'];
        file_exists($file) && unlink($file);

        $file = LOG_DIR.DS.$_POST['delete'];
        file_exists($file) && unlink($file);

    } elseif (!empty($_POST['url'])) {
        // Add new file to queue

        if (!empty($_POST['name'])) {
            $name = $_POST['name'];
        } else {
            $parsed = parse_url($_POST['url']);
            $name = basename($parsed['path']);
        }
        $url   = escapeshellarg($_POST['url']);
        $file  = escapeshellarg(FILES_DIR.DS.$name);
        $log   = escapeshellarg(LOG_DIR.DS.$name);
        $cmd   = $wget.' '.$wget_options
               . (!empty($_POST['limit']) ? ' --limit-rate='.$_POST['limit'] : '')
               . ' --background --random-wait --progress=bar:force '
               . ' -O '.$file.' -o '.$log.' '.$url;

        #echo '<p>Run: '.$cmd.'</p><hr />';
        exec($cmd);
        Header('Location: '.$_SERVER['DOCUMENT_URI']);
        exit;
    }
}

/**
 * Active downloads
 */
exec('ps ax | grep wget | grep "[/]'.F_DIR.'"', $ps);

if (!empty($ps)) {
    echo '<h2>Active downloads</h2>';

    foreach ($ps as $process) {
        if (preg_match('~^\s*(\d+).* ([^\s]+)$~', $process, $args)) {
            echo '<p><form method="post">',
                 '<input type="hidden" name="kill" value="'.$args[1].'">',
                 '<input type="submit" name="confirmed" value="X" title="Stop process">',
                 '</form> &nbsp; ',
                 '<tt>'.$args[2].'</tt></p>';
        }
    }
    echo '<hr />';
}

$files = glob(LOG_DIR.DS.'*');

if (!empty($files)) {

  echo '
<!-- Files list -->
<h2>Files</h2>

<table id="processes">
<thead>
<tr>
    <th>File</th>
    <th>Progress</th>
    <th>Delete</th>
</tr>
</thead>
<tbody>';

  $tr = '
<tr>
    <td><a href="/?get=%1$s" title="Download">%2$s</a></td>
    <td><pre>%3$s</pre></td>
    <td>
        <form method="post">
            <input type="hidden" name="delete" value="%1$s">
            <input type="submit" name="confirmed" value="X" title="Delete file and log">
        </form>
    </td>
</tr>'.PHP_EOL;

    foreach ($files as $file) {
        unset($last);
        exec('sed -e "s~\r~\n~g" '.$file.' | tail -n 1 ', $last);
        printf($tr, urlencode(basename($file)), basename($file), $last[0]);
    }

    echo '
</tbody></table>

<p><div style="text-align:center"><form><input type="submit" value="Reload page"></form></div></p>

<hr />';

}

echo '
<!-- Add file form -->
<h2>Add file to queue</h2>

<form method="post">

<table id="add">
<tr>
    <td>URL: </td>
    <td><input type="text" name="url" size="120" required="required"></td>
    <td><small>(required)</small></td>
</tr>
<tr>
    <td>Target file name:</td>
    <td><input type="text" name="name" size="60"</td>
    <td></td>
</tr>
<tr>
    <td>Limit download speed:</td>
    <td>
        <input type="text" name="limit" size="5">
        <small>Amount may be expressed in bytes, kilobytes with the "k" suffix, or megabytes with the "m" suffix.</small>
    </td>
    <td></td>
</tr>
<tr>
    <td></td>
    <td><input type="submit" value="Start"></td>
    <td></td>
</tr>
</table>

</form>

<!-- Footer -->
<hr />

<small><a href="https://github.com/K-Ko/YasWgetFrontend">'.APPNAME.' (v'.APPVERSION.') on GitHub</a></small>
</body>
</html>
';
