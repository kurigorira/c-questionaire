# PC版・Web版での作業の引き継ぎ

共通のコードは https://github.com/kurigorira/c-questionaire 、共通の作業指示はリポジトリルートの `AGENTS.md` で管理します。同じログインでも、別PCのローカルファイルやPHP、Git認証がそろうわけではありません。

## 現在の基準

- 2026-09-16時点のサブディレクトリ修正版: `codex-1ig1n6`。
- `main` はこの時点では初期READMEのみです。修正版を使う場合はブランチを選んでください。
- ローカルブランチ名が違っても、同じリモートブランチ・コミットを取得すれば同じコードです。
- 実行環境: PHP 8.3 系、PDO SQLite、mbstring、zip。本番は Apache 2.4。
- 手元のPCでは PHP 8.0.30 で既存6テストが通りましたが、これは PHP 8.3 環境の検証ではありません。

## 別のPCで初めて開く

空の保存先で次を実行して、できた `c-questionaire` フォルダをCodexのプロジェクトとして開きます。

```sh
git clone --branch codex-1ig1n6 https://github.com/kurigorira/c-questionaire.git
cd c-questionaire
git status --short --branch
php --version
php -m
php tests/run.php
```

PHP 8.3 系と必要な拡張をそのPCに用意してください。既存チェックアウトがある場合は再クローンせず、変更を確認してからfetchし、対象ブランチで `git pull --ff-only` します。競合や分岐がある場合は状態を確認し、強制上書きしません。

## Web版で開く

Codexで `kurigorira/c-questionaire` と `codex-1ig1n6` を選びます。クラウド環境側にもPHP 8.3系と必要な拡張を用意してください。開始時に `AGENTS.md` を読み、`php --version`、`php -m`、`php tests/run.php` で確認します。

この文書を保存しただけでは、クラウドの環境設定は変更されません。クラウド環境設定と起動確認は別途必要です。

## 別の環境へ作業を渡す

作業したブランチとコミットを記録し、GitHubへpushした変更を次の環境で取得します。未コミット・未pushの変更は別PCへ引き継がれません。Web版で新しい作業ブランチができた場合は、そのブランチを次の環境でも選びます。

GitHubへの書き込み認証は端末ごとに確認します。ブラウザへのログインとGitコマンドの認証は別です。認証情報、`.env`、回答データを共有用コードへ入れないでください。

新しいタスクへの開始指示例:

> kurigorira/c-questionaire の codex-1ig1n6 を使い、AGENTS.md と docs/codex-environment.md を読んで続けてください。既存の変更があれば保持してください。

ローカルプレビューはREADMEのセットアップに従います。病院サーバーへの配置と院内ネットワーク接続は、GitHub上の作業環境とは別に必要です。
