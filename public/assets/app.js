const people = document.querySelector('#people');
const template = document.querySelector('#person-template');
const form = document.querySelector('#application-form');
const schedule = form ? JSON.parse(form.dataset.scheduleConfig) : null;

function weekdays(start, end) {
  const dates = [];
  for (let date = new Date(`${start}T12:00:00`), last = new Date(`${end}T12:00:00`); date <= last; date.setDate(date.getDate() + 1)) {
    if (date.getDay() > 0 && date.getDay() < 6) dates.push(date.toISOString().slice(0, 10));
  }
  return dates;
}

function ageOn(birthDate, date) {
  const birth = new Date(`${birthDate}T12:00:00`);
  const onDate = new Date(`${date}T12:00:00`);
  if (birth > onDate) return -1;
  let age = onDate.getFullYear() - birth.getFullYear();
  if (onDate.getMonth() < birth.getMonth() || (onDate.getMonth() === birth.getMonth() && onDate.getDate() < birth.getDate())) age--;
  return age;
}

function afterSchoolCutoff(birthDate, years) {
  return birthDate > `${schedule.season - years}-04-01`;
}

function isChildCohort(birthDate) {
  return afterSchoolCutoff(birthDate, 15);
}

function renumber() {
  [...people.children].forEach((element, index) => {
    element.querySelector('.person-number').textContent = index + 1;
    element.querySelectorAll('[data-name]').forEach(input => { input.name = `people[${index}][${input.dataset.name}]`; });
    element.querySelector('.remove').hidden = people.children.length === 1;
  });
}

function updateNasalEligibility(fieldset) {
  const birthDate = fieldset.querySelector('.birth').value;
  const selectedDate = fieldset.querySelector('.appointment-date').value;
  const dates = isChildCohort(birthDate) ? schedule.childDates : weekdays(schedule.seniorStart, schedule.seniorEnd);
  const appointmentDate = selectedDate || dates[0];
  const eligible = Boolean(birthDate && appointmentDate && ageOn(birthDate, appointmentDate) >= 2 && afterSchoolCutoff(birthDate, 13));
  const nasal = fieldset.querySelector('.nasal');
  const input = nasal.querySelector('input');
  nasal.classList.toggle('disabled', !eligible);
  input.disabled = !eligible;
  if (!eligible && input.checked) input.checked = false;
}

function setSchedule(fieldset) {
  const birthDate = fieldset.querySelector('.birth').value;
  const dateSelect = fieldset.querySelector('.appointment-date');
  if (!birthDate) return;
  const child = isChildCohort(birthDate);
  const dates = child ? schedule.childDates : weekdays(schedule.seniorStart, schedule.seniorEnd);
  dateSelect.innerHTML = '<option value="">選択してください</option>' + dates.map(date => `<option value="${date}">${date.replaceAll('-', '/')}</option>`).join('');
  fieldset.querySelector('.appointment-time').innerHTML = '<option value="">日付を先に選択</option>';
  fieldset.querySelector('.eligibility-hint').textContent = child ? '小児接種日から選択できます。' : '平日の診療時間から選択できます。';
  updateNasalEligibility(fieldset);
}

function setTimes(fieldset) {
  const date = fieldset.querySelector('.appointment-date').value;
  const times = schedule.childDates.includes(date) ? schedule.childTimes : schedule.regularTimes;
  fieldset.querySelector('.appointment-time').innerHTML = '<option value="">選択してください</option>' + times.map(time => `<option>${time}</option>`).join('');
  updateNasalEligibility(fieldset);
}

function addPerson() {
  const element = template.content.firstElementChild.cloneNode(true);
  people.append(element);
  element.querySelector('.birth').addEventListener('change', () => setSchedule(element));
  element.querySelector('.appointment-date').addEventListener('change', () => setTimes(element));
  element.querySelector('.remove').addEventListener('click', () => { element.remove(); renumber(); });
  element.querySelectorAll('[data-name="vaccine_method"]').forEach(radio => radio.addEventListener('change', () => {
    if (radio.value === 'nasal' && radio.checked) element.querySelector('[data-name="dose_no"]').value = '1';
  }));
  renumber();
}

document.querySelector('#add-person')?.addEventListener('click', addPerson);
document.querySelector('[data-history-back]')?.addEventListener('click', () => history.back());
if (people && schedule) addPerson();
