# Java経験者向け PHPサンプル

PHP 8.2以降の構文で作った、フレームワークに依存しない学習用コードです。
新しく学習環境を作る場合の基準は PHP 8.4。案件のバージョンが判明したらそちらを優先します。
必要な拡張は PDO と pdo_sqlite。外部パッケージ、Composer、Docker は初回実行には不要です。
保存文字コードは UTF-8。DB は public/ の外の var/tasks.sqlite に作成します。

## 1. 環境を確認

ZIPを展開し、php-starterフォルダーでターミナルを開きます。

```text
php -v
php --ini
php -m
```

php -m に PDO と pdo_sqlite が必要です。「could not find driver」が出たら拡張を確認します。
Windowsでは公式配布のPHPを展開してPATHを設定し、php.ini-developmentをphp.iniとしてコピー。
必要に応じて extension_dir = "C:/tools/php/ext" と extension=pdo_sqlite を設定します。
C:/tools/php/ext は、実際にPHPを展開した場所のextフォルダーの絶対パスへ置き換えてください。
設定ファイルの場所は php --ini で確認し、変更後は起動中のサーバーを再起動します。
Windows向けランタイムの前提条件は https://www.php.net/manual/en/install.windows.php を参照。

## 2. CLI例を実行

```text
php examples/01_basics.php
php examples/02_objects.php
php examples/03_transactions.php
php examples/04_html.php
php tests/run.php
```

01_basics.php の出力:

```text
Java developer: 18 years
5
bool(true)
bool(false)
Java
PHP
SQL
guest
bool(false)
bool(true)
bool(true)
[200,400,600]
TypeError: string is not int
```

02_objects.php は before / after / after / [2,4] の順で出力します。
03_transactions.php は Rollback completed / tasks=1 / logs=1 を出力します。
04_html.php は &lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt; を出力します。
tests/run.php はインメモリDBを使い、成功時は Passed 20 checks と出力します。
assert() の有効・無効に依存しない検査です。

## 3. APIを起動

```text
php bin/init-db.php
php -S 127.0.0.1:8000 -t public public/index.php
```

内蔵サーバーはこの学習のために localhost で使用します。終了は Ctrl+C。
init-db.php は既存データを消しません。やり直す場合はサーバー停止後、学習用の var/tasks.sqlite を削除して再実行。
認証・認可・CSRF対策は本サンプルの実装範囲外です。公開用アプリへそのまま転用しないでください。

別ターミナルから実行します。Windows PowerShellでは curl の別名を避けるため curl.exe を使います。
macOS/Linuxでは以下の curl.exe を curl に置き換えます。

```text
curl.exe -i http://127.0.0.1:8000/tasks
curl.exe -i -H "Content-Type: application/json" --data-binary "@requests/create.json" http://127.0.0.1:8000/tasks
curl.exe -i http://127.0.0.1:8000/tasks/1
curl.exe -i -X PATCH -H "Content-Type: application/json" --data-binary "@requests/done.json" http://127.0.0.1:8000/tasks/1
curl.exe -i -X DELETE http://127.0.0.1:8000/tasks/1
```

IDは登録結果の id に置き換えてください。Location ヘッダーにも作成した資源のパスを返します。
POST の例: {"id":1,"title":"PHPの学習を始める","done":false}。
PATCH は title 変更ではなく、done の真偽値だけを更新する仕様です。
VS CodeのREST Client拡張を既に使っている場合は requests.http からも実行できます。

| 操作 | 正常時 | 主な異常時 |
|---|---|---|
| GET /tasks | 200 と一覧。空のとき [] | 内部エラー 500 |
| GET /tasks/{id} | 200 と1件 | 存在しないID 404 |
| POST /tasks | 201 と登録結果 | JSON不正400、入力不正422、Content-Type不正415 |
| PATCH /tasks/{id} | 200 と更新結果 | 入力不正422、存在しないID404 |
| DELETE /tasks/{id} | 204、本文なし | 存在しないID404 |
| 既知URLで非対応メソッド | - | 405 と Allow ヘッダー |
| 未知URL | - | 404 |

