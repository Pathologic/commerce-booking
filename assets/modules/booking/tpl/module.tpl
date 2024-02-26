<!DOCTYPE html>
<html>
<head>
    <title>[%module.title%]</title>
    <link rel="stylesheet" type="text/css" href="[+manager_url+]media/style/[+theme+]/style.css"/>
    <link rel="stylesheet" href="[+manager_url+]media/style/common/font-awesome/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/modules/booking/css/easyui.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/modules/booking/js/datepicker/air-datepicker.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/modules/booking/js/slimselect/slimselect.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/modules/booking/js/calendar/year-calendar.min.css"/>
    [+lexicon+]
    <script type="text/javascript" src="[+site_url+]assets/modules/booking/js/tabpane.js"></script>
    <script type="text/javascript" src="[+manager_url+]media/script/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/locale/easyui-lang-en.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/locale/easyui-lang-[+lang+].js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/booking/js/datepicker/air-datepicker.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/booking/js/datepicker/locale/en.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/booking/js/datepicker/locale/[+lang+].js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/booking/js/slimselect/slimselect.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/booking/js/calendar/year-calendar.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/booking/js/calendar/locales/js-year-calendar.[+lang+].js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/booking/js/module.js"></script>
    <script>
        const connector = '[+connector+]';
        const langcode = '[+lang+]';
        let currentView = 'calendar';
    </script>
    <style>
        body {
            overflow-y: scroll;
        }

        #grid {
            width: 100%;
            min-height: 100px;
        }

        #editWnd {
            overflow: hidden;
            min-height: 100px;
        }

        .delete, .btn-red {
            color: red;
        }

        .btn-green {
            color: green;
        }

        .delete:hover {
            color: #990404;
        }

        #begin, #end, #searchBegin, #searchEnd {
            pointer-events: auto;
        }

        .help-block {
            font-size: 0.8em;
            color: green;
        }

        .error .help-block {
            color: red;
        }

        .form-check-input {
            margin-top: 0.16rem;
        }

        .datagrid-row-selected {
            background: #d3f0ff;
        }

        .tabs-icon {
            margin-top: -6px;
        }

        .l-btn-focus {
            outline: none;
        }

        #searchBar {
            padding: 4px;
        }
        mark.ss-search-highlight {
            padding:0;
        }
    </style>
</head>
<body>
<h1 class="pagetitle">
  <span class="pagetitle-icon">
    <i class="fa fa-hotel"></i>
  </span>
    <span class="pagetitle-text">
    [%module.title%]
  </span>
</h1>
<div id="actions">
    <ul class="btn-group">
        <li><a class="btn btn-secondary" href="#" onclick="document.location.href='index.php?a=106';">[%button.exit%]</a>
        </li>
    </ul>
</div>
<div class="sectionBody">
    <div class="dynamic-tab-pane-control tab-pane" id="bookingPane">
        <script type="text/javascript">
            tpResources = new WebFXTabPane(document.getElementById('bookingPane'), false);
        </script>

        <div class="tab-page" id="calendarTab">
            <h2 class="tab"><i class="fa fa-calendar"></i> [%calendar.title%]</h2>
            <script type="text/javascript">
                tpResources.addTabPage(document.getElementById('calendarTab'), function(){
                    Module.initCalendar();
                });
            </script>
            <div class="">
                <div class="form-group" data-field="docid">
                    <label for="calendarItem">[%label.item.select%]</label>
                    <div class="input-group">
                        <select class="form-control" name="calendarItem" id="calendarItem"><option data-placeholder="true"></option></select>
                    </div>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
        <div class="tab-page" id="reservationsTab">
            <h2 class="tab"><i class="fa fa-table"></i> [%grid.title%]</h2>
            <script type="text/javascript">
                tpResources.addTabPage(document.getElementById('reservationsTab'), function(){
                    Module.initGrid();
                });
            </script>
            <div class="">
                <table id="grid"></table>
            </div>
        </div>
    </div>
</div>
<script type="text/template" id="reservationForm">
    <form>
        <div style="padding:5px 15px;">
            <input type="hidden" name="id" value="{%id%}">
            <input type="hidden" name="formid" value="reservation">
            <div class="form-group" data-field="docid">
                <label for="form_item_select">[%grid.field.item%]</label>
                <div class="input-group">
                    <select class="form-control" name="docid" id="form_item_select" class="item_select"><option data-placeholder="true"></option></select>
                </div>
            </div>
            <div class="form-group" data-field="description">
                <label for="description">[%grid.field.description%]</label>
                <textarea name="description" id="description" class="form-control"
                          rows="2">{%description%}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6" style="padding-left: 5px;" data-field="begin">
                    <label for="begin">[%grid.field.begin%]</label>
                    <input type="text" class="form-control" id="begin" name="begin" readonly value="{%begin%}">
                </div>
                <div class="form-group col-md-6" style="padding-left: 5px;" data-field="end">
                    <label for="end">[%grid.field.end%]</label>
                    <input type="text" class="form-control" id="end" name="end" readonly value="{%end%}">
                </div>
            </div>
        </div>
    </form>
</script>
<div style="display: none; visibility: hidden;" id="toolbar">
    <div id="actionsBar">
        <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-file',plain:true"
           onclick="Module.create(); return false;">[%button.create%]</a>
        <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-trash delete',plain:true"
           onclick="Module.delete(); return false;">[%button.delete%]</a>
        <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-search',toggle:true,plain:true"
           onclick="Search.init(this); return false;">[%button.search%]</a>
    </div>
    <div id="searchBar" style="display: none;">
        <form>
            <div class="form-row align-items-center mb-0">
                <div class="col-4" style="padding-left:5px;">
                    <select class="form-control" id="searchItem" class="item_select"><option data-placeholder="true"></option></select>
                </div>
                <div class="col-2" style="padding-left:5px;">
                    <input type="text" readonly class="form-control form-control-sm" id="searchBegin" name="begin"
                           placeholder="[%grid.field.begin%]">
                </div>
                <div class="col-auto">-</div>
                <div class="col-2" style="padding-left:5px;">
                    <input type="text" readonly class="form-control form-control-sm" id="searchEnd" name="end"
                           placeholder="[%grid.field.end%]">
                </div>
                <div class="col-auto">
                    <a href="#" class="easyui-linkbutton" data-options="iconCls:'btn-green fa fa-check'"
                       onclick="Search.process(); return false;">[%button.search%]</a>
                    <a href="#" class="easyui-linkbutton" data-options="iconCls:'btn-red fa fa-ban'"
                       onclick="Search.reset(); return false;">[%button.cancel%]</a>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    $.parser.onComplete = function () {
        $('#toolbar').css('visibility', 'visible');
    }
</script>
</body>
</html>
