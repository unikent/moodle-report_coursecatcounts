M.report_categories = {
    Y : null,
    transaction : [],

    init: function (Y, startdate, enddate) {
        Y.all('.datacell').on('click', function (e) {
            M.report_categories.cellClick(Y, e.target, startdate, enddate);
        });
    },

    cellClick : function(Y, cell, startdate, enddate) {
        var catid = cell.getAttribute("catid");
        var ctype = cell.getAttribute("column");

        if (!catid || !ctype) {
            return;
        }

        var dialog = new Y.Panel({
            contentBox : Y.Node.create('<div id="dialog" />'),
            bodyContent: '<div id="modalmessage"></div>',
            width      : 410,
            zIndex     : 6,
            centered   : true,
            modal      : true,
            render     : '.moduleDialog',
            visible    : true,
            buttons    : {
                footer: [
                    {
                        name     : 'close',
                        label    : 'Close',
                        action   : 'onOK'
                    }
                ]
            }
        });

        dialog.onOK = function (e) {
            e.preventDefault();
            this.hide();
        }

        var box = Y.one('#modalmessage');
        var spinner = M.util.add_spinner(Y, box);
        spinner.show();

        Y.io(M.cfg.wwwroot + "/report/coursecatcounts/ajax/categories.php", {
            timeout: 120000,
            method: "GET",
            data: {
                sesskey: M.cfg.sesskey,
                catid: catid,
                ctype: ctype,
                startdate: startdate,
                enddate: enddate
            },
            on: {
                success: function (x, o) {
                    spinner.hide();

                    try {
                        var data = Y.JSON.parse(o.responseText);
                        box.setHTML(data.content);
                    } catch (e) {
                        box.setHTML('Error');
                    }
                }
            }
        });
    }
};