title は trim() 後に1-200 **バイト**。strlen() は日本語の文字数ではありません。
文字数要件へ変える演習では mbstring を追加して mb_strlen($title, 'UTF-8') を使います。
HTTP入力は先に is_string / is_bool で型を確認し、数値や "false" を勝手に変換しません。

## 4. 異常系を確認

```text
curl.exe -i -H "Content-Type: application/json" --data-binary "@requests/bad-type.json" http://127.0.0.1:8000/tasks
curl.exe -i -H "Content-Type: application/json" --data-binary "@requests/blank.json" http://127.0.0.1:8000/tasks
curl.exe -i -X PATCH -H "Content-Type: application/json" --data-binary "@requests/done-string.json" http://127.0.0.1:8000/tasks/1
```

上の3件は422になります。PHPの TypeError に任せず、HTTP境界で入力不正を返します。
不正JSON、JSON配列、存在しないID、削除後のGET、PATCHで done=false も試してください。
500 の詳細はサーバー側ログに出し、HTTP本文は一般的なエラーメッセージだけを返します。

## 5. Javaからの読み替え

| ファイル | Javaで近い役割 |
|---|---|
| public/index.php | Controller/Servlet相当のHTTP境界と手動ルーティング |
| src/Application/TaskService.php | Service。入力ルールとユースケース |
| src/Domain/TaskRepository.php | Repositoryインターフェース |
| src/Infrastructure/PdoTaskRepository.php | JDBCで書いたRepository実装 |
| src/Domain/Task.php | 戻り値用のDTOに近い、変更不可のプロパティを持つクラス |
| bootstrap.php | 起動処理とオートローダー。DIコンテナは使わない |
| schema.sql | 学習用テーブル定義 |
| examples/03_transactions.php | 複数更新のcommit/rollback演習 |
| tests/run.php | 小さな検査ランナー。現場では既存のPHPUnit等を優先 |

データはSQLiteです。MySQL/PostgreSQLの接続文字列・SQL・ドライバー設定とは異なります。
PDOを使う点は応用できますが、DB固有の差まで隠蔽されるわけではありません。

## 6. Composerを学ぶとき

Composerがインストール済みなら、同じフォルダーで実行できます。

```text
composer install
composer dump-autoload
composer test
```

vendor/autoload.php が作られると、bootstrap.php はComposerのオートローダーを使います。
無い場合は教材の簡易ローダーを使います。どちらも App\ のクラスを src/ 以下から読みます。
この教材は依存パッケージを持たないため、初回用の composer.lock を同梱していません。
現場の既存アプリは composer.lock に従って composer install。依存更新が目的のときに composer update。
アプリの lock は通常Gitへ含め、vendor/ は含めません。

## 7. 追加演習

1. GET /tasks に done で絞り込む機能を追加。true / false / 未指定 / 不正値を決める。
2. 日本語タイトルの文字数検証を mb_strlen に変更し、境界値検査を追加。
3. title 更新を追加。存在しないID・空文字・200文字をどう扱うか先に決める。
4. tests/run.php の検査を現場のPHPUnitへ移植。
5. Laravel案件なら同じ機能を Route / Controller / FormRequest / Service へ対応づける。

公式資料の参照日: 2026-10-02。対応表と1か月の計画は同封の入門資料を参照。

## 作成時の検証記録

2026-10-02、PHP 8.3.6 CLI（Ubuntu配布、PDO/pdo_sqlite）で以下を確認しています。

- PHPファイル12件の構文チェック。
- CLIサンプル4件の出力が説明と一致。
- Repository/Serviceの検査20項目が成功。
- HTTP 33ケースが成功。Unicode入力、CRUD、falseの更新、型不正、壊れたJSON、
  Content-Type、存在しないID、405/Allow、SQLに見える値、204の空本文などを確認。

PHP 8.4/8.5、Windowsでの実行、Composerのオートローダー経由の実行は未検証です。
教材の互換対象はPHP 8.2以降ですが、現場の正確な環境でも起動と検査を確認してください。
