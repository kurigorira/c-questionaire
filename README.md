# インフルエンザ予防接種 事前アンケート

長崎北徳洲会病院の院内電子カルテネットワークで、一時的に使用する**職員ご家族専用**の事前申し込みです。職員本人は対象ではありません。Windows 11、Apache 2.4、PHP 8.3、SQLiteで動作します。

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

申込ページ右上の「管理画面」から管理者ログインへ移動できます。管理画面・ログイン画面右上の「申込ページ」から回答画面へ戻れます。

詳しい手順と、終了後の削除方法は[Windows設定手順書](docs/WINDOWS-SETUP.md)を参照してください。

## 用紙から反映した回答項目

接種希望日、診療時間、対象、氏名、フリガナ、生年月日、性別、接種当日の年齢、当院受診歴、2回接種希望、カルテ番号、郵便番号、住所、電話番号を回答・出力できます。接種当日の年齢は生年月日と希望日から自動計算します。カルテ番号がない場合は、郵便番号・住所・電話番号を必須とします。

対象区分ごとに次の候補だけを表示します。

- 高校生以上：10月1日～12月28日の平日、`午前診療（9:00～12:00）`または`夕診療（17:00～20:00）`、注射
- 65歳以上：10月1日～12月28日の平日、`午前診療（9:00～12:00）`または`夕診療（17:00～20:00）`、注射
- 小児（6ヶ月～中学生）（注射）：10月8日・22日・29日、`16:30～18:30`
- 2歳～小学生（経鼻ワクチン）：10月8日・22日・29日、`16:30～18:30`、1回接種

接種希望日は`2026/10/08（木）`のように曜日付きで表示します。高校生以上・65歳以上の診療時間は「午前診療」「夕診療」という名称付きで表示します。

接種方法の入力欄はありません。対象区分から、成人・65歳以上・小児（注射）は注射、2歳～小学生（経鼻ワクチン）は経鼻ワクチンとして自動保存します。

## 回答の保存場所

登録内容はExcelへ直接書き込むのではなく、次のSQLiteファイルへ保存されます。

```text
C:\Apache24\htdocs\c-questionaire\storage\questionnaire.sqlite
```

保存場所は`.env`の`DB_PATH=storage/questionnaire.sqlite`で指定しています。ExcelまたはCSVは管理画面からこのSQLiteの内容をダウンロードします。

回答送信後はSQLiteへ即時保存されます。管理画面の「Excelをダウンロード」を押すと、その時点までに保存された回答を`.xlsx`形式ですぐ確認できます。

空欄以外のカルテ番号は1回だけ登録できます。同じカルテ番号を再度送信した場合は登録せず、入力エラーを表示します。登録後に修正がある場合は総務課へ連絡してください。

郵便番号・住所・電話番号は、カルテ番号の有無にかかわらず接種対象者全員の必須項目です。

## ボタンが動かない場合

最新版を上書きした後、`deploy\install-simple.ps1`を再実行して`public\assets\app.js`を`assets\app.js`へコピーし、ブラウザーで`Ctrl＋F5`を押してください。画面の「画面を準備しています」が消えればJavaScriptは正常に読み込まれています。

入力エラー画面の「入力画面に戻る」は、ブラウザーの入力履歴へ戻って入力値を保持します。JavaScriptが無効な場合でも申込ページへ戻れるリンクとして動作します。

> この簡易構成では`.env`とSQLiteもDocumentRoot内に置かれます。インターネット非接続の一時運用という前提です。院内の他システムからもファイルURLへ到達できる可能性はあるため、運用終了後はフォルダー全体を削除してください。

## テスト

```powershell
php tests\run.php
```
