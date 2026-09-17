# インフルエンザ予防接種 事前アンケート

長崎北徳洲会病院の院内電子カルテネットワークで、一時的に使用するアンケートです。Windows 11、Apache 2.4、PHP 8.3、SQLiteで動作します。

## 最新ファイルのダウンロード

GitHubの`codex-1ig1n6`ブランチからダウンロードします。

- ブランチ画面：<https://github.com/kurigorira/c-questionaire/tree/codex-1ig1n6>
- ZIP直接ダウンロード：<https://github.com/kurigorira/c-questionaire/archive/refs/heads/codex-1ig1n6.zip>

ZIPを展開すると`c-questionaire-codex-1ig1n6`というフォルダーになります。その**中身をすべて**次へコピーします。

```text
C:\Apache24\htdocs\c-questionaire
```

## 最短セットアップ（Alias不要）

Apacheの`httpd.conf`にAliasや`<Directory>`は追加しません。管理者PowerShellで次を実行します。

```powershell
cd C:\Apache24\htdocs\c-questionaire
powershell -ExecutionPolicy Bypass -File .\deploy\install-simple.ps1
notepad .env
C:\Apache24\bin\httpd.exe -t
Restart-Service Apache2.4
```

`.env`の`ADMIN_PASSWORD`と`APP_KEY`を変更してください。`httpd.exe -t`が`Syntax OK`になってからApacheを再起動します。

回答画面：

```text
http://10.20.103.125/c-questionaire/
```

管理画面：

```text
http://10.20.103.125/c-questionaire/index.php?route=%2Fadmin%2Flogin
```

詳しい手順と、終了後の削除方法は[Windows設定手順書](docs/WINDOWS-SETUP.md)を参照してください。

> この簡易構成では`.env`とSQLiteもDocumentRoot内に置かれます。インターネット非接続の一時運用という前提です。院内の他システムからもファイルURLへ到達できる可能性はあるため、運用終了後はフォルダー全体を削除してください。

## テスト

```powershell
php tests\run.php
```
