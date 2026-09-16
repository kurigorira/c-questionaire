# c-questionaire

このリポジトリは PHP / SQLite のインフルエンザ予防接種アンケートです。PC版・Web版の Codex で同じ作業方針を使います。

- 日本語で作業結果を説明する。
- 開始時に `git status --short --branch`、現在のコミット、`docs/codex-environment.md` を確認する。未コミット変更を保持する。
- PHP の要求バージョンは `composer.json` の `^8.3`。PDO SQLite、mbstring、zip が必要。低いPHPバージョンで一部テストが通っても要求環境での確認完了とは扱わない。
- 外部PHPライブラリなしで `bootstrap.php` から起動できる。テストはリポジトリルートで `php tests/run.php`（または `composer test`）。変更したPHPファイルは `php -l` でも確認する。
- ローカル起動・DB初期化は README に従う。既存の `.env` を上書きしない。実データを使わず開発用DBで確認する。
- サブディレクトリ配置と `index.php?route=...` のルーティングを維持する。
- `.env`、認証情報、回答データ・SQLiteファイルをGitへ含めない。
- 作業前に対象ブランチを確認する。2026-09-16時点で main は初期READMEのみ、サブディレクトリ修正版は codex-1ig1n6 にある。codex には別のレビュー修正があるので、相互の修正が統合済みと仮定しない。
- GitHub上のコード更新と本番サーバーへのデプロイを区別して報告する。
