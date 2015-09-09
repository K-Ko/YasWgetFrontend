<?php if (!defined('APPNAME')) return; ?>

<!-- Header -->
<div id="header">
    <h1 style="float:left;font-style:italic">
        <a href="/" title="Reload page"><img src="favicon.ico" style="width:24px;height:24px"></a>
        <?php echo APPNAME; ?>
    </h1>

    <form style="float:right;text-align:right">
        <input type="submit" value="Reload page">
    </form>

    <form method="post" style="float:right;text-align:right">
        <input id="refresh" type="submit" value="Auto refresh each" style="float:left;display:none">
        <div id="time" style="float:left;margin-top:1px;display:none">
            <input type="text" name="refresh" value="<?php echo $_SESSION['refresh'] ?: 60; ?>" style="text-align:right" size="1"> s &nbsp;
        </div>
        <input id="stop" type="submit" name="off" value="Stop refresh" style="display:none">
    </form>
</div>
