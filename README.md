# インフルエンザ予防接種 事前アンケート

長崎北徳洲会病院の職員・職員家族向け予約アンケートです。PHP 8.3 / Apache 2.4 / SQLite で動作し、管理画面から追加ライブラリなしでExcel（XLSX）とCSVを出力できます。

## セットアップ

```bash
composer install
cp .env.example .env
mkdir -p storage && chmod 770 storage
php scripts/init-db.php
php -S 127.0.0.1:8080 -t public
```

`http://127.0.0.1:8080` で回答画面、`/admin/login` で管理画面を開きます。本番では `.env` の `ADMIN_PASSWORD` と `APP_KEY` を必ず変更し、ApacheでHTTPSを有効にしてください。

## Apache 2.4

DocumentRoot を `public/` に設定し、`mod_rewrite` を有効化します。設定例は `deploy/apache-vhost.conf` にあります。`storage/` と `.env` は公開ディレクトリ外にあるため、Webから直接取得できません。

## 受付条件（2026年度）

- 高校生以上：2026年10月1日〜12月28日の平日、9:00〜12:00／17:00〜20:00
- 6か月〜中学生：10月8日・22日・29日、16:30〜18:30
- 経鼻ワクチン：2歳〜小学生、1回接種
- 注射：6か月以上。小学生までは2回接種を推奨
- 自己負担金：2,000円、回答期限：2026年9月25日

日時や受付期限は `config/app.php` で変更できます。

## テスト

```bash
composer test
```
