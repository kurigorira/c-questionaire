(function () {
    'use strict';

    var people = document.getElementById('people');
    var template = document.getElementById('person-template');
    var form = document.getElementById('application-form');
    var warning = document.getElementById('js-warning');
    var historyButtons = document.querySelectorAll('[data-history-back]');
    var historyIndex;

    for (historyIndex = 0; historyIndex < historyButtons.length; historyIndex++) {
        historyButtons[historyIndex].addEventListener('click', function (event) {
            if (window.history.length > 1) {
                event.preventDefault();
                window.history.back();
            }
        });
    }

    if (warning) warning.style.display = 'none';
    if (!people || !template || !form) return;

    var schedule = JSON.parse(form.getAttribute('data-schedule') || '{}');
    var childDates = schedule.childDates || [];
    var regularTimes = schedule.regularTimes || [];
    var childTimes = schedule.childTimes || [];

    function weekdays() {
        var out = [];
        var current = new Date(schedule.seniorStart + 'T12:00:00');
        var last = new Date(schedule.seniorEnd + 'T12:00:00');
        while (current <= last) {
            if (current.getDay() > 0 && current.getDay() < 6) {
                out.push(current.toISOString().slice(0, 10));
            }
            current.setDate(current.getDate() + 1);
        }
        return out;
    }

    function ageOn(birth, date) {
        var born = new Date(birth + 'T12:00:00');
        var onDate = new Date(date + 'T12:00:00');
        var age = onDate.getFullYear() - born.getFullYear();
        if (onDate.getMonth() < born.getMonth() ||
            (onDate.getMonth() === born.getMonth() && onDate.getDate() < born.getDate())) age--;
        return age;
    }

    function formatDate(date) {
        var weekdaysJa = ['日', '月', '火', '水', '木', '金', '土'];
        var value = new Date(date + 'T12:00:00');
        return date.replace(/-/g, '/') + '（' + weekdaysJa[value.getDay()] + '）';
    }

    function field(fieldset, name) {
        return fieldset.querySelector('[data-name="' + name + '"]');
    }

    function isAdultTarget(target) {
        return target === '高校生以上' || target === '65歳以上';
    }

    function updateAge(fieldset) {
        var birth = field(fieldset, 'birth_date').value;
        var date = field(fieldset, 'appointment_date').value;
        fieldset.querySelector('.age-at-appointment').value = birth && date ? ageOn(birth, date) + '歳' : '';
    }

    function updateMethod(fieldset) {
        var target = field(fieldset, 'target_group').value;
        var birth = field(fieldset, 'birth_date').value;
        var nasal = fieldset.querySelector('.nasal');
        var nasalInput = nasal.querySelector('input');
        var eligible = target === '2歳～小学生';
        if (birth && childDates.length) {
            var age = ageOn(birth, childDates[0]);
            eligible = eligible && age >= 2 && age <= 12;
        }
        nasal.className = eligible ? 'choice nasal' : 'choice nasal disabled';
        nasalInput.disabled = !eligible;
        if (!eligible && nasalInput.checked) nasalInput.checked = false;
    }

    function updateSchedule(fieldset) {
        var birth = field(fieldset, 'birth_date').value;
        var target = field(fieldset, 'target_group').value;
        var dateSelect = field(fieldset, 'appointment_date');
        var hint = fieldset.querySelector('.eligibility-hint');
        var dates;
        var html = '<option value="">選択してください</option>';
        var i;

        updateMethod(fieldset);
        if (!birth || !target) {
            dateSelect.innerHTML = '<option value="">対象と生年月日を先に入力</option>';
            hint.innerHTML = '対象区分と生年月日を選択してください。';
            updateAge(fieldset);
            return;
        }

        dates = isAdultTarget(target) ? weekdays() : childDates;
        for (i = 0; i < dates.length; i++) {
            html += '<option value="' + dates[i] + '">' + formatDate(dates[i]) + '</option>';
        }
        dateSelect.innerHTML = html;
        field(fieldset, 'appointment_time').innerHTML = '<option value="">日付を先に選択</option>';
        hint.innerHTML = isAdultTarget(target)
            ? '10月1日～12月28日の平日・午前診療または夕診療から選択できます。'
            : '10月8日・22日・29日の16:30～18:30から選択できます。';
        updateAge(fieldset);
    }

    function updateTimes(fieldset) {
        var target = field(fieldset, 'target_group').value;
        var list = isAdultTarget(target) ? regularTimes : childTimes;
        var select = field(fieldset, 'appointment_time');
        var html = '<option value="">選択してください</option>';
        var i;
        for (i = 0; i < list.length; i++) html += '<option value="' + list[i] + '">' + list[i] + '</option>';
        select.innerHTML = html;
        updateAge(fieldset);
    }

    function renumber() {
        var entries = people.children;
        var i, j, inputs;
        for (i = 0; i < entries.length; i++) {
            entries[i].querySelector('.person-number').innerHTML = i + 1;
            inputs = entries[i].querySelectorAll('[data-name]');
            for (j = 0; j < inputs.length; j++) {
                inputs[j].name = 'people[' + i + '][' + inputs[j].getAttribute('data-name') + ']';
            }
            entries[i].querySelector('.remove').style.display = entries.length === 1 ? 'none' : '';
        }
    }

    function addPerson() {
        var holder = document.createElement('div');
        holder.innerHTML = template.text || template.textContent || template.innerHTML;
        var entry = holder.firstElementChild || holder.children[0];
        people.appendChild(entry);

        field(entry, 'target_group').addEventListener('change', function () { updateSchedule(entry); });
        field(entry, 'birth_date').addEventListener('change', function () { updateSchedule(entry); });
        field(entry, 'appointment_date').addEventListener('change', function () { updateTimes(entry); });
        entry.querySelector('.remove').addEventListener('click', function () {
            people.removeChild(entry);
            renumber();
        });

        var methods = entry.querySelectorAll('[data-name="vaccine_method"]');
        var i;
        for (i = 0; i < methods.length; i++) {
            methods[i].addEventListener('change', function () {
                if (this.value === 'nasal' && this.checked) field(entry, 'wants_second_dose').value = 'なし';
            });
        }
        renumber();
    }

    document.getElementById('add-person').addEventListener('click', addPerson);
    addPerson();
}());
