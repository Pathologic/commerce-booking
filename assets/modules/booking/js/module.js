const sanitize = function (value) {
    if (typeof value === 'string') value = value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    return value;
}
const parseTemplate = function (tpl, data) {
    for (let key in data) {
        let value = data[key];
        tpl = tpl.replace(new RegExp('\{%' + key + '%\}', 'g'), sanitize(value));
    }

    return tpl;
}

const formatDate = function(date) {
    let pad = function(num) { return ('00'+num).slice(-2) };
    _date = new Date(date);
    return _date.getFullYear()          + '-' +
        pad(_date.getMonth() + 1)  + '-' +
        pad(_date.getDate())
}
const _ = function (key, def = '') {
    return typeof lang !== undefined && typeof lang[key] !== undefined ? lang[key] : def;
};
const datePickerOptions = {
    locale: locale,
    timepicker: false,
    position: 'top center',
    dateFormat: 'yyyy-MM-dd',
    autoClose: true,
    todayButton: new Date(),
    buttons: [{
        content: _('button.today'),
        className: 'custom-button-classname',
        onClick: (dp) => {
            let date = new Date();
            dp.selectDate(date);
            dp.setViewDate(date);
        }
    }, 'clear']
};
const Module = {
    grid: null,
    calendar: null,
    initCalendar: function() {
        const module = this;
        currentView = 'calendar';
        if(this.calendar === null) {
            new Links('#calendarItem');
            this.calendar = new Calendar('#calendar', {
                language: langcode,
                enableRangeSelection: true,
                allowOverlap: true,
                dataSource: function ({year}) {
                    let formData = new FormData();
                    formData.append('mode', 'calendar');
                    formData.append('year', year);
                    formData.append('id', document.getElementById('calendarItem').value);
                    return fetch(connector, {
                        method: 'POST',
                        body: formData,
                        headers: new Headers({
                			'X-Requested-With': 'XMLHttpRequest',
                		}),
                    })
                        .then(result => result.json())
                        .then(result => {
                            if (result) {
                                return result.map(item => ({
                                    startDate: new Date(item[0]),
                                    endDate: new Date(item[1]),
                                    id: item[2],
                                    description: item[3],
                                }));
                            }

                            return [];
                        });
                },
                clickDay: function(e) {
                    if(e.events.length === 1) {
                        Module.edit(e.events[0].id);
                    } else if (e.events.length > 1) {
                        let ids = [];
                        for(let i = 0; i<e.events.length; i++) {
                            ids.push(e.events[i].id);
                        }
                        Module.initCalendarGrid(ids, formatDate(e.date));
                    }
                },
                selectRange: function(e) {
                    if(e.startDate === e.endDate) return;
                    if(e.events.length <= 2) {
                        let docid = document.getElementById('calendarItem').value;
                        if(docid === '') return;
                        Module.create({
                            docid: docid,
                            begin: formatDate(e.startDate),
                            end: formatDate(e.endDate),
                        });
                    }
                },
            })
        } else {
            Module.updateCalendar();
        }
    },
    initGrid: function () {
        const module = this;
        currentView = 'grid';
        if(this.grid === null) {
            $('#grid').datagrid({
                url: connector,
                title: _('module.title'),
                scrollbarSize: 0,
                fitColumns: true,
                pagination: true,
                idField: 'id',
                singleSelect: true,
                striped: true,
                checkOnSelect: false,
                selectOnCheck: false,
                emptyMsg: _('grid.emptyMsg'),
                pageList: [25, 50, 75, 100],
                pageSize: 25,
                columns: [[
                    {field: 'select', checkbox: true},
                    {
                        field: 'docid', title: _('grid.field.item'), width: 140, sortable: true,
                        formatter: function (value, row) {
                            return row.item_title + '<br><small>' + sanitize(row.description) + '</small>';
                        }
                    },
                    {
                        field: 'begin',
                        title: _('grid.field.begin'),
                        width: 110,
                        fixed: true,
                        align: 'center',
                        sortable: true,
                        formatter: function (value) {
                            if (value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                            return value;
                        }
                    },
                    {
                        field: 'end',
                        title: _('grid.field.end'),
                        width: 110,
                        fixed: true,
                        align: 'center',
                        sortable: true,
                        formatter: function (value) {
                            if (value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                            return value;
                        }
                    },
                    {
                        field: 'createdon', title: _('grid.field.createdon'), width: 110, fixed: true, align: 'center', sortable: true,
                        formatter: function (value) {
                            if (value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                            return value;
                        }
                    },
                    {
                        field: 'updatedon',
                        title: _('grid.field.updatedon'),
                        width: 110,
                        fixed: true,
                        align: 'center',
                        sortable: true,
                        formatter: function (value) {
                            if (value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                            return value;
                        }
                    },
                    {
                        field: 'action',
                        width: 40,
                        title: '',
                        align: 'center',
                        fixed: true,
                        formatter: function (value, row) {
                            return '<a class="action delete" href="javascript:void(0)" onclick="Module.delete(' + row.id + ')" title="' + _('button.delete') + '"><i class="fa fa-trash fa-lg"></i></a>';
                        }
                    }
                ]],
                toolbar: '#toolbar',
                onOpen: function () {
                    let options = $.extend({}, datePickerOptions, {timepicker: false, position: 'bottom center'});
                    new AirDatepicker('#searchBegin', options);
                    new AirDatepicker('#searchEnd', options);
                    new Links('#searchItem', 0);
                },
                onDblClickRow: function (index, row) {
                    module.edit(row.id);
                }
            });
        } else {
            this.grid.reload();
        }
    },
    initCalendarGrid: function(ids, date) {
        const module = this;
        $('<div id="calendarGridWnd"><table id="calendarGrid"></table></div>').window({
            modal: true,
            title: date,
            collapsible: false,
            minimizable: false,
            maximizable: false,
            resizable: true,
            width: 700,
            onOpen: function () {
                $('#calendarGrid').datagrid({
                    url: connector,
                    queryParams: {
                        ids: ids
                    },
                    fitColumns: true,
                    pagination: true,
                    idField: 'id',
                    singleSelect: true,
                    striped: true,
                    checkOnSelect: false,
                    selectOnCheck: false,
                    emptyMsg: _('grid.emptyMsg'),
                    pageList: [25, 50, 75, 100],
                    pageSize: 25,
                    height:400,
                    columns: [[
                        {
                            field: 'docid', title: _('grid.field.item'), width: 140, sortable: true,
                            formatter: function (value, row) {
                                return row.item_title + '<br><small>' + sanitize(row.description) + '</small>';
                            }
                        },
                        {
                            field: 'begin',
                            title: _('grid.field.begin'),
                            width: 80,
                            fixed: true,
                            align: 'center',
                            sortable: true,
                            formatter: function (value) {
                                if (value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                                return value;
                            }
                        },
                        {
                            field: 'end',
                            title: _('grid.field.end'),
                            width: 80,
                            fixed: true,
                            align: 'center',
                            sortable: true,
                            formatter: function (value) {
                                if (value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                                return value;
                            }
                        },
                        {
                            field: 'createdon', title: _('grid.field.createdon'), width: 110, fixed: true, align: 'center', sortable: true,
                            formatter: function (value) {
                                if (value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                                return value;
                            }
                        },
                        {
                            field: 'updatedon',
                            title: _('grid.field.updatedon'),
                            width: 110,
                            fixed: true,
                            align: 'center',
                            sortable: true,
                            formatter: function (value) {
                                if (value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                                return value;
                            }
                        },
                        {
                            field: 'action',
                            width: 40,
                            title: '',
                            align: 'center',
                            fixed: true,
                            formatter: function (value, row) {
                                return '<a class="action delete" href="javascript:void(0)" onclick="Module.delete(' + row.id + ')" title="' + _('button.delete') + '"><i class="fa fa-trash fa-lg"></i></a>';
                            }
                        }
                    ]],
                    onDblClickRow: function (index, row) {
                        module.edit(row.id);
                    }
                });
                $(this).window('center');
            },
            onClose: function () {
                module.destroyWindow($('#calendarGridWnd'));
            }
        })
    },
    delete: function (id, callback) {
        let ids = [];
        let grid;
        if(currentView === 'calendar') {
            if($('#calendarGrid')) {
                grid = $('#calendarGrid')
            }
        } else {
            grid = $('#grid');
        }
        if (typeof id === 'undefined') {
            const rows = grid.datagrid('getChecked');
            const options = grid.datagrid('options');
            const pkField = options.idField;
            if (rows.length) {
                $.each(rows, function (i, row) {
                    ids.push(row[pkField]);
                });
            }
            if (!ids.length) {
                const row = grid.datagrid('getSelected');
                if (row) ids.push(row[pkField]);
            }
        } else {
            ids.push(id);
        }
        if (ids.length) {
            $.messager.confirm(_('wnd.delete.title'), _('wnd.delete.message'), function (r) {
                if (r) {
                    $.post(
                        connector,
                        {
                            mode: 'delete',
                            ids: ids
                        },
                        function (result) {
                            if (result.status) {
                                if (typeof callback === 'function') {
                                    callback();
                                }
                               if (currentView === 'calendar') {
                                  Module.updateCalendar();
                               }
                               if(typeof grid !== 'undefined') {
                                   grid.datagrid('reload');
                               }
                            } else {
                                $.messager.alert(_('error'), _('error.delete.message'), 'error')
                            }
                        },
                        'json'
                    ).fail(function () {
                        $.messager.alert(_('error'), _('error.message'), 'error');
                    });
                }
            });
        }
    },
    create: function (data = {}) {
        const tpl = $('#reservationForm').html();
        const module = this;
        const form = parseTemplate(tpl, {
            id: 0,
            description: '',
            begin: data.begin || '',
            end: data.end || '',
        });
        $('<div id="editWnd">' + form + '</div>').dialog({
            modal: true,
            title: _('wnd.create.title'),
            collapsible: false,
            minimizable: false,
            maximizable: false,
            resizable: true,
            width: 400,
            buttons: [
                {
                    text: _('button.save'),
                    iconCls: 'btn-green fa fa-check fa-lg',
                    handler: function () {
                        const wnd = $('#editWnd');
                        const form = $('form', wnd);
                        $('.error', form).removeClass('error');
                        $('div.help-block', form).remove();
                        $.post(connector + '?mode=create',
                            form.serialize(),
                            function (response) {
                                if (response.status) {
                                    if(currentView === 'calendar') {
                                        Module.updateCalendar();
                                    } else {
                                        $('#grid').datagrid('reload');
                                    }
                                    wnd.dialog('close', true);
                                } else {
                                    if (typeof response.errors !== 'undefined' && Object.keys(response.errors).length > 0) {
                                        for (let field in response.errors) {
                                            let $field = $('[data-field="' + field + '"]', form).addClass('error');
                                            let errors = response.errors[field];
                                            for (let error in errors) {
                                                $field.append($('<div class="help-block">' + errors[error] + '</div>'));
                                            }
                                        }
                                    }
                                    if (typeof response.messages !== 'undefined' && response.messages.length > 0) {
                                        $.messager.alert(_('error'), response.messages.join('<br>'), 'error');
                                    }
                                }
                            }, 'json'
                        ).fail(function () {
                            $.messager.alert(_('error'), _('error.message'), 'error');
                        });
                    }
                }, {
                    text: _('button.cancel'),
                    iconCls: 'btn-red fa fa-ban fa-lg',
                    handler: function () {
                        $('#editWnd').dialog('close', true);
                    }
                }
            ],
            onOpen: function () {
                module.initReservationForm();
                new Links('#form_item_select', data.docid || 0);
                $(this).window('center');
            },
            onClose: function () {
                module.destroyWindow($('#editWnd'));
            }
        })
    },
    edit: function (id) {
        const module = this;
        const loader = $('#mainloader', window.parent.document);
        loader.addClass('show');
        $.post(
            connector,
            {
                mode: 'get',
                id: id
            }, function (response) {
                loader.removeClass('show');
                if (response.status) {
                    const tpl = $('#reservationForm').html();
                    const form = parseTemplate(tpl, {
                        id: response.fields.id,
                        description: response.fields.description,
                        begin: response.fields.begin || '',
                        end: response.fields.end || '',
                    });
                    $('<div id="editWnd">' + form + '</div>').dialog({
                        modal: true,
                        title: _('wnd.edit.title') + sanitize(response.fields.id),
                        collapsible: false,
                        minimizable: false,
                        maximizable: false,
                        resizable: true,
                        width: 400,
                        buttons: [
                            {
                                text: _('button.save'),
                                iconCls: 'btn-green fa fa-check fa-lg',
                                handler: function () {
                                    const wnd = $('#editWnd');
                                    const form = $('form', wnd);
                                    $('.error', form).removeClass('error');
                                    $('div.help-block', form).remove();
                                    $.post(connector + '?mode=update',
                                        form.serialize(),
                                        function (response) {
                                            if (response.status) {
                                                if(currentView === 'calendar') {
                                                    Module.updateCalendar();
                                                    if($('#calendarGrid')) {
                                                        $('#calendarGrid').datagrid('reload');
                                                    }
                                                } else {
                                                    $('#grid').datagrid('reload');
                                                }
                                                wnd.dialog('close', true);
                                            } else {
                                                if (typeof response.errors !== 'undefined' && Object.keys(response.errors).length > 0) {
                                                    for (let field in response.errors) {
                                                        let $field = $('[data-field="' + field + '"]', form).addClass('error');
                                                        let errors = response.errors[field];
                                                        for (let error in errors) {
                                                            $field.append($('<div class="help-block">' + errors[error] + '</div>'));
                                                        }
                                                    }
                                                }
                                                if (typeof response.messages !== 'undefined' && response.messages.length > 0) {
                                                    $.messager.alert(_('error'), response.messages.join('<br>'), 'error');
                                                }
                                            }
                                        }, 'json'
                                    ).fail(function () {
                                        $.messager.alert(_('error'), _('error.message'), 'error');
                                    });
                                }
                            },
                            {
                                text: _('button.delete'),
                                iconCls: 'btn-red fa fa-trash fa-lg',
                                handler: function () {
                                    Module.delete(id, function(){
                                        $('#editWnd').dialog('close', true);
                                    });
                                }
                            },
                            {
                                text: _('button.cancel'),
                                iconCls: 'btn-red fa fa-ban fa-lg',
                                handler: function () {
                                    $('#editWnd').dialog('close', true);
                                }
                            }
                        ],
                        onOpen: function () {
                            module.initReservationForm();
                            new Links('#form_item_select', response.fields.docid);
                            $(this).window('center');
                        },
                        onClose: function () {
                            module.destroyWindow($('#editWnd'));
                        }
                    })
                } else {
                    $.messager.alert(_('error'), _('error.message'), 'error');
                }
            },
            'json'
        ).fail(function () {
            loader.removeClass('show');
            $.messager.alert(_('error'), _('error.message'), 'error');
        });
    },
    destroyWindow: function (wnd) {
        const mask = $('.window-mask');
        wnd.window('destroy', true);
        $('.window-shadow,.window-mask').remove();
        $('body').css('overflow', 'auto').append(mask);
    },
    initReservationForm: function () {
        new AirDatepicker('#begin', datePickerOptions);
        new AirDatepicker('#end', datePickerOptions);
    },
    updateCalendar: function()
    {
        let data = this.calendar.getDataSource();
        this.calendar.setDataSource(data);
    }
}
const Search = {
    init: function (btn) {
        if (btn.classList.contains('l-btn-selected')) {
            $('#searchBar').hide();
        } else {
            $('#searchBar').show();
        }
    },
    process: function () {
        const form = $('form', '#searchBar');
        $('#grid').datagrid('load', {
            item: $('#searchItem', form).val(),
            begin: $('#searchBegin', form).val(),
            end: $('#searchEnd', form).val(),
        });
    },
    reset: function () {
        $('form', '#searchBar').get(0).reset();
        this.process();
    }
}

const Links = function (selector, value) {
    return this.init(selector, value);
}
Links.prototype = {
    init: function (selector, value) {
        const that = this;
        this.selector = new SlimSelect({
            select: selector,
            settings: {
                allowDeselect: true,
                placeholderText: '',
                searchText: _('slim.searchText'),
                searchPlaceholder: _('slim.searchPlaceholder'),
                searchingText: _('slim.searchingText'),
                searchHighlight: true,
            },
            events: {
                afterChange: (newValue) => {
                    if(this.selector.select.select.id === 'calendarItem') {
                        Module.updateCalendar()
                    };
                },
                search: (search, currentData) => {
                    return new Promise((resolve, reject) => {
                        if (search.length > 0 && search.length < 3) {
                            return;
                        }
                        $.post(connector,
                            {
                                mode: 'objects',
                                q: search
                            }, function (response) {
                                const options = [{
                                    value: '',
                                    text: '',
                                    display: false,
                                    data: {placeholder: true}
                                }].concat(response
                                    .map((item) => {
                                        return {
                                            value: item.id,
                                            text: item.pagetitle,
                                        }
                                    })
                                );
                                resolve(options);
                            }, 'json')
                    })
                }
            }
        })
        $.post(connector,
            {
                mode: 'objects',
                value: 0
            }, function (response) {
                response = response
                    .map((item) => {
                        return {
                            value: item.id,
                            text: item.pagetitle,
                        }
                    });
                if(selector === '#searchItem' || selector === '#calendarItem') {
                    response = [{value: '', text: '', display: false, data: {placeholder: true}}].concat(response);
                }
                that.selector.setData(response);
                that.selector.setSelected(value);
            }, 'json'
        )
    },
}
