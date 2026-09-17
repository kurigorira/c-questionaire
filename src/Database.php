<?php

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo) return self::$pdo;
        Env::load(dirname(__DIR__) . '/.env');
        $relative = getenv('DB_PATH') ?: 'storage/questionnaire.sqlite';
        // Windows absolute paths (for example C:\\flu-data\\questionnaire.sqlite)
        // must not be prefixed with the project directory.
        $isAbsolute = str_starts_with($relative, '/')
            || str_starts_with($relative, '\\\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $relative) === 1;
        $path = $isAbsolute ? $relative : dirname(__DIR__) . DIRECTORY_SEPARATOR . $relative;
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0770, true);
        self::$pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        self::$pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');
        self::migrate(self::$pdo);
        return self::$pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT, receipt_no TEXT NOT NULL UNIQUE,
            employee_no TEXT NOT NULL, employee_name TEXT NOT NULL, department TEXT NOT NULL,
            employee_phone TEXT NOT NULL, employee_email TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS recipients (
            id INTEGER PRIMARY KEY AUTOINCREMENT, application_id INTEGER NOT NULL,
            relationship TEXT NOT NULL, target_group TEXT NOT NULL DEFAULT '',
            name TEXT NOT NULL, kana TEXT NOT NULL, birth_date TEXT NOT NULL,
            gender TEXT NOT NULL, patient_history TEXT NOT NULL, chart_no TEXT NOT NULL DEFAULT '',
            postal_code TEXT NOT NULL DEFAULT '', address TEXT NOT NULL DEFAULT '', phone TEXT NOT NULL,
            vaccine_method TEXT NOT NULL, dose_no INTEGER NOT NULL DEFAULT 1,
            wants_second_dose TEXT NOT NULL DEFAULT 'なし', appointment_date TEXT NOT NULL,
            appointment_time TEXT NOT NULL, notes TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT '受付済',
            FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE
        );
        CREATE INDEX IF NOT EXISTS idx_recipients_date ON recipients(appointment_date);");

        $columns = array_column($pdo->query('PRAGMA table_info(recipients)')->fetchAll(), 'name');
        if (!in_array('wants_second_dose', $columns, true)) {
            $pdo->exec("ALTER TABLE recipients ADD COLUMN wants_second_dose TEXT NOT NULL DEFAULT 'なし'");
        }
        if (!in_array('target_group', $columns, true)) {
            $pdo->exec("ALTER TABLE recipients ADD COLUMN target_group TEXT NOT NULL DEFAULT ''");
        }

        $duplicateCharts = (int)$pdo->query("SELECT COUNT(*) FROM (
            SELECT chart_no FROM recipients WHERE chart_no <> '' GROUP BY chart_no HAVING COUNT(*) > 1
        )")->fetchColumn();
        if ($duplicateCharts === 0) {
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uniq_recipients_chart_no
                ON recipients(chart_no) WHERE chart_no <> ''");
        }
    }
}
