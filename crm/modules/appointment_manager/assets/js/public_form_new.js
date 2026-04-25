(function ($) {
    'use strict';

    var state = {
        calendar: null,
        selectedDate: '',
        selectedStart: '',
        selectedEnd: '',
        activeStaffId: '',
        selectedStaffIds: []
    };

    function crmUrl(path) {
        return window.location.origin + '/crm/' + path;
    }

    function normalizeTime(value) {
        return (value || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function toTwentyFour(value) {
        var normalized = (value || '').trim();
        if (!normalized) {
            return '';
        }
        if (!/am|pm/i.test(normalized)) {
            return normalized;
        }
        var parts = normalized.match(/(\d{1,2}):(\d{2})\s*(am|pm)/i);
        if (!parts) {
            return normalized;
        }
        var hour = parseInt(parts[1], 10);
        var minute = parts[2];
        var suffix = parts[3].toLowerCase();
        if (suffix === 'pm' && hour !== 12) {
            hour += 12;
        }
        if (suffix === 'am' && hour === 12) {
            hour = 0;
        }
        return String(hour).padStart(2, '0') + ':' + minute;
    }

    function hidePreloader() {
        $('.preloader-cl').fadeOut(120);
    }

    function refreshSelect($select) {
        if ($select.length && $select.hasClass('selectpicker')) {
            $select.selectpicker('refresh');
        }
    }

    function getSelectedStaffIds() {
        var values = $('.name-select').val() || [];
        if (!Array.isArray(values)) {
            values = values ? [values] : [];
        }
        return values.filter(Boolean);
    }

    function selectedStaffText() {
        var ids = getSelectedStaffIds();
        if (!ids.length) {
            return 'Select staff members';
        }
        var labels = [];
        $('.name-select option:selected').each(function () {
            var text = $(this).text().trim();
            if (text) {
                labels.push(text);
            }
        });
        return labels.length > 2 ? labels.length + ' staff selected' : labels.join(', ');
    }

    function updateSummary() {
        var locationText = $('.location-select option:selected').text().trim() || 'Select location';
        var staffText = selectedStaffText();
        var timezoneValue = $('#timezone-select').val() || 'Choose timezone';
        $('.location-display').html('<i class="fs-in fa-solid fa-location-dot"></i> ' + locationText + ' <i class="fa-solid fa-pen edit-btn-cl location-edit"></i>');
        $('.name-display').html('<i class="fs-in fa-regular fa-user"></i> ' + staffText + ' <i class="fa-solid fa-pen edit-btn-cl name-edit"></i>');

        var summary = 'No time selected';
        if (state.selectedDate && state.selectedStart && state.selectedEnd) {
            summary = state.selectedDate + ' · ' + state.selectedStart + ' - ' + state.selectedEnd;
        }
        $('#selected-time-display').text(summary);
        $('.time-pckr .filter-option-inner-inner').text(timezoneValue);

        var duration = 0;
        if (state.selectedStart && state.selectedEnd) {
            var start = new Date('2000-01-01T' + toTwentyFour(state.selectedStart) + ':00');
            var end = new Date('2000-01-01T' + toTwentyFour(state.selectedEnd) + ':00');
            duration = Math.max(0, Math.round((end - start) / 60000));
        }
        $('.lst-lft-rg-cl li:first-child p').text(duration ? duration + ' min' : '0 min');
    }

    function ajaxJson(path, payload) {
        return $.ajax({
            url: crmUrl(path),
            method: 'POST',
            data: payload,
            dataType: 'json'
        });
    }

    function resetSelection() {
        state.selectedDate = '';
        state.selectedStart = '';
        state.selectedEnd = '';
        $('#appmgrConflictBanner').hide().empty();
        $('#fillForm').hide();
        $('#remove_from_form').show();
        $('#startTimePicker, #endTimePicker').removeClass('open').hide();
        $('#date-calendar .selected-fc-date').removeClass('selected-fc-date');
        updateSummary();
    }

    function setHiddenValues() {
        var selectedIds = getSelectedStaffIds();
        $('#fillForm input[name="location"]').val($('.location-select').val() || '');
        $('#fillForm input[name="appointee"]').val(selectedIds[0] || '');
        $('#fillForm input[name="additional_appointees"]').val(selectedIds.slice(1).join(','));
        $('#fillForm input[name="timezone"]').val($('#timezone-select').val() || '');
        $('#fillForm input[name="appointment_date"]').val(state.selectedDate || '');
        $('#fillForm input[name="appointment_start_time"]').val(state.selectedStart || '');
        $('#fillForm input[name="appointment_end_time"]').val(state.selectedEnd || '');
    }

    function renderStaffButtons() {
        var $wrap = $('#appmgrStaffSwitcher');
        var ids = getSelectedStaffIds();
        state.selectedStaffIds = ids;

        if (!ids.length) {
            state.activeStaffId = '';
            $wrap.html('<span class="appmgr-staff-switcher-empty">Select staff members to load calendars.</span>');
            if (state.calendar) {
                state.calendar.refetchEvents();
            }
            return;
        }

        if (!ids.includes(state.activeStaffId)) {
            state.activeStaffId = ids[0];
        }

        var html = ids.map(function (id) {
            var text = $('.name-select option[value="' + id + '"]').text().trim();
            var active = id === state.activeStaffId ? ' active' : '';
            return '<button type="button" class="appmgr-staff-chip' + active + '" data-staff-id="' + id + '">' + text + '</button>';
        }).join('');
        $wrap.html(html);
        if (state.calendar) {
            state.calendar.refetchEvents();
        }
    }

    function loadPractitioners(locationId) {
        return ajaxJson('appointment_manager/appointment_manager_client/ajax_search_practitioner', {
            location_id: locationId
        }).done(function (rows) {
            var $select = $('.name-select');
            var selected = getSelectedStaffIds();
            $select.empty();
            $.each(rows || [], function (_, row) {
                var isSelected = selected.includes(String(row.id)) ? ' selected' : '';
                $select.append('<option value="' + row.id + '"' + isSelected + '>' + row.name + '</option>');
            });
            refreshSelect($select);
            renderStaffButtons();
            updateSummary();
        });
    }

    function applyDisabledDays(info) {
        var disabledDates = [];
        try {
            disabledDates = JSON.parse(disabledDatesString || '[]');
        } catch (e) {
            disabledDates = [];
        }
        var isoDate = info.date.toISOString().slice(0, 10);
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var current = new Date(info.date);
        current.setHours(0, 0, 0, 0);
        var isHoliday = disabledDates.some(function (item) {
            return (item.holidaydate || '').slice(0, 10) === isoDate;
        });
        if (current < today || isHoliday) {
            info.el.classList.add('fc-disabled-date');
        }
    }

    function buildCalendar() {
        var el = document.getElementById('date-calendar');
        if (!el || typeof FullCalendar === 'undefined') {
            hidePreloader();
            return;
        }

        state.calendar = new FullCalendar.Calendar(el, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next',
                center: 'title',
                right: ''
            },
            height: 'auto',
            fixedWeekCount: false,
            dayMaxEvents: 3,
            eventDisplay: 'block',
            events: function (info, successCallback, failureCallback) {
                var locationId = $('.location-select').val();
                if (!locationId || !state.activeStaffId) {
                    successCallback([]);
                    return;
                }
                ajaxJson('appointment_manager/appointment_manager_client/public_month_schedule', {
                    location: locationId,
                    appointee: state.activeStaffId,
                    start: info.startStr,
                    end: info.endStr
                }).done(successCallback).fail(failureCallback);
            },
            dayCellDidMount: applyDisabledDays,
            datesSet: function () {
                hidePreloader();
            },
            dateClick: function (info) {
                if ($(info.dayEl).hasClass('fc-disabled-date')) {
                    return;
                }
                if (!$('.location-select').val() || !state.activeStaffId) {
                    alert_float('warning', 'Select the location and at least one staff member first.');
                    return;
                }
                $('#date-calendar .selected-fc-date').removeClass('selected-fc-date');
                $(info.dayEl).addClass('selected-fc-date');
                state.selectedDate = info.dateStr;
                state.selectedStart = '';
                state.selectedEnd = '';
                updateSummary();
                loadStartSlots();
            }
        });

        state.calendar.render();
    }

    function markBusySlots(busyRows, containerSelector, type) {
        $(containerSelector + ' .time-slot').each(function () {
            var $slot = $(this);
            var label = normalizeTime($slot.text());
            var isBusy = busyRows.some(function (row) {
                var start = normalizeTime(row.appointment_time);
                var end = normalizeTime(row.appointment_endtime);
                if (type === 'start') {
                    return label === start;
                }
                return label !== start && label === end;
            });
            $slot.toggleClass('appmgr-slot-busy', isBusy);
            if (isBusy) {
                $slot.closest('.time-slot-wrapper').find('.next-btn-time-slot').hide();
            }
        });
    }

    function loadBusyRows(staffId) {
        return ajaxJson('appointment_manager/appointment_manager_client/get_practitioner_busy_times', {
            location: $('.location-select').val(),
            appointee: staffId,
            appointment_date: state.selectedDate
        });
    }

    function showConflictBanner(rows) {
        var selectedIds = getSelectedStaffIds();
        var conflicts = [];
        var selectedStart = normalizeTime(state.selectedStart);
        var selectedEnd = normalizeTime(state.selectedEnd);

        var requests = selectedIds.map(function (staffId) {
            return loadBusyRows(staffId).then(function (busyRows) {
                var hasConflict = (busyRows || []).some(function (row) {
                    var busyStart = normalizeTime(row.appointment_time);
                    var busyEnd = normalizeTime(row.appointment_endtime);
                    return selectedStart === busyStart || selectedEnd === busyEnd || (selectedStart > busyStart && selectedStart < busyEnd);
                });
                if (hasConflict) {
                    conflicts.push($('.name-select option[value="' + staffId + '"]').text().trim());
                }
            });
        });

        $.when.apply($, requests).always(function () {
            var $banner = $('#appmgrConflictBanner');
            if (conflicts.length) {
                $banner.html('<strong>Scheduling conflict:</strong> ' + conflicts.join(', ') + ' already show busy during the selected time.').show();
            } else {
                $banner.hide().empty();
            }
        });
    }

    function loadStartSlots() {
        ajaxJson('appointment_manager/appointment_manager_client/generate_time_slots', {
            location_id: $('.location-select').val(),
            location: $('.location-select').val(),
            appointee: state.activeStaffId,
            appointment_date: state.selectedDate
        }).done(function (response) {
            $('#timeSlotsContainer').html(response.start || '');
            $('#endTimeSlotsContainer').html(response.end || '');
            loadBusyRows(state.activeStaffId).done(function (busyRows) {
                markBusySlots(busyRows || [], '#timeSlotsContainer', 'start');
                $('#time-picker-date').text(state.selectedDate);
                $('#startTimePicker').addClass('open').show();
                $('#endTimePicker').removeClass('open').hide();
            });
        });
    }

    function loadEndSlots() {
        ajaxJson('appointment_manager/appointment_manager_client/generate_time_slots', {
            location_id: $('.location-select').val(),
            start_time: toTwentyFour(state.selectedStart)
        }).done(function (response) {
            $('#endTimeSlotsContainer').html(response.end || '');
            loadBusyRows(state.activeStaffId).done(function (busyRows) {
                markBusySlots(busyRows || [], '#endTimeSlotsContainer', 'end');
                $('#end-time-picker-date').text(state.selectedDate + ' · from ' + state.selectedStart);
                $('#startTimePicker').removeClass('open').hide();
                $('#endTimePicker').addClass('open').show();
            });
        });
    }

    window.showStartNextBtn = function (element) {
        var $element = $(element);
        if ($element.hasClass('appmgr-slot-busy')) {
            return;
        }
        $('#timeSlotsContainer .time-slot').removeClass('selected');
        $('#timeSlotsContainer .next-btn-time-slot').removeClass('show').hide();
        $element.addClass('selected');
        $element.closest('.time-slot-wrapper').find('.next-btn-time-slot').addClass('show').show();
        state.selectedStart = $element.text().trim();
    };

    window.openEndTimePicker = function () {
        if (!state.selectedStart) {
            return;
        }
        loadEndSlots();
    };

    window.showEndNextBtn = function (element) {
        var $element = $(element);
        if ($element.hasClass('appmgr-slot-busy')) {
            return;
        }
        $('#endTimeSlotsContainer .time-slot').removeClass('selected');
        $('#endTimeSlotsContainer .next-btn-time-slot').removeClass('show').hide();
        $element.addClass('selected');
        $element.closest('.time-slot-wrapper').find('.next-btn-time-slot').addClass('show').show();
        state.selectedEnd = $element.text().trim();
    };

    window.onEndNextClickBtn = function () {
        if (!state.selectedDate || !state.selectedStart || !state.selectedEnd) {
            return;
        }
        setHiddenValues();
        updateSummary();
        showConflictBanner();
        $('#remove_from_form').hide();
        $('#startTimePicker, #endTimePicker').removeClass('open').hide();
        $('#fillForm').fadeIn(150);
    };

    function bindUi() {
        $(document).on('click', '.location-edit', function () {
            $('.location-display').hide();
            $('.location-selct-cl').show();
        });

        $(document).on('click', '.name-edit', function () {
            $('.name-display').hide();
            $('.name-select-cl').show();
        });

        $(document).on('changed.bs.select', '.location-select', function () {
            $('.location-display').show();
            $('.location-selct-cl').hide();
            resetSelection();
            loadPractitioners($(this).val());
        });

        $(document).on('changed.bs.select', '.name-select', function () {
            $('.name-display').show();
            $('.name-select-cl').hide();
            state.activeStaffId = getSelectedStaffIds()[0] || '';
            resetSelection();
            renderStaffButtons();
            updateSummary();
        });

        $(document).on('changed.bs.select', '#timezone-select', updateSummary);

        $(document).on('click', '.appmgr-staff-chip', function () {
            state.activeStaffId = $(this).data('staff-id').toString();
            $('.appmgr-staff-chip').removeClass('active');
            $(this).addClass('active');
            resetSelection();
            if (state.calendar) {
                state.calendar.refetchEvents();
            }
        });

        $(document).on('click', '#closestartTimePicker', function () {
            $('#startTimePicker').removeClass('open').hide();
        });

        $(document).on('click', '#closeEndTimePicker', function () {
            $('#endTimePicker').removeClass('open').hide();
        });

        $(document).on('click', '#backToStartTime', function () {
            $('#endTimePicker').removeClass('open').hide();
            $('#startTimePicker').addClass('open').show();
        });

        $(document).on('click', '#backToCalendar', function (e) {
            e.preventDefault();
            resetSelection();
        });

        $(document).on('submit', '#appmgr-public-form', function () {
            setHiddenValues();
        });
    }

    $(function () {
        refreshSelect($('.location-select'));
        refreshSelect($('.name-select'));
        refreshSelect($('#timezone-select'));
        refreshSelect($('select[name="service_cat"]'));
        refreshSelect($('select[name="treatment"]'));
        bindUi();
        renderStaffButtons();
        updateSummary();
        buildCalendar();
        hidePreloader();
    });
}(jQuery));
