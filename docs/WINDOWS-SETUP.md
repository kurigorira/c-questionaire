# Windows 11簡単セットアップ（Alias不要）

## 前提

- Apache：`C:\Apache24`
- DocumentRoot：`C:\Apache24\htdocs`
- PHP：8.3
- インターネット非接続の院内ネットワークで数日間だけ使用
- アプリ配置先：`C:\Apache24\htdocs\c-questionaire`

この簡易手順では、アプリ、`.env`、SQLiteを同じフォルダーへ置きます。Alias、VirtualHost、`<Directory>`、`mod_rewrite`の設定追加は不要です。

## 1. 前回追加したAliasを削除する

`C:\Apache24\conf\httpd.conf`から、`c-questionaire`用に追加した次のブロックだけを削除します。

```apache
Alias "/c-questionaire/" "C:/Apache24/htdocs/c-questionaire/public/"
<Directory "C:/Apache24/htdocs/c-questionaire/public">
    # このブロック全体を削除
</Directory>
```

`pdfcheck`や`nissi`など、既存システムの設定は削除しません。次のIncludeを追加していた場合は、それも削除またはコメントアウトします。

```apache
# Include conf/extra/flu-questionnaire.conf
```

構文を確認します。

```powershell
C:\Apache24\bin\httpd.exe -t
```

`Syntax OK`になればApacheを起動できます。

## 2. 最新ZIPをダウンロードする

インターネット接続可能なPCで次を開きます。

```text
https://github.com/kurigorira/c-questionaire/archive/refs/heads/codex-1ig1n6.zip
```

ZIPを展開し、`c-questionaire-codex-1ig1n6`フォルダーの**中身をすべて**次へコピーします。

```text
C:\Apache24\htdocs\c-questionaire
```

正しくコピーできたか確認します。

```powershell
Test-Path C:\Apache24\htdocs\c-questionaire\bootstrap.php
Test-Path C:\Apache24\htdocs\c-questionaire\public\index.php
Test-Path C:\Apache24\htdocs\c-questionaire\deploy\install-simple.ps1
```

3行とも`True`なら正常です。

## 3. PHP拡張を確認する

```powershell
php -v
php -m | findstr /I "PDO pdo_sqlite sqlite3 zip"
```

`PDO`、`pdo_sqlite`、`sqlite3`、`zip`が表示されなければ、`php.ini`で次を有効にします。

```ini
extension=pdo_sqlite
extension=sqlite3
extension=zip
```

## 4. セットアップを実行する

管理者PowerShellで実行します。

```powershell
cd C:\Apache24\htdocs\c-questionaire
powershell -ExecutionPolicy Bypass -File .\deploy\install-simple.ps1
```

このスクリプトは次を自動実行します。

1. `public`の`index.php`と`assets`をアプリ直下へコピー
2. `.env.example`から`.env`を作成
3. SQLiteを作成・初期化
4. 必要なPHP拡張と保存先への書き込みを確認

最後に`Installation completed.`と表示されれば完了です。

## 5. パスワードを変更する

```powershell
notepad C:\Apache24\htdocs\c-questionaire\.env
```

次の2項目を変更して保存します。

```dotenv
APP_KEY=32文字以上の任意の文字列
ADMIN_PASSWORD=管理者用パスワード
```

## 6. Apacheを再起動する

```powershell
C:\Apache24\bin\httpd.exe -t
Restart-Service Apache2.4
```

サービス名が異なる場合は、Windowsのサービス画面からApacheを再起動します。

## 7. 画面を開く

Webサーバー自身で確認します。

```text
http://127.0.0.1/c-questionaire/
```

院内PCから確認します。

```text
http://10.20.103.125/c-questionaire/
```

管理画面：

```text
http://10.20.103.125/c-questionaire/index.php?route=%2Fadmin%2Flogin
```

## エラー時

### Apacheが起動しない

```powershell
C:\Apache24\bin\httpd.exe -t
Get-Content C:\Apache24\logs\error.log -Tail 30
```

前回追加したAliasまたは`<Directory>`の削除漏れがないか確認します。

### 画面が404になる

```powershell
Test-Path C:\Apache24\htdocs\c-questionaire\index.php
Test-Path C:\Apache24\htdocs\c-questionaire\assets\app.css
```

両方`True`になる必要があります。`public`内のファイルが直下へコピーされていない場合は、`install-simple.ps1`を再実行します。

### 画面が500になる

```powershell
cd C:\Apache24\htdocs\c-questionaire
php scripts\init-db.php
Get-Content C:\Apache24\logs\error.log -Tail 30
```

## 運用終了後の削除

最終的なExcelまたはCSVを管理画面から保存し、必要ならSQLiteをバックアップします。その後Apacheを停止し、アプリフォルダーを削除します。

```powershell
Stop-Service Apache2.4
Remove-Item C:\Apache24\htdocs\c-questionaire -Recurse -Force
Start-Service Apache2.4
```

これでアンケート画面、`.env`、回答データ、SQLiteがまとめて削除されます。
