<?php

namespace App;

use DateTimeImmutable;

final class Eligibility
{
    public static function ageOn(string $birthDate, string $date): int
    {
        $birth = new DateTimeImmutable($birthDate);
        $onDate = new DateTimeImmutable($date);
        return $birth > $onDate ? -1 : $birth->diff($onDate)->y;
    }

    public static function monthsOn(string $birthDate, string $date): int
    {
        $birth = new DateTimeImmutable($birthDate);
        $onDate = new DateTimeImmutable($date);
        if ($birth > $onDate) return -1;
        $d = $birth->diff($onDate);
        return $d->y * 12 + $d->m;
    }

    public static function isChildCohort(string $birthDate, int $season): bool
    {
        return new DateTimeImmutable($birthDate) > new DateTimeImmutable(($season - 15) . '-04-01');
    }

    public static function isElementaryCohort(string $birthDate, int $season): bool
    {
        return new DateTimeImmutable($birthDate) > new DateTimeImmutable(($season - 13) . '-04-01');
    }

    public static function responsesOpen(string $deadline, ?DateTimeImmutable $now = null): bool
    {
        $today = ($now ?? new DateTimeImmutable())->setTime(0, 0);
        return $today <= new DateTimeImmutable($deadline . ' 00:00:00');
    }

    public static function validate(array $person, array $config): array
    {
        $errors = [];
        $date = $person['appointment_date'] ?? '';
        $birth = $person['birth_date'] ?? '';
        if (!$birth || !$date) return ['生年月日と接種希望日を入力してください。'];
        $months = self::monthsOn($birth, $date);
        if ($months < 0) return ['生年月日は接種希望日以前の日付を入力してください。'];
        if ($months < 6) $errors[] = '接種日時点で生後6か月未満の方は対象外です。';
        $age = self::ageOn($birth, $date);
        if (($person['vaccine_method'] ?? '') === 'nasal' && ($age < 2 || !self::isElementaryCohort($birth, (int)$config['season']))) $errors[] = '経鼻ワクチンは2歳〜小学生が対象です。';
        if (($person['vaccine_method'] ?? '') === 'nasal' && (int)($person['dose_no'] ?? 1) !== 1) $errors[] = '経鼻ワクチンは1回接種です。';
        $isChild = self::isChildCohort($birth, (int)$config['season']);
        $allowedDates = $isChild ? $config['child_dates'] : self::weekdays($config['senior_start'], $config['senior_end']);
        if (!in_array($date, $allowedDates, true)) $errors[] = '年齢区分に対応した接種日を選択してください。';
        $times = $isChild ? $config['child_times'] : $config['regular_times'];
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
