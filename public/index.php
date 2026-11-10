<?php
declare(strict_types=1);

use App\QueueConnectionFactory;
use App\QueuePublisher;
use App\RequestRepository;
use App\StatementRequest;

require dirname(__DIR__) . '/src/bootstrap.php';
session_start();
$repository = new RequestRepository(getenv('STORAGE_DIR') ?: dirname(__DIR__) . '/var/requests');
$error = null;
$acceptedId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
            throw new InvalidArgumentException('Сессия формы устарела. Обновите страницу.');
        }
        $request = StatementRequest::fromForm($_POST);
        (new QueuePublisher(new QueueConnectionFactory(), getenv('RABBITMQ_QUEUE') ?: 'statement_requests'))->publish($request);
        $repository->save($request->id, ['status' => 'queued', 'request' => $request->toArray()]);
        $acceptedId = $request->id;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
$_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
$status = isset($_GET['id']) ? $repository->find((string)$_GET['id']) : null;
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?><!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Заказ выписки</title><link rel="stylesheet" href="/style.css"></head>
<body><main><h1>Банковская выписка</h1><p class="lead">Заявка попадёт в очередь. Страницу можно закрыть — результат придёт на email.</p>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif ?>
<?php if ($acceptedId): ?><div class="alert success"><strong>Запрос принят в обработку.</strong><br>Номер заявки: <?= e($acceptedId) ?><br><a href="/?id=<?= e($acceptedId) ?>">Проверить статус</a></div><?php endif ?>
<form method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><label>Email для уведомления<input type="email" name="email" required placeholder="user@example.com"></label><div class="dates"><label>Дата начала<input type="date" name="date_from" required></label><label>Дата окончания<input type="date" name="date_to" max="<?= date('Y-m-d') ?>" required></label></div><button>Сформировать выписку</button></form>
<?php if (isset($_GET['id'])): ?><section><h2>Статус заявки</h2><?php if (!$status): ?><p>Заявка не найдена.</p><?php else: ?><p><span class="badge"><?= e($status['status']) ?></span></p><?php if (($status['status'] ?? '') === 'completed'): ?><p>Период: <?= e($status['report']['period']) ?></p><p>Итоговый баланс: <?= number_format($status['report']['closingBalance'], 2, '.', ' ') ?> руб.</p><p>Email: <?= $status['notificationSent'] ? 'отправлен' : 'не отправлен' ?></p><?php else: ?><meta http-equiv="refresh" content="3"><?php endif ?><?php endif ?></section><?php endif ?>
</main></body></html>
