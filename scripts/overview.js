$(function() {
    $("#excludemanual").on('change', function(e) {
        // TODO - change to AMD's URL (.relativeUrl('/report/coursecatcounts/overview.php'))
        if ($(this).is(":checked")) {
            window.location = M.cfg.wwwroot + '/report/coursecatcounts/overview.php?excludemanual=1';
        } else {
            window.location = M.cfg.wwwroot + '/report/coursecatcounts/overview.php?excludemanual=0';
        }
    });
});
