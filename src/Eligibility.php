<?php

namespace App;

use DateTimeImmutable;

final class Eligibility
{
    public static function ageOn(string $birthDate, string $date): int
    {
        return (new DateTimeImmutable($birthDate))->diff(new DateTimeImmutable($date))->y;
    }

    public static function monthsOn(string $birthDate, string $date): int
    {
        $d = (new DateTimeImmutable($birthDate))->diff(new DateTimeImmutable($date));
        return $d->y * 12 + $d->m;
    }

    public static function validate(array $person, array $config): array
    {
        $errors = [];
        $date = $person['appointment_date'] ?? '';
        $birth = $person['birth_date'] ?? '';
        if (!$birth || !$date) return ['生年月日と接種希望日を入力してください。'];
        if (self::monthsOn($birth, $date) < 6) $errors[] = '接種日時点で生後6か月未満の方は対象外です。';
        $age = self::ageOn($birth, $date);
        $target = $person['target_group'] ?? '';
        if ($target === '高校生以上' && $age < 15) $errors[] = '高校生以上の対象区分と年齢が一致しません。';
        if ($target === '65歳以上' && $age < 65) $errors[] = '65歳以上の対象区分と年齢が一致しません。';
        if ($target === '小児（6か月～中学生）' && $age > 15) $errors[] = '小児の対象区分と年齢が一致しません。';
        if ($target === '2歳～小学生' && ($age < 2 || $age > 12)) $errors[] = '2歳～小学生の対象区分と年齢が一致しません。';
        if (in_array($target, ['高校生以上', '65歳以上'], true) && ($person['vaccine_method'] ?? '') !== 'injection') $errors[] = '高校生以上・65歳以上の接種方法は注射です。';
        if ($target === '小児（6か月～中学生）' && ($person['vaccine_method'] ?? '') !== 'injection') $errors[] = '小児（6か月～中学生）の接種方法は注射です。';
        if (($person['vaccine_method'] ?? '') === 'nasal' && ($age < 2 || $age > 12)) $errors[] = '経鼻ワクチンは2歳〜小学生が対象です。';
        if (($person['vaccine_method'] ?? '') === 'nasal' && (int)($person['dose_no'] ?? 1) !== 1) $errors[] = '経鼻ワクチンは1回接種です。';
        if (($person['vaccine_method'] ?? '') === 'nasal' && ($person['wants_second_dose'] ?? 'なし') === 'あり') $errors[] = '経鼻ワクチンは1回接種のため、2回接種は希望できません。';
        $isAdult = in_array($target, ['高校生以上', '65歳以上'], true);
        $allowedDates = $isAdult ? self::weekdays($config['senior_start'], $config['senior_end']) : $config['child_dates'];
        if (!in_array($date, $allowedDates, true)) $errors[] = '年齢区分に対応した接種日を選択してください。';
        $times = $isAdult ? $config['regular_times'] : $config['child_times'];
        if (!in_array($person['appointment_time'] ?? '', $times, true)) $errors[] = '接種時間が受付時間外です。';
        return $errors;
    }

    public static function weekdays(string $start, string $end): array
    {
        $dates = [];
        for ($d = new DateTimeImmutable($start), $last = new DateTimeImmutable($end); $d <= $last; $d = $d->modify('+1 day')) {
            if ((int)$d->format('N') <= 5) $dates[] = $d->format('Y-m-d');
        }
        return $dates;
    }
}
