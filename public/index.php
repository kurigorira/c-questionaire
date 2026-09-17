<?php

declare(strict_types=1);

use App\Database;
use App\Eligibility;
use App\Env;
use App\Security;
use App\XlsxExporter;

$appRoot = is_file(__DIR__ . '/bootstrap.php') ? __DIR__ : dirname(__DIR__);
if (!is_file($appRoot . '/bootstrap.php')) {
    throw new RuntimeException('Application bootstrap not found: ' . $appRoot . '/bootstrap.php');
}

require $appRoot . '/bootstrap.php';
Env::load($appRoot . '/.env');
$config = require $appRoot . '/config/app.php';
session_name('flu_questionnaire');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict', 'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'");
$script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$base = rtrim(str_replace('\\', '/', dirname($script)), '/');
$base = $base === '.' ? '' : $base;
$path = (string)($_GET['route'] ?? '');
if ($path === '') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = preg_replace('#^' . preg_quote($base, '#') . '(?:/index\.php)?#', '', $requestPath) ?: '/';
}
$path = '/' . ltrim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function appUrl(string $path = '/'): string
{
    global $script;
    return $path === '/' ? $script : $script . '?route=' . rawurlencode($path);
}
function assetUrl(string $path): string
{
    global $base;
    return $base . '/assets/' . ltrim($path, '/');
}
function redirect(string $path): never { header('Location: ' . appUrl($path)); exit; }
function view(string $title, string $content, bool $admin = false): never {
    global $config;
    $safeTitle = Security::e($title);
    $hospital = Security::e($config['hospital']);
    $formUrl = Security::e(appUrl('/'));
    $adminUrl = Security::e(appUrl('/admin'));
    $loginUrl = Security::e(appUrl('/admin/login'));
    $logoutUrl = Security::e(appUrl('/admin/logout'));
    $cssVersion = (string)(filemtime(__DIR__ . '/assets/app.css') ?: 1);
    $jsVersion = (string)(filemtime(__DIR__ . '/assets/app.js') ?: 1);
    $cssUrl = Security::e(assetUrl('app.css') . '?v=' . $cssVersion);
    $jsUrl = Security::e(assetUrl('app.js') . '?v=' . $jsVersion);
    if ($admin) {
        $nav = "<a class=\"subtle\" href=\"{$formUrl}\">申込ページ</a>";
        if (!empty($_SESSION['admin'])) {
            $nav .= "<a class=\"subtle\" href=\"{$adminUrl}\">回答一覧</a><a class=\"subtle\" href=\"{$logoutUrl}\">ログアウト</a>";
        }
    } else {
        $nav = "<span class=\"badge\">職員ご家族専用</span><a class=\"subtle\" href=\"{$loginUrl}\">管理画面</a>";
    }
    echo "<!doctype html><html lang=\"ja\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>{$safeTitle}｜{$hospital}</title><link rel=\"stylesheet\" href=\"{$cssUrl}\"></head><body><header><div><span class=\"hospital\">{$hospital}</span><span class=\"service\">インフルエンザ予防接種</span></div><nav>{$nav}</nav></header><main>{$content}</main><footer>個人情報は予防接種受付業務の目的にのみ利用します。</footer><script src=\"{$jsUrl}\" defer></script></body></html>";
    exit;
}
function requireAdmin(): void { if (empty($_SESSION['admin'])) redirect('/admin/login'); }
function validDate(string $date): bool { $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date); return $d && $d->format('Y-m-d') === $date; }

try {
    $pdo = Database::connection();
} catch (Throwable $error) {
    error_log('Questionnaire startup error: ' . $error->getMessage());
    http_response_code(500);
    $storage = $appRoot . '/storage';
    $sqlite = in_array('sqlite', PDO::getAvailableDrivers(), true) ? '利用可能' : '利用不可（pdo_sqliteを有効にしてください）';
    $writable = is_dir($storage) && is_writable($storage) ? '書き込み可能' : '書き込み不可（Apache実行ユーザーへ権限を付与してください）';
    echo '<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>起動設定エラー</title><style>body{font-family:sans-serif;background:#f7f6f1;color:#1c2a30;padding:40px}.box{max-width:720px;margin:auto;background:#fff;padding:32px;border-top:5px solid #b54838}code{background:#f1f3f2;padding:3px 7px}li{margin:12px 0}</style></head><body><div class="box"><h1>アンケートを起動できませんでした</h1><p>サーバー管理者は、次の2点を確認してください。</p><ul><li>SQLite：<b>' . Security::e($sqlite) . '</b></li><li>storageディレクトリ：<b>' . Security::e($writable) . '</b></li></ul><p><code>php scripts/init-db.php</code>をサーバー上で実行すると詳しいエラーを確認できます。</p></div></body></html>';
    exit;
}

