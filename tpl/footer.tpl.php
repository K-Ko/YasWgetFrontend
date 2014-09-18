<?php if (!defined('APPNAME')) return; ?>

<!-- Footer -->
<div id="footer">
<small>
    <a href="https://github.com/K-Ko/YasWgetFrontend">
        <?php echo APPSHORT, ' v', APPVERSION; ?> on
        <img src="images/Octocat.png" style="height:16px">
        GitHub
    </a>
</small>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js"></script>

<script>

$(function() {

    $('#header, #footer').addClass('ui-widget-header').addClass('ui-corner-all');
    $('legend').addClass('ui-state-default').addClass('ui-corner-all');
    $('fieldset, input[type=text]').addClass('ui-corner-all');
    $('input[type=submit]').button();
    $('input[type=submit]').each(function(i, el) {
        $(el).button('option', 'disabled', !!$(el).data('disable'));
    });

    <?php if ($_SESSION['refresh']): ?>
        $('#stop').show();
    <?php elseif (count($processes)): ?>
        $('#time, #refresh').show();
    <?php endif; ?>

});

</script>

</body>
</html>
