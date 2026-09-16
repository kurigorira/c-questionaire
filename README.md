# インフルエンザ予防接種 事前アンケート

長崎北徳洲会病院の職員・職員家族向け予約アンケートです。PHP 8.3 / Apache 2.4 / SQLite で動作し、管理画面から追加ライブラリなしでExcel（XLSX）とCSVを出力できます。

## セットアップ

```bash
cp .env.example .env
mkdir -p storage && chmod 770 storage
php scripts/init-db.php
php -S 127.0.0.1:8080 -t public
```

外部PHPライブラリを使っていないため、Composerがないサーバーでも動作します。`http://127.0.0.1:8080/index.php` で回答画面、`index.php?route=%2Fadmin%2Flogin` で管理画面を開きます。本番では `.env` の `ADMIN_PASSWORD` と `APP_KEY` を必ず変更し、ApacheでHTTPSを有効にしてください。

`http://サーバー名/c-clau/public/index.php` のようにサブディレクトリへ配置した場合も、そのURLのまま動作します。Apacheの`mod_rewrite`は必須ではありません。

## HTTP 500になる場合

最初に、プロジェクト全体（`bootstrap.php`、`src/`、`public/`を含む）を配置したことを確認してください。`public/`だけでは動作しません。その後、サーバー上で次を実行します。

```bash
cd /var/www/html/c-clau
cp .env.example .env
mkdir -p storage
chown -R apache:apache storage  # Ubuntu/Debianは www-data:www-data
chmod 770 storage
php scripts/init-db.php
```

`could not find driver`と表示された場合は、PHP 8.3のSQLite拡張をインストールしてApacheを再起動します。

```bash
# Ubuntu / Debian
sudo apt install php8.3-sqlite3
sudo systemctl restart apache2

# RHEL / Rocky Linux（利用中のPHPリポジトリに応じてパッケージ名を確認）
sudo dnf install php-pdo php-sqlite3
sudo systemctl restart httpd
```

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