if ($path === '/' && $method === 'GET') {
    $deadline = Security::e((new DateTimeImmutable($config['deadline']))->format('Y年n月j日'));
    $fee = number_format($config['fee']);
    $childDates = implode('・', array_map(fn($d) => (new DateTimeImmutable($d))->format('n月j日'), $config['child_dates']));
    $token = Security::csrf();
    $submitUrl = Security::e(appUrl('/submit'));
    $clientConfig = Security::e(json_encode([
        'seniorStart' => $config['senior_start'], 'seniorEnd' => $config['senior_end'],
        'childDates' => $config['child_dates'], 'regularTimes' => $config['regular_times'],
        'childTimes' => $config['child_times'],
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    $content = <<<HTML
<section class="network-banner"><strong>院内電子カルテネットワーク専用</strong><span>院内のWindows PCからご利用ください</span></section>
<section class="hero"><div><p class="eyebrow">2026年度 職員ご家族専用</p><h1>インフルエンザ予防接種<br><em>事前申し込み</em></h1><p>この申し込みは職員のご家族専用です。職員本人は対象ではありません。</p></div><div class="hero-date"><small>回答期限</small><strong>{$deadline}</strong><span>自己負担金{$fee}円</span><small class="fee-note">市町村の補助により価格が変わることがあります。</small></div></section>
<section class="notice"><strong>接種について</strong><div class="notice-grid"><p><b>高校生以上・65歳以上</b><br>10月1日〜12月28日の平日<br>午前診療 9:00〜12:00<br>夕診療 17:00〜20:00</p><p><b>小児（6ヶ月～中学生）（注射）</b><br>{$childDates}<br>16:30〜18:30</p><p><b>2歳～小学生（経鼻ワクチン）</b><br>{$childDates}<br>16:30〜18:30・1回接種</p></div></section>
<form action="{$submitUrl}" method="post" id="application-form" data-schedule="{$clientConfig}"><input type="hidden" name="csrf" value="{$token}">
<section class="card"><div class="step-title"><span>01</span><div><h2>職員情報</h2><p>お申し込みをする職員の情報をご入力ください。</p></div></div><div class="fields four"><label>職員番号<input name="employee_no" required autocomplete="off"></label><label>職員氏名<input name="employee_name" required autocomplete="name"></label><label>所属部署<input name="department" required></label><label>連絡先電話番号<input name="employee_phone" required inputmode="tel" autocomplete="tel"></label><label class="wide">メールアドレス <small>任意</small><input name="employee_email" type="email" autocomplete="email"></label></div></section>
<section class="card"><div class="step-title"><span>02</span><div><h2>接種する方</h2><p>ご家族は「もう1名追加」から続けて登録できます。</p></div></div><p id="js-warning" class="alert">画面を準備しています。この表示が消えない場合は、JavaScriptが読み込まれていません。Ctrl＋F5を押してください。</p><div id="people"></div><button class="add" type="button" id="add-person">＋ 接種する方をもう1名追加</button></section>
<section class="agreement"><label><input type="checkbox" required> 案内内容と個人情報の取り扱いを確認し、申し込みます。</label></section><button class="primary" type="submit">入力内容を送信する <span>→</span></button></form>
<script type="text/html" id="person-template"><fieldset class="person"><legend><span class="person-number">1</span>人目</legend><button type="button" class="remove" aria-label="この人を削除">削除</button><div class="fields four"><label>対象（続柄）<select data-name="relationship" required><option value="">選択してください</option><option value="配偶者">配偶者</option><option value="子">子</option><option value="その他家族">その他の家族</option></select></label><label>対象区分<select data-name="target_group" required><option value="">選択してください</option><option>高校生以上</option><option>65歳以上</option><option>小児（6ヶ月～中学生）（注射）</option><option>2歳～小学生（経鼻ワクチン）</option></select></label><label>氏名<input data-name="name" required></label><label>フリガナ<input data-name="kana" required></label><label>生年月日<input data-name="birth_date" type="date" required class="birth"></label><label>性別<select data-name="gender" required><option value="">選択してください</option><option>男性</option><option>女性</option><option>回答しない</option></select></label><label>接種当日の年齢<input class="age-at-appointment" readonly placeholder="希望日選択後に自動計算"></label><label>当院受診歴<select data-name="patient_history" required><option value="">選択してください</option><option>あり</option><option>なし</option><option>不明</option></select></label><label>2回接種希望<select data-name="wants_second_dose" required><option value="">選択してください</option><option>あり</option><option>なし</option></select></label><label>カルテ番号 <small>ある方</small><input data-name="chart_no" inputmode="numeric"></label><label>郵便番号 <small>カルテ番号がない方</small><input data-name="postal_code" inputmode="numeric"></label><label class="wide">住所 <small>カルテ番号がない方</small><input data-name="address"></label><label>電話番号 <small>カルテ番号がない方</small><input data-name="phone" inputmode="tel"></label></div><input type="hidden" data-name="dose_no" value="1"><div class="fields three schedule"><label>接種希望日<select data-name="appointment_date" required class="appointment-date"><option value="">生年月日を先に入力</option></select></label><label>診療時間<select data-name="appointment_time" required class="appointment-time"><option value="">日付を先に選択</option></select></label><label class="wide">備考 <small>任意</small><textarea data-name="notes" rows="2"></textarea></label></div><p class="eligibility-hint"></p></fieldset></script>
HTML;
    view('事前アンケート', $content);
}

if ($path === '/submit' && $method === 'POST') {
    if (!Security::verifyCsrf($_POST['csrf'] ?? null)) { http_response_code(419); $homeUrl = Security::e(appUrl('/')); view('有効期限切れ', "<section class=\"card\"><h1>画面の有効期限が切れました</h1><p>最初からやり直してください。</p><a class=\"button\" href=\"{$homeUrl}\">戻る</a></section>"); }
    $required = ['employee_no','employee_name','department','employee_phone'];
    $errors = [];
    foreach ($required as $field) if (trim((string)($_POST[$field] ?? '')) === '') $errors[] = '職員情報の必須項目を入力してください。';
    $people = $_POST['people'] ?? [];
    if (!is_array($people) || count($people) < 1 || count($people) > 10) $errors[] = '接種する方を1〜10名登録してください。';
    $allowed = ['target_group'=>['高校生以上','65歳以上','小児（6ヶ月～中学生）（注射）','2歳～小学生（経鼻ワクチン）'],'relationship'=>['配偶者','子','その他家族'],'gender'=>['男性','女性','回答しない'],'patient_history'=>['あり','なし','不明'],'wants_second_dose'=>['あり','なし'],'dose_no'=>['1']];
    foreach ($people as $i => $person) {
        $person['vaccine_method'] = ($person['target_group'] ?? '') === '2歳～小学生（経鼻ワクチン）' ? 'nasal' : 'injection';
        foreach (['target_group','relationship','name','kana','birth_date','gender','patient_history','wants_second_dose','dose_no','appointment_date','appointment_time'] as $field) if (trim((string)($person[$field] ?? '')) === '') $errors[] = ($i + 1) . '人目の必須項目を入力してください。';
        foreach ($allowed as $field => $values) if (!in_array((string)($person[$field] ?? ''), $values, true)) $errors[] = ($i + 1) . '人目の選択項目が正しくありません。';
        if (trim((string)($person['chart_no'] ?? '')) === '' && (trim((string)($person['postal_code'] ?? '')) === '' || trim((string)($person['address'] ?? '')) === '' || trim((string)($person['phone'] ?? '')) === '')) $errors[] = ($i + 1) . '人目はカルテ番号、または郵便番号・住所・電話番号を入力してください。';
        if (validDate((string)($person['birth_date'] ?? '')) && validDate((string)($person['appointment_date'] ?? ''))) $errors = array_merge($errors, Eligibility::validate($person, $config));
        else $errors[] = ($i + 1) . '人目の日付が正しくありません。';
    }
    if ($errors) {
        $items = implode('', array_map(fn($e) => '<li>' . Security::e($e) . '</li>', array_unique($errors)));
        $formUrl = Security::e(appUrl('/'));
        view('入力エラー', "<section class=\"card error\"><h1>入力内容をご確認ください</h1><ul>{$items}</ul><p>「入力画面に戻る」を押すと、入力内容を残したまま修正できます。</p><a class=\"button\" href=\"{$formUrl}\" data-history-back>入力画面に戻る</a></section>");
    }
    $pdo->beginTransaction();
    $receipt = 'FLU-' . $config['season'] . '-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
    $stmt = $pdo->prepare('INSERT INTO applications(receipt_no,employee_no,employee_name,department,employee_phone,employee_email,created_at) VALUES(?,?,?,?,?,?,?)');
    $stmt->execute([$receipt, trim($_POST['employee_no']), trim($_POST['employee_name']), trim($_POST['department']), trim($_POST['employee_phone']), trim($_POST['employee_email'] ?? ''), date('c')]);
    $applicationId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO recipients(application_id,relationship,target_group,name,kana,birth_date,gender,patient_history,chart_no,postal_code,address,phone,vaccine_method,dose_no,wants_second_dose,appointment_date,appointment_time,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($people as $p) {
        $method = $p['target_group'] === '2歳～小学生（経鼻ワクチン）' ? 'nasal' : 'injection';
        $stmt->execute([$applicationId,$p['relationship'],$p['target_group'],$p['name'],$p['kana'],$p['birth_date'],$p['gender'],$p['patient_history'],$p['chart_no'] ?? '',$p['postal_code'] ?? '',$p['address'] ?? '',$p['phone'] ?? '',$method,1,$p['wants_second_dose'],$p['appointment_date'],$p['appointment_time'],$p['notes'] ?? '']);
    }
    $pdo->commit();
    unset($_SESSION['csrf']);
    $safe = Security::e($receipt);
    $homeUrl = Security::e(appUrl('/'));
    view('受付完了', "<section class=\"complete card\"><div class=\"check\">✓</div><p class=\"eyebrow\">送信が完了しました</p><h1>お申し込みありがとうございます</h1><p>受付番号を控えてください。</p><div class=\"receipt\"><small>受付番号</small><strong>{$safe}</strong></div><p class=\"muted\">予約確定や変更については、病院担当者からの案内をご確認ください。</p><a class=\"button\" href=\"{$homeUrl}\">トップへ戻る</a></section>");
}

if ($path === '/admin/login') {
    if ($method === 'POST' && Security::verifyCsrf($_POST['csrf'] ?? null)) {
        $user = getenv('ADMIN_USER') ?: 'admin'; $pass = getenv('ADMIN_PASSWORD') ?: 'change-me';
        if (hash_equals($user, (string)($_POST['username'] ?? '')) && hash_equals($pass, (string)($_POST['password'] ?? ''))) { session_regenerate_id(true); $_SESSION['admin'] = true; redirect('/admin'); }
        $error = '<p class="alert">ユーザー名またはパスワードが違います。</p>';
    }
    $error ??= '';
    $token = Security::csrf();
    $loginUrl = Security::e(appUrl('/admin/login'));
    view('管理者ログイン', "<section class=\"login card\"><p class=\"eyebrow\">管理者専用</p><h1>回答管理</h1>{$error}<form method=\"post\" action=\"{$loginUrl}\"><input type=\"hidden\" name=\"csrf\" value=\"{$token}\"><label>ユーザー名<input name=\"username\" required autocomplete=\"username\"></label><label>パスワード<input type=\"password\" name=\"password\" required autocomplete=\"current-password\"></label><button class=\"primary\" type=\"submit\">ログイン</button></form></section>", true);
}
if ($path === '/admin/logout') { session_destroy(); redirect('/admin/login'); }

if ($path === '/admin' || str_starts_with($path, '/admin/export')) {
    requireAdmin();
    $rows = $pdo->query("SELECT a.receipt_no,a.created_at,a.employee_no,a.employee_name,a.department,a.employee_phone,a.employee_email,r.* FROM recipients r JOIN applications a ON a.id=r.application_id ORDER BY r.appointment_date,r.appointment_time,r.id")->fetchAll();
    if ($path === '/admin/export.csv') {
        header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="flu-responses-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'wb'); fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['接種希望日','診療時間','対象区分','続柄','氏名','フリガナ','生年月日','性別','接種当日の年齢','当院受診歴','2回接種希望','カルテ番号','郵便番号','住所','電話番号','接種方法','受付番号','回答日時','職員番号','申込職員氏名','所属','職員連絡先','状態','備考']);
        foreach ($rows as $r) fputcsv($out, array_map([Security::class,'excelSafe'], [$r['appointment_date'],$r['appointment_time'],$r['target_group'],$r['relationship'],$r['name'],$r['kana'],$r['birth_date'],$r['gender'],Eligibility::ageOn($r['birth_date'],$r['appointment_date']),$r['patient_history'],$r['wants_second_dose'],$r['chart_no'],$r['postal_code'],$r['address'],$r['phone'],$r['vaccine_method']==='nasal'?'経鼻ワクチン':'注射',$r['receipt_no'],$r['created_at'],$r['employee_no'],$r['employee_name'],$r['department'],$r['employee_phone'],$r['status'],$r['notes']])); exit;
    }
    if ($path === '/admin/export.xlsx') {
        $data = [['接種希望日','診療時間','対象区分','続柄','氏名','フリガナ','生年月日','性別','接種当日の年齢','当院受診歴','2回接種希望','カルテ番号','郵便番号','住所','電話番号','接種方法','受付番号','回答日時','職員番号','申込職員氏名','所属','職員連絡先','状態','備考']];
        foreach ($rows as $r) $data[] = array_map([Security::class,'excelSafe'],[$r['appointment_date'],$r['appointment_time'],$r['target_group'],$r['relationship'],$r['name'],$r['kana'],$r['birth_date'],$r['gender'],Eligibility::ageOn($r['birth_date'],$r['appointment_date']),$r['patient_history'],$r['wants_second_dose'],$r['chart_no'],$r['postal_code'],$r['address'],$r['phone'],$r['vaccine_method']==='nasal'?'経鼻ワクチン':'注射',$r['receipt_no'],$r['created_at'],$r['employee_no'],$r['employee_name'],$r['department'],$r['employee_phone'],$r['status'],$r['notes']]);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="flu-responses-' . date('Ymd') . '.xlsx"'); XlsxExporter::output($data); exit;
    }
    $count = count($rows); $nasal = count(array_filter($rows, fn($r) => $r['vaccine_method']==='nasal')); $injection=$count-$nasal;
    $trs = ''; foreach($rows as $r) { $methodName=$r['vaccine_method']==='nasal'?'経鼻':'注射'; $trs.='<tr><td>'.Security::e($r['appointment_date']).'<br><small>'.Security::e($r['appointment_time']).'</small></td><td><b>'.Security::e($r['name']).'</b><br><small>'.Security::e($r['kana']).'</small><br><small>'.Security::e($r['target_group']).'</small></td><td>'.Security::e($r['employee_name']).'<br><small>'.Security::e($r['department']).'</small></td><td><span class="pill">'.Security::e($methodName).'</span></td><td>'.Security::e($r['wants_second_dose']).'</td><td>'.Security::e($r['status']).'</td></tr>'; }
    if(!$trs)$trs='<tr><td colspan="6" class="empty">まだ回答はありません。</td></tr>';
    $csvUrl = Security::e(appUrl('/admin/export.csv')); $xlsxUrl = Security::e(appUrl('/admin/export.xlsx'));
    $content="<section class=\"admin-head\"><div><p class=\"eyebrow\">ADMINISTRATION</p><h1>回答一覧</h1></div><div class=\"exports\"><a href=\"{$csvUrl}\">CSV</a><a class=\"button\" href=\"{$xlsxUrl}\">Excelをダウンロード</a></div></section><section class=\"stats\"><div><small>申込人数</small><strong>{$count}</strong></div><div><small>注射</small><strong>{$injection}</strong></div><div><small>経鼻ワクチン</small><strong>{$nasal}</strong></div></section><section class=\"table-card\"><table><thead><tr><th>接種日時</th><th>接種対象者</th><th>申込職員</th><th>方法</th><th>2回接種希望</th><th>状態</th></tr></thead><tbody>{$trs}</tbody></table></section>";
    view('回答一覧', $content, true);
}

http_response_code(404); $homeUrl = Security::e(appUrl('/')); view('ページが見つかりません', "<section class=\"card\"><h1>ページが見つかりません</h1><a class=\"button\" href=\"{$homeUrl}\">トップへ</a></section>");
