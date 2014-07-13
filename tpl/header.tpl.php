<?php if (!defined('APPNAME')) return; ?>

<!-- Header -->
<div id="header">
    <h1 style="float:left;font-style:italic"><?php echo APPNAME; ?></h1>

    <form style="float:right;text-align:right">
        <input type="submit" value="Reload page">
    </form>

    <form method="post" style="float:right;text-align:right">
        <input id="refresh" type="submit" value="Auto refresh each" style="float:left">
        <div id="time" style="float:left;margin-top:1px">
            <input type="text" name="refresh" value="<?php echo $_SESSION['refresh'] ?: 60; ?>" style="text-align:right" size="1"> s &nbsp;
        </div>
        <input id="stop" type="submit" name="off" value="Stop refresh">
    </form>
</div>

<div style="clear:both"></div>

